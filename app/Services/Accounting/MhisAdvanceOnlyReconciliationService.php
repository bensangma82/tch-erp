<?php

namespace App\Services\Accounting;

use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Applies an already-received advance on a FINALIZED bill with exactly one
 * pending/submitted MHIS claim and NO other complicating transactions.
 *
 * Invoked only via the existing permission-gated Finance reconciliation action.
 * Never re-posts a receipt and never changes bill/claim/approved amounts.
 */
final class MhisAdvanceOnlyReconciliationService
{
    public function __construct(private JournalPostingService $postingService) {}

    public function reconcile(IpBillingAccount $input): array
    {
        return DB::transaction(function () use ($input): array {
            $fail = static function (string $message): never {
                throw ValidationException::withMessages(['reconciliation' => $message]);
            };

            $account = IpBillingAccount::query()
                ->whereKey($input->id)->lockForUpdate()->firstOrFail();

            if (!auth()->check()) {
                $fail('An authenticated Finance user is required.');
            }
            if ($account->status !== 'finalized' || !$account->final_bill_no) {
                $fail('The IPD bill must be finalized before applying advances.');
            }

            // Do not touch receipts or claim recognition; only bridge 2100 -> 1200.
            if ($account->mhisClaims()->count() !== 1
                || $account->mhisClaims()->whereNotIn('status', ['pending', 'submitted'])->exists()
                || $account->payments()->exists()
                || $account->refunds()->exists()
                || $account->mhisReceipts()->exists()
                || $account->mhisAdjustments()->exists()
                || $account->staffMedicalBenefitTransactions()->exists()
                || $account->charityAdjustments()->exists()) {
                $fail('Advance-only MHIS reconciliation requires one pending/submitted claim and no other financial adjustments or patient payments.');
            }

            $claim = $account->mhisClaims()->firstOrFail();
            if (JournalEntry::query()
                ->where('source_type', 'ip_billing_mhis_claim')
                ->where('source_id', (string) $claim->id)
                ->where('source_event', 'approved')->exists()) {
                $fail('An MHIS recognition journal already exists; refer to Finance.');
            }

            $finalCount = JournalEntry::query()
                ->where('source_type', 'ip_billing_account')
                ->where('source_id', (string) $account->id)
                ->where('source_event', 'finalized')
                ->where('status', 'posted')->count();
            if ($finalCount !== 1) {
                $fail('Exactly one posted final-bill revenue journal is required.');
            }

            $advances = $account->advances()->orderBy('id')->lockForUpdate()->get();
            if ($advances->isEmpty()) {
                $fail('No patient advance found.');
            }

            $total = 0;
            foreach ($advances as $advance) {
                if ($advance->status !== 'active' || $advance->source_type !== null) {
                    $fail('Inactive or source-linked advances require separate Finance review.');
                }
                $receiptJournals = JournalEntry::query()
                    ->where('source_type', 'ip_billing_advance')
                    ->where('source_id', (string) $advance->id)
                    ->where('source_event', 'received')
                    ->with('lines.generalLedgerAccount')
                    ->get();

                if ($receiptJournals->count() !== 1
                    || $receiptJournals->first()->status !== 'posted') {
                    $fail('Exactly one posted receipt journal is required for advance #'.$advance->id.'.');
                }

                $receiptJournal = $receiptJournals->first();
                $expected = self::paise((string) $advance->amount);
                $mode = strtolower(trim((string) $advance->payment_mode));
                $debitCode = [
                    'cash' => '1100',
                    'upi' => '1110',
                    'card' => '1120',
                ][$mode] ?? null;

                if (!$debitCode || $expected <= 0) {
                    $fail('Unsupported payment mode or amount for advance #'.$advance->id.'.');
                }

                $debitTotal = 0;
                $creditTotal = 0;

                foreach ($receiptJournal->lines as $line) {
                    $code = (string) ($line->generalLedgerAccount?->account_code ?? '');
                    $debit = self::paise((string) $line->debit);
                    $credit = self::paise((string) $line->credit);

                    if ($code === $debitCode && $credit === 0 && $debit > 0) {
                        $debitTotal += $debit;
                    } elseif ($code === '2100' && $debit === 0 && $credit > 0) {
                        $creditTotal += $credit;
                    } else {
                        $fail('Unexpected GL line in advance receipt journal #'.$receiptJournal->id.'.');
                    }
                }

                if ($receiptJournal->lines->count() !== 2
                    || $debitTotal !== $expected
                    || $creditTotal !== $expected) {
                    $fail('Advance receipt journal does not match receipt amount and GL accounts for advance #'.$advance->id.'.');
                }

                $total += $expected;
            }
            $bill = self::paise((string) $account->net_amount);
            $amountPaise = min($total, $bill);
            if ($amountPaise <= 0) {
                $fail('No positive advance amount is eligible for application.');
            }
            $amount = number_format($amountPaise / 100, 2, '.', '');

            $matching = JournalEntry::query()
                ->where('source_type', 'ip_billing_account')
                ->where('source_id', (string) $account->id)
                ->where('source_event', 'advance_applied')
                ->with('lines.generalLedgerAccount')->lockForUpdate()->get();
            if ($matching->count() > 1) {
                $fail('Multiple advance application journals exist; manual reconciliation required.');
            }
            $existing = $matching->first();
            if ($existing) {
                if ($existing->status !== 'posted') {
                    $fail('A non-posted advance application journal already exists.');
                }
                // Idempotent only if its GL lines exactly match the current advance.
                $debit = 0;
                $credit = 0;
                foreach ($existing->lines as $line) {
                    $code = (string) ($line->generalLedgerAccount?->account_code ?? '');
                    $d = self::paise((string) $line->debit);
                    $c = self::paise((string) $line->credit);
                    if ($code === '2100' && $c === 0) $debit += $d;
                    elseif ($code === '1200' && $d === 0) $credit += $c;
                    else $fail('Existing advance journal contains unexpected GL lines.');
                }
                if ($debit !== $amountPaise || $credit !== $amountPaise
                    || $existing->lines->count() !== 2) {
                    $fail('Existing advance application does not equal the eligible advance.');
                }
                return ['posted_journals' => [], 'already_reconciled' => true];
            }

            $journalNo = 'JRN-IPAPPLY-'.$account->id;
            if (JournalEntry::query()->where('journal_no', $journalNo)->exists()) {
                $fail('The advance journal number is already allocated to another entry.');
            }
            $gl = GeneralLedgerAccount::query()
                ->whereIn('account_code', ['2100', '1200'])->get()->keyBy('account_code');
            foreach (['2100', '1200'] as $code) {
                $entry = $gl->get($code);
                if (!$entry || !$entry->is_active || !$entry->allow_posting) {
                    $fail('GL account '.$code.' is unavailable for posting.');
                }
            }

            $journal = JournalEntry::create([
                'journal_no' => $journalNo,
                'journal_date' => now()->toDateString(),
                'source_type' => 'ip_billing_account',
                'source_id' => $account->id,
                'source_event' => 'advance_applied',
                'reference_no' => $account->final_bill_no,
                'narration' => 'Controlled MHIS advance-only application to finalized bill',
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);
            foreach ([
                ['2100', $amount, '0.00', 'Patient advance liability applied'],
                ['1200', '0.00', $amount, 'Patient receivable reduced'],
            ] as $index => [$code, $debit, $credit, $description]) {
                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'line_no' => $index + 1,
                    'general_ledger_account_id' => $gl[$code]->id,
                    'department_id' => null,
                    'debit' => $debit,
                    'credit' => $credit,
                    'reference_no' => $account->final_bill_no,
                    'description' => $description,
                ]);
            }
            $this->postingService->post($journal);
            return ['posted_journals' => [$journalNo], 'already_reconciled' => false];
        }, 3);
    }

    private static function paise(string $value): int
    {
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw ValidationException::withMessages([
                'reconciliation' => 'Invalid monetary amount in financial records.',
            ]);
        }
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
