<?php

namespace App\Services\Accounting;

use App\Models\GeneralLedgerAccount;
use App\Models\JournalEntry;
use App\Models\MhisLegacySettlement;
use App\Models\MhisOpeningReceivable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Prepares a DRAFT GL journal for an independently verified legacy settlement.
 * Does not post, export, or transmit anything to Tally.
 * Reconciliation is evidenced by a separate signed-off opening reconciliation row.
 */
final class MhisLegacySettlementDraftJournalService
{
    public function prepare(int $settlementId, int $actorId): JournalEntry
    {
        return DB::transaction(function () use ($settlementId, $actorId) {
            // Lock opening first: the same lock order as settlement verification.
            $openingId = MhisLegacySettlement::query()->whereKey($settlementId)
                ->value('mhis_opening_receivable_id');
            if (! $openingId) {
                throw ValidationException::withMessages(['settlement' => 'Settlement not found.']);
            }
            $opening = MhisOpeningReceivable::query()->whereKey($openingId)
                ->lockForUpdate()->firstOrFail();
            $settlement = MhisLegacySettlement::query()->whereKey($settlementId)
                ->lockForUpdate()->firstOrFail();
            if ((int) $settlement->mhis_opening_receivable_id !== (int) $opening->id) {
                throw ValidationException::withMessages(['settlement' => 'Opening linkage changed. Retry.']);
            }
            if ($opening->status !== 'approved' || $settlement->status !== 'verified'
                || $settlement->verified_by === null || $settlement->verified_at === null) {
                throw ValidationException::withMessages(['settlement' => 'Approved opening and independently verified settlement required.']);
            }
            if ((int) $settlement->created_by === (int) $settlement->verified_by
                || (int) $actorId === (int) $settlement->created_by
                || (int) $actorId === (int) $settlement->verified_by) {
                throw ValidationException::withMessages(['actor' => 'Independent journal preparer required.']);
            }
            if (! \App\Models\User::whereKey($actorId)->exists()) {
                throw ValidationException::withMessages(['actor' => 'Unknown preparer.']);
            }
            // Reconciliation is not satisfied by opening.status=approved or a checkbox.
            $reconciliation = DB::table('mhis_opening_reconciliations')
                ->where('mhis_opening_receivable_id', $opening->id)
                ->where('status', 'verified')->lockForUpdate()->first();
            if (! $reconciliation || ! $reconciliation->gl_1210_carry_forward_confirmed
                || ! $reconciliation->trial_balance_reference || ! $reconciliation->verified_by) {
                throw ValidationException::withMessages(['reconciliation' => 'Verified closing trial balance and GL 1210 carry-forward evidence required.']);
            }
            if ($settlement->journal_entry_id) {
                $existing = JournalEntry::find($settlement->journal_entry_id);
                if ($existing && $existing->source_type === 'mhis_legacy_settlement'
                    && (int) $existing->source_id === (int) $settlementId
                    && $existing->source_event === 'draft_settlement') {
                    return $existing; // Idempotent, never another journal.
                }
                throw ValidationException::withMessages(['journal' => 'Settlement already linked to a different journal.']);
            }
            $existing = JournalEntry::query()->where('source_type', 'mhis_legacy_settlement')
                ->where('source_id', $settlementId)->where('source_event', 'draft_settlement')->first();
            if ($existing) {
                $settlement->journal_entry_id = $existing->id;
                $settlement->save();
                return $existing;
            }
            $plan = MhisLegacySettlementJournalBlueprint::build(
                $settlement->status,
                (string) $settlement->net_amount,
                (string) $settlement->tds_amount,
                (string) $settlement->approved_deduction,
                (string) $settlement->gross_settled_amount,
                true, true
            );
            $codes = array_map('strval', array_column($plan['lines'], 'account_code'));
            $accounts = GeneralLedgerAccount::query()->whereIn('account_code', $codes)
                ->get()->keyBy(fn ($a) => (string) $a->account_code);
            foreach ($codes as $code) {
                $a = $accounts->get($code);
                if (! $a || ! $a->is_active || ! $a->allow_posting) {
                    throw ValidationException::withMessages(['account' => "GL {$code} is missing or not postable."]);
                }
            }
            $journal = JournalEntry::create([
                'journal_no' => 'JRN-MHISLEG-'.$settlementId,
                'journal_date' => $settlement->settlement_date,
                'source_type' => 'mhis_legacy_settlement',
                'source_id' => $settlementId,
                'source_event' => 'draft_settlement',
                'reference_no' => $settlement->settlement_reference,
                'narration' => 'Legacy MHIS settlement; opening TB '.$reconciliation->trial_balance_reference,
                'status' => 'draft',
                'created_by' => $actorId,
            ]);
            foreach ($plan['lines'] as $i => $line) {
                $journal->lines()->create([
                    'line_no' => $i + 1,
                    'general_ledger_account_id' => $accounts->get((string) $line['account_code'])->id,
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'reference_no' => $settlement->settlement_reference,
                    'description' => 'Legacy MHIS settlement '.$settlement->id,
                ]);
            }
            $settlement->journal_entry_id = $journal->id;
            $settlement->save();
            return $journal->load('lines');
        }, 3);
    }
}
