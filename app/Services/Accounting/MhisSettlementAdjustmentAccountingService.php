<?php

namespace App\Services\Accounting;

use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingMhisClaim;
use App\Models\IpBillingMhisReceipt;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\MhisSettlementAdjustment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MhisSettlementAdjustmentAccountingService
{
    public function __construct(
        private JournalPostingService $postingService
    ) {}

    private function requirePermission(string $permission): int
    {
        $user = auth()->user();

        if (! $user || ! $user->hasPermission($permission)) {
            throw new RuntimeException(
                'Not authorized: '.$permission
            );
        }

        return (int) $user->id;
    }

    private function paise($value): int
    {
        $value = (string) $value;

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new RuntimeException('Invalid monetary amount.');
        }

        [$whole, $fraction] = array_pad(
            explode('.', $value, 2),
            2,
            '0'
        );

        return ((int) $whole * 100)
            + (int) str_pad($fraction, 2, '0');
    }

    private function money(int $paise): string
    {
        return number_format($paise / 100, 2, '.', '');
    }

    public function submit(
        MhisSettlementAdjustment $adjustment
    ): MhisSettlementAdjustment {
        $userId = $this->requirePermission(
            'finance.mhis-adjustment.submit'
        );

        return DB::transaction(function () use ($adjustment, $userId) {
            $a = MhisSettlementAdjustment::query()
                ->whereKey($adjustment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $a->status !== 'draft'
                || (int) $a->created_by !== $userId
            ) {
                throw new RuntimeException(
                    'Only the creator can submit a draft adjustment.'
                );
            }

            $tds = $this->paise($a->tds_amount);
            $deduction = $this->paise(
                $a->accepted_deduction_amount
            );

            if ($tds + $deduction <= 0) {
                throw new RuntimeException(
                    'Adjustment must be positive.'
                );
            }

            if (
                trim((string) $a->supporting_evidence) === ''
                || trim((string) $a->settlement_reference) === ''
                || ($deduction > 0
                    && trim((string) $a->reason) === '')
            ) {
                throw new RuntimeException(
                    'Settlement evidence, reference and deduction reason are required.'
                );
            }

            $a->status = 'submitted';
            $a->save();

            return $a;
        });
    }

    public function approve(
        MhisSettlementAdjustment $adjustment
    ): MhisSettlementAdjustment {
        $userId = $this->requirePermission(
            'finance.mhis-adjustment.approve'
        );

        return DB::transaction(function () use ($adjustment, $userId) {
            $a = MhisSettlementAdjustment::query()
                ->whereKey($adjustment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($a->status !== 'submitted') {
                throw new RuntimeException(
                    'Only submitted adjustments can be approved.'
                );
            }

            if ((int) $a->created_by === $userId) {
                throw new RuntimeException(
                    'Maker-checker violation: creator cannot approve.'
                );
            }

            $a->approved_by = $userId;
            $a->approved_at = now();
            $a->status = 'approved';
            $a->save();

            return $a;
        });
    }

    public function post(
        MhisSettlementAdjustment $adjustment
    ): JournalEntry {
        $userId = $this->requirePermission(
            'finance.mhis-adjustment.post'
        );

        return DB::transaction(function () use ($adjustment, $userId) {
            // Match the existing MHIS receipt lock order:
            // billing account -> claim -> adjustment.
            $claimId = $adjustment->ip_billing_mhis_claim_id;

            $claimSource = IpBillingMhisClaim::query()
                ->findOrFail($claimId);

            $account = $claimSource->billingAccount()
                ->lockForUpdate()
                ->firstOrFail();

            $claim = IpBillingMhisClaim::query()
                ->whereKey($claimId)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $claim->ip_billing_account_id !== (int) $account->id) {
                throw new RuntimeException(
                    'MHIS claim and billing account mismatch.'
                );
            }

            $a = MhisSettlementAdjustment::query()
                ->whereKey($adjustment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                (int) $a->ip_billing_mhis_claim_id !== (int) $claim->id
                || $a->status !== 'approved'
                || ! $a->approved_by
                || ! $a->approved_at
                || (int) $a->approved_by === (int) $a->created_by
            ) {
                throw new RuntimeException(
                    'Adjustment is not validly approved.'
                );
            }

            if (
                ! in_array($claim->status, ['approved', 'settled'], true)
                || ! $claim->approval_date
            ) {
                throw new RuntimeException(
                    'Formal MHIS claim approval is required.'
                );
            }

            $reference = trim((string) $a->settlement_reference);
            $evidence = trim((string) $a->supporting_evidence);

            if (
                $reference === ''
                || $evidence === ''
                || (
                    $this->paise($a->accepted_deduction_amount) > 0
                    && trim((string) $a->reason) === ''
                )
            ) {
                throw new RuntimeException(
                    'Settlement evidence is incomplete.'
                );
            }

            $tds = $this->paise($a->tds_amount);
            $deduction = $this->paise(
                $a->accepted_deduction_amount
            );
            $total = $tds + $deduction;

            if ($total <= 0) {
                throw new RuntimeException(
                    'Invalid settlement adjustment amount.'
                );
            }

            $existing = JournalEntry::query()
                ->where('source_type', 'mhis_settlement_adjustment')
                ->where('source_id', (string) $a->id)
                ->where('source_event', 'posted')
                ->first();

            if ($existing) {
                throw new RuntimeException(
                    'Adjustment already has an accounting journal.'
                );
            }

            $accounts = GeneralLedgerAccount::query()
                ->whereIn('account_code', ['1210', '1220', '5301'])
                ->get()
                ->keyBy('account_code');

            foreach (['1210', '1220', '5301'] as $code) {
                $gl = $accounts->get($code);

                if (
                    ! $gl
                    || ! $gl->is_active
                    || ! $gl->allow_posting
                ) {
                    throw new RuntimeException(
                        'GL account '.$code.' unavailable.'
                    );
                }
            }

            $recognition = JournalEntry::query()
                ->where('source_type', 'ip_billing_mhis_claim')
                ->where('source_id', (string) $claim->id)
                ->where('source_event', 'approved')
                ->where('status', 'posted')
                ->with('lines')
                ->first();

            if (! $recognition) {
                throw new RuntimeException(
                    'Posted MHIS recognition journal is missing.'
                );
            }

            $approved = $this->paise($claim->approved_amount);

            // Reject adjustments against fully settled claims.
            $postedAdjustments = app(
                MhisSettlementBalanceService::class
            )->postedAdjustmentsPaise((int) $claim->id);

            $cashReceived = IpBillingMhisReceipt::query()
                ->where('ip_billing_mhis_claim_id', $claim->id)
                ->where('status', 'active')
                ->sum('amount');

            if (
                (int) round((float) $cashReceived * 100)
                + $postedAdjustments >= $approved
            ) {
                throw new RuntimeException(
                    'MHIS claim is already fully settled.'
                );
            }

            $recognized = 0;

            foreach ($recognition->lines as $line) {
                if (
                    (int) $line->general_ledger_account_id
                    === (int) $accounts['1210']->id
                ) {
                    $recognized += $this->paise($line->debit)
                        - $this->paise($line->credit);
                }
            }

            if ($approved <= 0 || $recognized !== $approved) {
                throw new RuntimeException(
                    'MHIS claim recognition does not reconcile.'
                );
            }

            // All existing MHIS receipt records must have posted journals.
            $receipts = DB::table('ip_billing_mhis_receipts')
                ->where('ip_billing_mhis_claim_id', $claim->id)
                ->get();

            $received = 0;

            foreach ($receipts as $receipt) {
                if ($receipt->status !== 'active') {
                    throw new RuntimeException(
                        'Cancelled MHIS receipts require reconciliation.'
                    );
                }

                $journal = JournalEntry::query()
                    ->where('source_type', 'ip_billing_mhis_receipt')
                    ->where('source_id', (string) $receipt->id)
                    ->where('source_event', 'received')
                    ->where('status', 'posted')
                    ->with('lines')
                    ->first();

                if (! $journal) {
                    throw new RuntimeException(
                        'Unposted MHIS receipt found.'
                    );
                }

                $amount = $this->paise($receipt->amount);
                $credit1210 = 0;

                foreach ($journal->lines as $line) {
                    if (
                        (int) $line->general_ledger_account_id
                        === (int) $accounts['1210']->id
                    ) {
                        $credit1210 +=
                            $this->paise($line->credit)
                            - $this->paise($line->debit);
                    }
                }

                if ($credit1210 !== $amount) {
                    throw new RuntimeException(
                        'MHIS receipt GL does not reconcile.'
                    );
                }

                $received += $amount;
            }

            // Include previously posted settlement adjustments.
            $priorAdjustments = MhisSettlementAdjustment::query()
                ->where('ip_billing_mhis_claim_id', $claim->id)
                ->where('status', 'posted')
                ->where('id', '!=', $a->id)
                ->get();

            $adjusted = 0;

            foreach ($priorAdjustments as $prior) {
                $priorJournal = JournalEntry::query()
                    ->where('source_type', 'mhis_settlement_adjustment')
                    ->where('source_id', (string) $prior->id)
                    ->where('source_event', 'posted')
                    ->where('status', 'posted')
                    ->with('lines')
                    ->first();

                if (! $priorJournal) {
                    throw new RuntimeException(
                        'Prior adjustment journal is missing.'
                    );
                }

                $priorAmount = $this->paise($prior->tds_amount)
                    + $this->paise($prior->accepted_deduction_amount);

                $credit1210 = 0;

                foreach ($priorJournal->lines as $line) {
                    if (
                        (int) $line->general_ledger_account_id
                        === (int) $accounts['1210']->id
                    ) {
                        $credit1210 +=
                            $this->paise($line->credit)
                            - $this->paise($line->debit);
                    }
                }

                if ($credit1210 !== $priorAmount) {
                    throw new RuntimeException(
                        'Prior adjustment does not reconcile.'
                    );
                }

                $adjusted += $priorAmount;
            }

            $outstanding = $approved - $received - $adjusted;

            if ($outstanding <= 0 || $total > $outstanding) {
                throw new RuntimeException(
                    'Adjustment exceeds outstanding MHIS receivable.'
                );
            }

            $journal = JournalEntry::create([
                'journal_no' => 'JRN-MHISADJ-'.$a->id,
                'journal_date' => $a->adjustment_date,
                'source_type' => 'mhis_settlement_adjustment',
                'source_id' => (string) $a->id,
                'source_event' => 'posted',
                'reference_no' => $reference,
                'narration' => 'MHIS settlement adjustment '
                    .$a->adjustment_reference,
                'status' => 'draft',
                'created_by' => $userId,
            ]);

            $lineNo = 1;

            foreach ([
                ['1220', $tds, 'MHIS TDS recoverable'],
                ['5301', $deduction, 'Approved MHIS deduction'],
            ] as [$code, $amount, $description]) {
                if ($amount <= 0) {
                    continue;
                }

                JournalEntryLine::create([
                    'journal_entry_id' => $journal->id,
                    'line_no' => $lineNo++,
                    'general_ledger_account_id' => $accounts[$code]->id,
                    'department_id' => null,
                    'debit' => $this->money($amount),
                    'credit' => '0.00',
                    'reference_no' => $reference,
                    'description' => $description,
                ]);
            }

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => $lineNo,
                'general_ledger_account_id' => $accounts['1210']->id,
                'department_id' => null,
                'debit' => '0.00',
                'credit' => $this->money($total),
                'reference_no' => $reference,
                'description' => 'MHIS receivable settlement adjustment',
            ]);

            $posted = $this->postingService->post($journal);

            $a->status = 'posted';
            $a->posted_at = now();
            $a->save();

            // Keep claim status consistent with posted GL settlements.
            $fullySettled = ($received + $adjusted + $total)
                === $approved;

            $claim->status = $fullySettled
                ? 'settled'
                : 'approved';

            // This field continues to represent cash receipts only.
            $claim->settlement_amount = $this->money($received);

            $claim->settlement_date = $fullySettled
                ? $a->adjustment_date
                : null;

            $claim->updated_by = $userId;
            $claim->save();

            return $posted;
        });
    }
}
