<?php

namespace App\Services\Accounting;

use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PharmacySaleItem;
use App\Models\PharmacyReturnItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IpBillingAccountingService
{
    public function __construct(
        private JournalPostingService $postingService
    ) {}

    public function postFinalBill(
        IpBillingAccount $account
    ): ?JournalEntry {
        return DB::transaction(function () use ($account) {

            $account = IpBillingAccount::query()
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $account->status !== 'finalized'
                || ! $account->final_bill_no
                || ! $account->finalized_at
            ) {
                throw new RuntimeException(
                    'IPD bill must be finalized before accounting.'
                );
            }

            $existing = JournalEntry::query()
                ->where('source_type', 'ip_billing_account')
                ->where('source_id', (string) $account->id)
                ->where('source_event', 'finalized')
                ->first();

            if ($existing) {
                if ($existing->status !== 'posted') {
                    throw new RuntimeException(
                        'An unposted IPD journal already exists.'
                    );
                }

                return $existing;
            }

            $toCents = static function ($value): int {
                $value = (string) $value;

                if (! preg_match(
                    '/^-?\d+(?:\.\d{1,2})?$/',
                    $value
                )) {
                    throw new RuntimeException(
                        'Invalid IPD accounting amount.'
                    );
                }

                $negative = str_starts_with($value, '-');
                $value = ltrim($value, '-');
                [$whole, $fraction] = array_pad(
                    explode('.', $value, 2),
                    2,
                    '0'
                );

                $cents = ((int) $whole * 100)
                    + (int) str_pad($fraction, 2, '0');

                return $negative ? -$cents : $cents;
            };

            $money = static fn (int $cents): string =>
                number_format($cents / 100, 2, '.', '');

            $adjustments = $account->mhisAdjustments()
                ->where('status', 'active')
                ->count();

            if ($adjustments > 0) {
                throw new RuntimeException(
                    'MHIS package adjustments require separate accounting configuration.'
                );
            }

            $charges = $account->charges()
                ->where('status', 'active')
                ->with('service')
                ->get();

            $totalCents = $toCents($account->net_amount);
            $chargeTotalCents = 0;

            $receivable = GeneralLedgerAccount::query()
                ->where('account_code', '1200')
                ->where('is_active', true)
                ->where('allow_posting', true)
                ->firstOrFail();

            $revenueAccounts = GeneralLedgerAccount::query()
    ->where('account_type', 'income')
    ->where('is_active', true)
    ->where('allow_posting', true)
    ->whereNotNull('finance_head_id')
    ->whereNotIn('account_code', ['4101', '4102'])
    ->get()
    ->keyBy('finance_head_id');

            $bedRevenue = GeneralLedgerAccount::query()
                ->where('account_code', '4301')
                ->where('is_active', true)
                ->where('allow_posting', true)
                ->firstOrFail();

            $grouped = [];
            $taxGrouped = ['2311' => 0, '2312' => 0, '2313' => 0];
            $pharmacyRevenue = GeneralLedgerAccount::query()
                ->where('account_code', '4306')
                ->where('is_active', true)
                ->where('allow_posting', true)
                ->first();

            foreach ($charges as $charge) {
                $cents = $toCents($charge->amount);
                if ($cents === 0) {
                    continue;
                }

                $chargeTotalCents += $cents;
                $revenueCents = $cents;

                if ($charge->service_id) {
                    $service = $charge->service;
                    if (! $service || ! $service->finance_head_id) {
                        throw new RuntimeException(
                            'Missing finance-head mapping for IPD charge '.$charge->id
                        );
                    }

                    $revenue = $revenueAccounts->get($service->finance_head_id);
                    if (! $revenue) {
                        throw new RuntimeException(
                            'Missing revenue GL account for service '.$service->code
                        );
                    }
                    $departmentId = $service->department_id;
                } elseif ($charge->charge_type === 'bed') {
                    $revenue = $bedRevenue;
                    $departmentId = null;
                } elseif (in_array($charge->charge_type, ['pharmacy', 'pharmacy_return'], true)) {
                    if (! $pharmacyRevenue) {
                        throw new RuntimeException('GL account 4306 Pharmacy Revenue is unavailable.');
                    }

                    if ($charge->source_type === 'pharmacy_sale_item') {
                        $taxSource = PharmacySaleItem::query()->find((int) $charge->source_id);
                    } elseif ($charge->source_type === 'pharmacy_return_item') {
                        $taxSource = PharmacyReturnItem::query()->find((int) $charge->source_id);
                    } else {
                        throw new RuntimeException(
                            'Unmapped IPD Pharmacy source for charge '.$charge->id
                        );
                    }

                    if (! $taxSource) {
                        throw new RuntimeException(
                            'Missing Pharmacy sale/return item for IPD charge '.$charge->id
                        );
                    }

                    $sign = $cents < 0 ? -1 : 1;
                    $cgstCents = $toCents($taxSource->cgst_amount);
                    $sgstCents = $toCents($taxSource->sgst_amount);
                    $igstCents = $toCents($taxSource->igst_amount);
                    $taxCents = $cgstCents + $sgstCents + $igstCents;
                    $taxableCents = abs($cents) - $taxCents;
                    $sourceTaxableCents = $toCents($taxSource->taxable_amount);

                    // Older pre-GST sale/return rows can have zeroed tax and
                    // taxable columns while the IPD charge still carries value.
                    if ($sourceTaxableCents === 0 && $taxCents === 0) {
                        $taxableCents = abs($cents);
                    }

                    if ($taxableCents < 0 || ($sourceTaxableCents > 0 && abs($taxableCents - $sourceTaxableCents) > 1)) {
                        throw new RuntimeException(
                            'IPD Pharmacy charge does not reconcile with its taxable and GST amounts.'
                        );
                    }

                    $revenue = $pharmacyRevenue;
                    $departmentId = null;
                    $revenueCents = $sign * $taxableCents;
                    $taxGrouped['2311'] += $sign * $cgstCents;
                    $taxGrouped['2312'] += $sign * $sgstCents;
                    $taxGrouped['2313'] += $sign * $igstCents;
                } else {
                    throw new RuntimeException(
                        'Unmapped IPD charge type: '.$charge->charge_type
                    );
                }

                $key = $revenue->id.':'.($departmentId ?? 'none');
                if (! isset($grouped[$key])) {
                    $grouped[$key] = [
                        'account_id' => $revenue->id,
                        'department_id' => $departmentId,
                        'cents' => 0,
                    ];
                }
                $grouped[$key]['cents'] += $revenueCents;
            }

            if ($chargeTotalCents !== $totalCents) {
                throw new RuntimeException(
                    'IPD charges do not reconcile with the final bill.'
                );
            }

            if ($totalCents === 0) {
                return null;
            }

            $journal = JournalEntry::create([
                'journal_no' => 'JRN-IPD-'.$account->id,
                'journal_date' => $account->finalized_at->toDateString(),
                'source_type' => 'ip_billing_account',
                'source_id' => (string) $account->id,
                'source_event' => 'finalized',
                'reference_no' => $account->final_bill_no,
                'narration' => 'IPD final bill '.$account->final_bill_no,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => 1,
                'general_ledger_account_id' => $receivable->id,
                'department_id' => null,
                'debit' => $money($totalCents),
                'credit' => '0.00',
                'reference_no' => $account->final_bill_no,
                'description' => 'IPD patient receivable',
            ]);

            $lineNo = 2;

            foreach ($grouped as $group) {
                $signedCents = (int) $group['cents'];
                if ($signedCents === 0) {
                    continue;
                }
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'line_no' => $lineNo++,
                    'general_ledger_account_id' => $group['account_id'],
                    'department_id' => $group['department_id'],
                    'debit' => $signedCents < 0 ? $money(abs($signedCents)) : '0.00',
                    'credit' => $signedCents > 0 ? $money($signedCents) : '0.00',
                    'reference_no' => $account->final_bill_no,
                    'description' => 'IPD departmental revenue / reversal',
                ]);
            }

            foreach ($taxGrouped as $code => $signedCents) {
                if ($signedCents === 0) {
                    continue;
                }
                $taxAccount = GeneralLedgerAccount::query()
                    ->where('account_code', $code)
                    ->where('is_active', true)
                    ->where('allow_posting', true)
                    ->first();
                if (! $taxAccount) {
                    throw new RuntimeException('Output GST GL account '.$code.' is unavailable for IPD Pharmacy billing.');
                }
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'line_no' => $lineNo++,
                    'general_ledger_account_id' => $taxAccount->id,
                    'department_id' => null,
                    'debit' => $signedCents < 0 ? $money(abs($signedCents)) : '0.00',
                    'credit' => $signedCents > 0 ? $money($signedCents) : '0.00',
                    'reference_no' => $account->final_bill_no,
                    'description' => 'IPD Pharmacy output GST / reversal',
                ]);
            }

            return $this->postingService->post($journal);
        });
    }
}