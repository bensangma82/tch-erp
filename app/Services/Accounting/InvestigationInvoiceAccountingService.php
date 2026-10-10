<?php

namespace App\Services\Accounting;

use App\Models\GeneralLedgerAccount;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvestigationInvoiceAccountingService
{
    public function __construct(
        private JournalPostingService $postingService
    ) {}

    public function postInvoice(Invoice $invoice): ?JournalEntry
    {
        return DB::transaction(function () use ($invoice) {
            $invoice = Invoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (strtolower((string) $invoice->invoice_type) !== 'investigation') {
                throw new RuntimeException('Not an investigation invoice.');
            }

            $existing = JournalEntry::query()
                ->where('source_type', 'invoice')
                ->where('source_id', $invoice->id)
                ->where('source_event', 'issued')
                ->first();

            if ($existing) {
                if ($existing->status !== 'posted') {
                    throw new RuntimeException('An unposted invoice journal exists.');
                }

                return $existing;
            }

            $toCents = static function ($value): int {
                $value = (string) $value;

                if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
                    throw new RuntimeException('Invalid accounting amount.');
                }

                [$whole, $fraction] = array_pad(
                    explode('.', $value, 2),
                    2,
                    '0'
                );

                return ((int) $whole * 100)
                    + (int) str_pad($fraction, 2, '0');
            };

            $money = static fn (int $cents): string =>
                number_format($cents / 100, 2, '.', '');

            $totalCents = $toCents($invoice->total_amount);

            if ($totalCents === 0) {
                return null;
            }

            $invoice->load('items.service');

            if ($invoice->items->isEmpty()) {
                throw new RuntimeException('Investigation invoice has no items.');
            }

            $receivable = GeneralLedgerAccount::query()
                ->where('account_code', '1200')
                ->where('is_active', true)
                ->where('allow_posting', true)
                ->firstOrFail();

            $revenueAccounts = GeneralLedgerAccount::query()
                ->whereIn('account_code', [
                    '4201', '4202', '4203',
                    '4204', '4205', '4206',
                ])
                ->where('is_active', true)
                ->where('allow_posting', true)
                ->get()
                ->keyBy('finance_head_id');

            $grouped = [];
            $itemTotalCents = 0;

            foreach ($invoice->items as $item) {
                $service = $item->service;

                if (! $service || ! $service->finance_head_id) {
                    throw new RuntimeException(
                        'Missing service finance-head mapping for invoice item '.$item->id
                    );
                }

                $account = $revenueAccounts->get($service->finance_head_id);

                if (! $account) {
                    throw new RuntimeException(
                        'Missing revenue GL account for service '.$service->code
                    );
                }

                $cents = $toCents($item->amount);

                if ($cents <= 0) {
                    throw new RuntimeException('Investigation item amount must be positive.');
                }

                $itemTotalCents += $cents;

                $departmentId = $service->department_id;
                $key = $account->id.':'.($departmentId ?? 'none');

                if (! isset($grouped[$key])) {
                    $grouped[$key] = [
                        'account_id' => $account->id,
                        'department_id' => $departmentId,
                        'cents' => 0,
                    ];
                }

                $grouped[$key]['cents'] += $cents;
            }

            if ($itemTotalCents !== $totalCents) {
                throw new RuntimeException(
                    'Investigation invoice total does not match its items.'
                );
            }

            $journal = JournalEntry::create([
                'journal_no' => 'JRN-INV-'.$invoice->id,
                'journal_date' => $invoice->invoice_date,
                'source_type' => 'invoice',
                'source_id' => $invoice->id,
                'source_event' => 'issued',
                'reference_no' => $invoice->invoice_no,
                'narration' => 'Investigation invoice '.$invoice->invoice_no,
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
                'reference_no' => $invoice->invoice_no,
                'description' => 'Patient investigation receivable',
            ]);

            $lineNo = 2;

            foreach ($grouped as $group) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'line_no' => $lineNo++,
                    'general_ledger_account_id' => $group['account_id'],
                    'department_id' => $group['department_id'],
                    'debit' => '0.00',
                    'credit' => $money($group['cents']),
                    'reference_no' => $invoice->invoice_no,
                    'description' => 'Investigation revenue',
                ]);
            }

            return $this->postingService->post($journal);
        });
    }
}
