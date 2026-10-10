<?php

namespace App\Services\Accounting;

use App\Models\GeneralLedgerAccount;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceAccountingService
{
    public function __construct(
        private JournalPostingService $postingService
    ) {}

    public function postOpdInvoice(Invoice $invoice): ?JournalEntry
    {
        return DB::transaction(function () use ($invoice) {

            $invoice = Invoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (strtoupper((string) $invoice->invoice_type) !== 'OPD') {
                throw ValidationException::withMessages([
                    'invoice' => 'Only OPD invoices are supported.',
                ]);
            }

            $existing = JournalEntry::query()
                ->where('source_type', 'invoice')
                ->where('source_id', $invoice->id)
                ->where('source_event', 'issued')
                ->first();

            if ($existing) {
                if ($existing->status !== 'posted') {
                    throw ValidationException::withMessages([
                        'journal' => 'An unposted journal already exists.',
                    ]);
                }

                return $existing;
            }

            $invoice->load(['items', 'encounter']);

            $toCents = static function ($value): int {
                $value = (string) $value;

                if (! preg_match(
                    '/^\d+(?:\.\d{1,2})?$/',
                    $value
                )) {
                    throw ValidationException::withMessages([
                        'amount' => 'Invalid accounting amount.',
                    ]);
                }

                [$whole, $fraction] = array_pad(
                    explode('.', $value, 2),
                    2,
                    '0'
                );

                return ((int) $whole * 100)
                    + (int) str_pad($fraction, 2, '0');
            };

            $total = $toCents($invoice->total_amount);

            if ($total === 0) {
                return null;
            }

            $accountCodes = [
                'OPD-CONS' => '4101',
                'OPD-REG' => '4102',
            ];

            $amounts = [];
            $itemTotal = 0;

            foreach ($invoice->items as $item) {
                $code = (string) $item->code;

                if (! isset($accountCodes[$code])) {
                    throw ValidationException::withMessages([
                        'invoice' => 'Unmapped OPD invoice item: '.$code,
                    ]);
                }

                $amount = $toCents($item->amount);

                if ($amount === 0) {
                    continue;
                }

                $amounts[$code] = ($amounts[$code] ?? 0)
                    + $amount;

                $itemTotal += $amount;
            }

            if ($itemTotal !== $total) {
                throw ValidationException::withMessages([
                    'invoice' => 'Invoice items do not match invoice total.',
                ]);
            }

            $accounts = GeneralLedgerAccount::query()
                ->whereIn('account_code', [
                    '1200',
                    '4101',
                    '4102',
                ])
                ->get()
                ->keyBy('account_code');

            foreach (['1200', ...array_values($accountCodes)] as $code) {
                $account = $accounts->get($code);

                if (
                    ! $account
                    || ! $account->is_active
                    || ! $account->allow_posting
                ) {
                    throw ValidationException::withMessages([
                        'account' => 'GL account '.$code.' is unavailable.',
                    ]);
                }
            }

            $journal = JournalEntry::create([
                'journal_no' => 'JRN-INV-'.$invoice->id,
                'journal_date' => $invoice->invoice_date,
                'source_type' => 'invoice',
                'source_id' => $invoice->id,
                'source_event' => 'issued',
                'reference_no' => $invoice->invoice_no,
                'narration' => 'OPD invoice '.$invoice->invoice_no,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => 1,
                'general_ledger_account_id' => $accounts['1200']->id,
                'department_id' => null,
                'debit' => number_format($total / 100, 2, '.', ''),
                'credit' => '0.00',
                'reference_no' => $invoice->invoice_no,
                'description' => 'Patient receivable',
            ]);

            $lineNo = 2;

            foreach ($accountCodes as $itemCode => $glCode) {
                $amount = $amounts[$itemCode] ?? 0;

                if ($amount === 0) {
                    continue;
                }

                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'line_no' => $lineNo++,
                    'general_ledger_account_id' => $accounts[$glCode]->id,
                    'department_id' => $invoice->encounter?->department_id,
                    'debit' => '0.00',
                    'credit' => number_format($amount / 100, 2, '.', ''),
                    'reference_no' => $invoice->invoice_no,
                    'description' => $itemCode,
                ]);
            }

            return $this->postingService->post($journal);
        });
    }
}