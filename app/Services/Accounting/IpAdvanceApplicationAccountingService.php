<?php

namespace App\Services\Accounting;

use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingAccount;
use App\Models\PharmacySale;
use App\Models\IpBillingAdvance;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IpAdvanceApplicationAccountingService
{
    public function __construct(
        private JournalPostingService $postingService
    ) {}

    public function apply(
        IpBillingAccount $account
    ): ?JournalEntry {
        return DB::transaction(function () use ($account) {
            $account = IpBillingAccount::query()
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();

            $sourceType = 'ip_billing_account';
            $sourceEvent = 'advance_applied';

            if (
                $account->status !== 'finalized'
                || ! $account->final_bill_no
            ) {
                throw new RuntimeException(
                    'The IPD bill must be finalized.'
                );
            }

            if (! auth()->check()) {
                throw new RuntimeException(
                    'An authenticated user is required.'
                );
            }

            // The revenue journal must already be posted.
            $revenueJournal = JournalEntry::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $account->id)
                ->where('source_event', 'finalized')
                ->where('status', 'posted')
                ->first();

            if (! $revenueJournal) {
                throw new RuntimeException(
                    'The IPD revenue journal is not posted.'
                );
            }

            // MHIS with a patient-paid portion is supported only when there is
            // exactly one formally approved claim and no other settlements.
            $account->load([
                'advances', 'payments', 'refunds', 'mhisClaims',
                'mhisReceipts', 'mhisAdjustments',
                'staffMedicalBenefitTransactions', 'charityAdjustments',
            ]);

            if ($account->mhis_category === 'free' && $account->advances->isNotEmpty()) {
                throw new RuntimeException('MHIS Free cannot have patient advances.');
            }

            if (
                $account->mhisReceipts->isNotEmpty()
                || $account->mhisAdjustments->isNotEmpty()
                || $account->staffMedicalBenefitTransactions->isNotEmpty()
                || $account->charityAdjustments->isNotEmpty()
                || $account->payments->isNotEmpty()
                || $account->refunds->isNotEmpty()
            ) {
                throw new RuntimeException('This bill requires extended accounting reconciliation.');
            }

            if ($account->mhisClaims->isNotEmpty() && (
                $account->mhisClaims->count() !== 1
                || $account->mhisClaims->first()->status !== 'approved'
                || ! $account->mhisClaims->first()->approval_date
            )) {
                throw new RuntimeException('MHIS advance application requires exactly one formally approved claim.');
            }

            if ($account->advances->contains(fn ($advance) => $advance->status !== 'active')) {
                throw new RuntimeException('Inactive patient advances require Finance reconciliation.');
            }

            $advances = $account->advances;
            if ($advances->isEmpty()) {
                return null;
            }

            $totalAdvancePaise = 0;
            foreach ($advances as $advance) {
                $totalAdvancePaise += $this->validatedAdvancePaise($account, $advance);
            }

            $billPaise = $this->toPaise(
                (string) $account->net_amount
            );

            if ($billPaise <= 0) {
                return null;
            }

            $applicationPaise = min(
                $totalAdvancePaise,
                $billPaise
            );

            if ($applicationPaise <= 0) {
                return null;
            }

            // Never silently accept an incorrect earlier application journal.
            $existingJournals = JournalEntry::query()
                ->where('source_type', $sourceType)
                ->where('source_id', (string) $account->id)
                ->where('source_event', $sourceEvent)
                ->with('lines.generalLedgerAccount')
                ->lockForUpdate()->get();

            if ($existingJournals->count() > 1) {
                throw new RuntimeException('Duplicate advance application journals require Finance review.');
            }
            if ($existingJournals->isNotEmpty()) {
                $existing = $existingJournals->first();
                if ($existing->status !== 'posted' || $existing->lines->count() !== 2) {
                    throw new RuntimeException('Existing advance application journal is not valid.');
                }
                $debit = $credit = 0;
                foreach ($existing->lines as $line) {
                    $code = (string) ($line->generalLedgerAccount?->account_code ?? '');
                    $d = $this->toPaise((string) $line->debit);
                    $c = $this->toPaise((string) $line->credit);
                    if ($code === '2100' && $c === 0) {
                        $debit += $d;
                    } elseif ($code === '1200' && $d === 0) {
                        $credit += $c;
                    } else {
                        throw new RuntimeException('Existing advance application contains unexpected GL lines.');
                    }
                }
                if ($debit !== $applicationPaise || $credit !== $applicationPaise) {
                    throw new RuntimeException('Existing advance application does not reconcile.');
                }
                return $existing;
            }

            $accounts = GeneralLedgerAccount::query()
                ->whereIn('account_code', ['2100', '1200'])
                ->get()
                ->keyBy('account_code');

            foreach (['2100', '1200'] as $code) {
                $gl = $accounts->get($code);

                if (
                    ! $gl
                    || ! $gl->is_active
                    || ! $gl->allow_posting
                ) {
                    throw new RuntimeException(
                        'GL account '.$code.' is unavailable.'
                    );
                }
            }

            $amount = number_format(
                $applicationPaise / 100,
                2,
                '.',
                ''
            );

            $journal = JournalEntry::create([
                'journal_no' => 'JRN-IPAPPLY-'.$account->id,
                'journal_date' => now()->toDateString(),
                'source_type' => $sourceType,
                'source_id' => $account->id,
                'source_event' => $sourceEvent,
                'reference_no' => $account->final_bill_no,
                'narration' => 'IPD advance applied to final bill',
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => 1,
                'general_ledger_account_id' => $accounts['2100']->id,
                'department_id' => null,
                'debit' => $amount,
                'credit' => '0.00',
                'reference_no' => $account->final_bill_no,
                'description' => 'Patient advance liability applied',
            ]);

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => 2,
                'general_ledger_account_id' => $accounts['1200']->id,
                'department_id' => null,
                'debit' => '0.00',
                'credit' => $amount,
                'reference_no' => $account->final_bill_no,
                'description' => 'Reduction of patient receivable',
            ]);

            return $this->postingService->post($journal);
        });
    }

    /** Validates the source and the exact posted receipt GL before application. */
    public function validatedAdvancePaise(
        IpBillingAccount $account,
        IpBillingAdvance $advance
    ): int {
        if (
            $advance->status !== 'active'
            || (int) $advance->ip_billing_account_id !== (int) $account->id
            || (int) $advance->admission_id !== (int) $account->admission_id
            || (int) $advance->patient_id !== (int) $account->patient_id
            || $account->mhis_category === 'free'
        ) {
            throw new RuntimeException('Patient advance requires Finance review.');
        }

        if ($advance->source_type === 'pharmacy_sale') {
            $sale = PharmacySale::query()
                ->with('returns')
                ->whereKey($advance->source_id)
                ->where('admission_id', $account->admission_id)
                ->where('patient_id', $account->patient_id)
                ->whereIn('status', ['completed', 'credit'])
                ->first();
            if (! $sale || $sale->returns->isNotEmpty()) {
                throw new RuntimeException('Pharmacy sale source requires Finance reconciliation.');
            }
        } elseif ($advance->source_type === 'pharmacy_admission') {
            if ((int) $advance->source_id !== (int) $account->admission_id) {
                throw new RuntimeException('Pharmacy admission source does not match this bill.');
            }
            $sales = PharmacySale::query()
                ->with('returns')
                ->where('admission_id', $account->admission_id)
                ->where('patient_id', $account->patient_id)
                ->whereIn('status', ['completed', 'credit'])
                ->get();
            if ($sales->isEmpty() || $sales->contains(fn ($sale) => $sale->returns->isNotEmpty())) {
                throw new RuntimeException('Pharmacy admission collection requires Finance reconciliation.');
            }
        } elseif ($advance->source_type !== null || $advance->source_id !== null) {
            throw new RuntimeException('Unsupported patient advance source.');
        }

        $amount = $this->toPaise((string) $advance->amount);
        $cashGl = [
            'cash' => '1100',
            'upi' => '1110',
            'card' => '1120',
        ][strtolower(trim((string) $advance->payment_mode))] ?? null;
        if ($amount <= 0 || ! $cashGl) {
            throw new RuntimeException('Invalid patient advance amount or payment mode.');
        }

        $receipts = JournalEntry::query()
            ->where('source_type', 'ip_billing_advance')
            ->where('source_id', (string) $advance->id)
            ->where('source_event', 'received')
            ->with('lines.generalLedgerAccount')
            ->get();
        if ($receipts->count() !== 1 || $receipts->first()->status !== 'posted') {
            throw new RuntimeException('Exactly one posted advance receipt journal is required.');
        }
        $receipt = $receipts->first();
        if ($receipt->lines->count() !== 2 || $receipt->reference_no !== $advance->receipt_no) {
            throw new RuntimeException('Advance receipt reference or GL lines are inconsistent.');
        }
        $cashDebit = $liabilityCredit = 0;
        foreach ($receipt->lines as $line) {
            $gl = (string) ($line->generalLedgerAccount?->account_code ?? '');
            $debit = $this->toPaise((string) $line->debit);
            $credit = $this->toPaise((string) $line->credit);
            if ($gl === $cashGl && $credit === 0) {
                $cashDebit += $debit;
            } elseif ($gl === '2100' && $debit === 0) {
                $liabilityCredit += $credit;
            } else {
                throw new RuntimeException('Unexpected GL line in advance receipt journal.');
            }
        }
        if ($cashDebit !== $amount || $liabilityCredit !== $amount) {
            throw new RuntimeException('Advance receipt journal does not match the collection.');
        }
        return $amount;
    }

    private function toPaise(string $amount): int
    {
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
            throw new RuntimeException(
                'Invalid accounting amount: '.$amount
            );
        }

        [$whole, $fraction] = array_pad(
            explode('.', $amount, 2),
            2,
            '0'
        );

        return ((int) $whole * 100)
            + (int) str_pad($fraction, 2, '0');
    }
}
