<?php

namespace App\Services\Accounting;

use App\Models\GeneralLedgerAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PharmacyGrn;
use App\Models\PharmacyPurchaseReturn;
use App\Models\PharmacyReturn;
use App\Models\PharmacySale;
use App\Models\PharmacySaleItem;
use App\Models\PharmacySupplierPayment;
use App\Models\PharmacyReturnItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Accrual-accounting integration for Pharmacy transactions.
 *
 * The source transaction and journal are committed in the caller's transaction.
 * Source-type/source-id/source-event uniqueness is enforced by journal_entries.
 */
class PharmacyAccountingService
{
    public function __construct(
        private readonly JournalPostingService $postingService
    ) {}

    public function postGrn(PharmacyGrn $grn): JournalEntry
    {
        return DB::transaction(function () use ($grn) {
            $grn = PharmacyGrn::query()->whereKey($grn->id)->lockForUpdate()->firstOrFail();

            if ($grn->status !== 'completed') {
                throw new RuntimeException('Only completed Pharmacy GRNs can be accounted.');
            }

            $taxable = $this->cents($grn->taxable_amount);
            $cgst = $this->cents($grn->cgst_amount);
            $sgst = $this->cents($grn->sgst_amount);
            $igst = $this->cents($grn->igst_amount);
            $total = $this->cents($grn->total_amount);

            if ($taxable + $cgst + $sgst + $igst !== $total || $total <= 0) {
                throw new RuntimeException('GRN taxable value, GST and total do not reconcile.');
            }

            $recoverable = (bool) config('pharmacy_accounting.input_gst_recoverable', false);
            $inventoryValue = $recoverable ? $taxable : $total;
            $lines = [];
            $this->line($lines, '1300', $inventoryValue, 0, 'Medicine inventory received');

            if ($recoverable) {
                $this->line($lines, '2301', $cgst, 0, 'Recoverable input CGST');
                $this->line($lines, '2302', $sgst, 0, 'Recoverable input SGST');
                $this->line($lines, '2303', $igst, 0, 'Recoverable input IGST');
            }

            $this->line($lines, '2200', 0, $total, 'Pharmacy supplier payable');

            return $this->post(
                'pharmacy_grn', (string) $grn->id, 'received',
                'JRN-PH-GRN-'.$grn->id, $grn->grn_date,
                $grn->supplier_invoice_no ?: $grn->grn_no,
                'Pharmacy GRN '.$grn->grn_no,
                $grn->created_by,
                $lines
            );
        });
    }

    public function postSupplierPayment(PharmacySupplierPayment $payment): JournalEntry
    {
        return DB::transaction(function () use ($payment) {
            $payment = PharmacySupplierPayment::query()
                ->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            $amount = $this->cents($payment->amount);
            if ($amount <= 0) {
                throw new RuntimeException('Supplier payment amount must be positive.');
            }

            $cashAccount = GeneralLedgerAccount::query()
                ->where('finance_account_id', $payment->finance_account_id)
                ->where('is_active', true)
                ->where('allow_posting', true)
                ->first();

            if (! $cashAccount) {
                $financeAccount = $payment->financeAccount;
                if ($financeAccount?->general_ledger_account_id) {
                    $cashAccount = GeneralLedgerAccount::query()
                        ->whereKey($financeAccount->general_ledger_account_id)
                        ->where('is_active', true)
                        ->where('allow_posting', true)
                        ->first();
                }
            }

            if (! $cashAccount || $cashAccount->account_type !== 'asset') {
                throw new RuntimeException(
                    'The selected Pharmacy payment account is not mapped to an active posting asset GL account.'
                );
            }

            $lines = [];
            $this->line($lines, '2200', $amount, 0, 'Reduce Pharmacy supplier payable');
            $this->line($lines, (string) $cashAccount->account_code, 0, $amount, 'Supplier payment from '.$cashAccount->account_name);

            return $this->post(
                'pharmacy_supplier_payment', (string) $payment->id, 'paid',
                'JRN-PH-PAY-'.$payment->id, $payment->payment_date,
                $payment->payment_no,
                'Pharmacy supplier payment '.$payment->payment_no,
                $payment->created_by,
                $lines
            );
        });
    }

    /**
     * OPD sales post revenue, GST, settlement/receivable and COGS together.
     * IPD sales post only COGS here; IPD final-bill accounting recognizes
     * Pharmacy revenue and output GST once, from the running-bill charges.
     */
    public function postSale(PharmacySale $sale): ?JournalEntry
    {
        return DB::transaction(function () use ($sale) {
            $sale = PharmacySale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();
            $items = $sale->items()->lockForUpdate()->get();
            if ($items->isEmpty()) {
                throw new RuntimeException('Pharmacy sale has no sale items.');
            }

            $cogsValue = 0.0;
            foreach ($items as $item) {
                if ($item->unit_cost === null || $item->total_cost === null) {
                    throw new RuntimeException(
                        'Pharmacy sale item '.$item->id.' has no acquisition-cost snapshot; sale accounting was not posted.'
                    );
                }
                $cogsValue += (float) $item->total_cost;
            }

            $cogs = (int) round($cogsValue * 100);
            $lines = [];

            if ($sale->admission_id) {
                if ($cogs <= 0) {
                    return null;
                }
                $this->line($lines, '5101', $cogs, 0, 'Cost of medicines dispensed to IPD');
                $this->line($lines, '1300', 0, $cogs, 'Reduce medicine inventory for IPD dispensing');

                return $this->post(
                    'pharmacy_sale', (string) $sale->id, 'cogs',
                    'JRN-PH-COGS-'.$sale->id, $sale->sale_at->toDateString(),
                    $sale->sale_no, 'IPD Pharmacy dispensing COGS '.$sale->sale_no,
                    $sale->created_by, $lines
                );
            }

            $taxable = $this->cents($sale->taxable_amount);
            $cgst = $this->cents($sale->cgst_amount);
            $sgst = $this->cents($sale->sgst_amount);
            $igst = $this->cents($sale->igst_amount);
            $total = $this->cents($sale->total_amount);
            $paid = $this->cents($sale->paid_amount);
            $balance = $this->cents($sale->balance_amount);

            if ($total === 0 && $cogs === 0) {
                return null;
            }

            if ($taxable + $cgst + $sgst + $igst !== $total || $paid + $balance !== $total) {
                throw new RuntimeException('Pharmacy sale totals, GST, payments and balance do not reconcile.');
            }

            $settlementCodes = ['cash' => '1100', 'upi' => '1110', 'card' => '1120'];
            if ($paid > 0) {
                $mode = strtolower((string) $sale->payment_mode);
                if (! isset($settlementCodes[$mode])) {
                    throw new RuntimeException('Unsupported Pharmacy sale settlement mode: '.$mode);
                }
                $this->line($lines, $settlementCodes[$mode], $paid, 0, strtoupper($mode).' Pharmacy receipt');
            }
            $this->line($lines, '1200', $balance, 0, 'Pharmacy patient receivable');
            $this->line($lines, '4306', 0, $taxable, 'Pharmacy sales revenue');
            $this->line($lines, '2311', 0, $cgst, 'Output CGST on Pharmacy sale');
            $this->line($lines, '2312', 0, $sgst, 'Output SGST on Pharmacy sale');
            $this->line($lines, '2313', 0, $igst, 'Output IGST on Pharmacy sale');
            $this->line($lines, '5101', $cogs, 0, 'Cost of medicines dispensed');
            $this->line($lines, '1300', 0, $cogs, 'Reduce medicine inventory');

            return $this->post(
                'pharmacy_sale', (string) $sale->id, 'completed',
                'JRN-PH-SALE-'.$sale->id, $sale->sale_at->toDateString(),
                $sale->sale_no, 'OPD Pharmacy sale '.$sale->sale_no,
                $sale->created_by, $lines
            );
        });
    }

    public function postPurchaseReturn(PharmacyPurchaseReturn $return): JournalEntry
    {
        return DB::transaction(function () use ($return) {
            $return = PharmacyPurchaseReturn::query()->whereKey($return->id)->lockForUpdate()->firstOrFail();
            $total = $this->cents($return->total_amount);
            $taxable = $this->cents($return->taxable_amount);
            $cgst = $this->cents($return->cgst_amount);
            $sgst = $this->cents($return->sgst_amount);
            $igst = $this->cents($return->igst_amount);
            if ($total <= 0 || $taxable + $cgst + $sgst + $igst !== $total) {
                throw new RuntimeException('Pharmacy purchase-return amounts do not reconcile.');
            }

            $recoverable = (bool) config('pharmacy_accounting.input_gst_recoverable', false);
            $carryingValue = 0;
            foreach ($return->items()->with('stockBatch')->get() as $item) {
                if (! $item->stockBatch) {
                    throw new RuntimeException('Purchase return item has no stock batch.');
                }
                $carryingValue += (int) round(
                    (float) $item->stockBatch->purchase_price * (int) $item->quantity_returned * 100
                );
            }
            $carryingValue = (int) round($carryingValue);
            $inventoryCredit = max(0, $carryingValue);
            $taxCredit = $recoverable ? ($cgst + $sgst + $igst) : 0;
            $variance = ($recoverable ? $taxable : $total) - $inventoryCredit;

            $lines = [];
            $this->line($lines, '2200', $total, 0, 'Supplier credit for Pharmacy purchase return');
            $this->line($lines, '1300', 0, $inventoryCredit, 'Reduce medicine inventory for supplier return');
            if ($recoverable) {
                $this->line($lines, '2301', 0, $cgst, 'Reverse recoverable input CGST');
                $this->line($lines, '2302', 0, $sgst, 'Reverse recoverable input SGST');
                $this->line($lines, '2303', 0, $igst, 'Reverse recoverable input IGST');
            }
            if ($variance > 0) {
                $this->line($lines, '5108', 0, $variance, 'Credit purchase/inventory valuation variance');
            } elseif ($variance < 0) {
                $this->line($lines, '5108', -$variance, 0, 'Debit purchase/inventory valuation variance');
            }

            return $this->post(
                'pharmacy_purchase_return', (string) $return->id, 'completed',
                'JRN-PH-PRET-'.$return->id, $return->return_date,
                $return->return_no, 'Pharmacy purchase return '.$return->return_no,
                $return->created_by, $lines
            );
        });
    }

    public function postSaleReturn(PharmacyReturn $return): ?JournalEntry
    {
        return DB::transaction(function () use ($return) {
            $return = PharmacyReturn::query()->whereKey($return->id)->lockForUpdate()->firstOrFail();
            $sale = $return->sale()->firstOrFail();
            $items = $return->items()->with('saleItem')->get();
            $originalCogsJournal = JournalEntry::query()
                ->where('source_type', 'pharmacy_sale')
                ->where('source_id', (string) $sale->id)
                ->whereIn('source_event', ['completed', 'cogs'])
                ->where('status', 'posted')
                ->exists();

            $cogsReversalValue = 0.0;
            if ($originalCogsJournal) {
                foreach ($items as $item) {
                    if ($item->saleItem?->unit_cost === null) {
                        throw new RuntimeException('Cannot reverse Pharmacy COGS without the original item cost snapshot.');
                    }
                    $cogsReversalValue += (float) $item->saleItem->unit_cost * (int) $item->quantity;
                }
            }
            $cogsReversal = (int) round($cogsReversalValue * 100);

            $lines = [];
            if (! $sale->admission_id) {
                $taxable = $this->cents($return->taxable_amount);
                $cgst = $this->cents($return->cgst_amount);
                $sgst = $this->cents($return->sgst_amount);
                $igst = $this->cents($return->igst_amount);
                // gross_amount is the pre-discount line value; accounting reverses
                // the net taxable amount plus tax actually included in the sale.
                $gross = $taxable + $cgst + $sgst + $igst;
                // Older pre-GST records may have zero tax columns despite a sale value.
                if ($gross === 0 && $this->cents($return->gross_amount) > 0) {
                    $gross = $this->cents($return->gross_amount);
                    $taxable = $gross;
                }
                $refund = $this->cents($return->refund_amount);
                if ($gross <= 0 || $refund > $gross) {
                    throw new RuntimeException('Pharmacy return totals do not reconcile.');
                }

                $this->line($lines, '4306', $taxable, 0, 'Reverse Pharmacy sales revenue');
                $this->line($lines, '2311', $cgst, 0, 'Reverse output CGST');
                $this->line($lines, '2312', $sgst, 0, 'Reverse output SGST');
                $this->line($lines, '2313', $igst, 0, 'Reverse output IGST');

                $refundMode = strtolower((string) $return->refund_mode);
                $refundCode = ['cash' => '1100', 'upi' => '1110', 'card' => '1120'][$refundMode] ?? null;
                if ($refund > 0 && $refundCode) {
                    $this->line($lines, $refundCode, 0, $refund, 'Pharmacy return refund paid');
                    $this->line($lines, '1200', 0, $gross - $refund, 'Patient credit for unrefunded Pharmacy return amount');
                } elseif (in_array($refundMode, ['credit', 'mhis', 'none'], true)) {
                    $this->line($lines, '1200', 0, $gross, 'Patient credit for Pharmacy return');
                } else {
                    throw new RuntimeException('Unsupported Pharmacy return refund mode.');
                }
            }

            if ($cogsReversal > 0) {
                $this->line($lines, '1300', $cogsReversal, 0, 'Restore inventory for returned medicines');
                $this->line($lines, '5101', 0, $cogsReversal, 'Reverse cost of returned medicines');
            }

            if (count($lines) < 2) {
                return null;
            }

            return $this->post(
                'pharmacy_return', (string) $return->id, 'completed',
                'JRN-PH-RET-'.$return->id, $return->returned_at->toDateString(),
                $return->return_no, 'Pharmacy sales return '.$return->return_no,
                $return->created_by, $lines
            );
        });
    }

    private function post(
        string $sourceType,
        string $sourceId,
        string $sourceEvent,
        string $journalNo,
        mixed $date,
        ?string $reference,
        string $narration,
        ?int $createdBy,
        array $lines
    ): JournalEntry {
        $existing = JournalEntry::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('source_event', $sourceEvent)
            ->first();

        if ($existing) {
            if ($existing->status !== 'posted') {
                throw new RuntimeException('An unposted Pharmacy journal already exists for '.$sourceType.' '.$sourceId.' ('.$sourceEvent.').');
            }
            return $existing;
        }

        $journal = JournalEntry::create([
            'journal_no' => $journalNo,
            'journal_date' => $date,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_event' => $sourceEvent,
            'reference_no' => $reference,
            'narration' => $narration,
            'status' => 'draft',
            'created_by' => $createdBy ?: auth()->id(),
        ]);

        $lineNo = 1;
        foreach ($lines as $line) {
            $debit = (int) $line['debit'];
            $credit = (int) $line['credit'];
            if ($debit === 0 && $credit === 0) {
                continue;
            }
            $account = $this->account($line['code']);
            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => $lineNo++,
                'general_ledger_account_id' => $account->id,
                'department_id' => $line['department_id'] ?? null,
                'debit' => number_format($debit / 100, 2, '.', ''),
                'credit' => number_format($credit / 100, 2, '.', ''),
                'reference_no' => $reference,
                'description' => $line['description'],
            ]);
        }

        return $this->postingService->post($journal);
    }

    private function account(string $code): GeneralLedgerAccount
    {
        $account = GeneralLedgerAccount::query()
            ->where('account_code', $code)
            ->where('is_active', true)
            ->where('allow_posting', true)
            ->first();

        if (! $account) {
            throw new RuntimeException('Required Pharmacy GL account '.$code.' is missing, inactive or not postable.');
        }

        return $account;
    }

    private function line(array &$lines, string $code, int $debit, int $credit, string $description): void
    {
        if ($debit < 0 || $credit < 0 || ($debit > 0 && $credit > 0)) {
            throw new RuntimeException('Invalid Pharmacy journal line amount.');
        }
        if ($debit === 0 && $credit === 0) {
            return;
        }
        $lines[] = ['code' => $code, 'debit' => $debit, 'credit' => $credit, 'description' => $description];
    }

    private function cents(mixed $value): int
    {
        $value = (string) $value;
        if (! preg_match('/^-?\d+(?:\.\d{1,4})?$/', $value)) {
            throw new RuntimeException('Invalid Pharmacy accounting amount: '.$value);
        }
        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '-');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');
        $fraction = str_pad($fraction, 4, '0');
        $fourDecimals = ((int) $whole * 10000) + (int) substr($fraction, 0, 4);
        $cents = (int) round($fourDecimals / 100);
        return $negative ? -$cents : $cents;
    }
}
