<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use App\Models\MhisLegacySettlement;
use App\Models\MhisOpeningReceivable;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Opt-in legacy posting service. No controller or route invokes this service.
 * A posted journal may become Tally-export eligible in the existing application;
 * therefore activation requires an explicit downstream export exclusion audit.
 */
final class MhisLegacySettlementControlledPostingService
{
    public function post(int $settlementId, int $actorId): JournalEntry
    {
        if (! config('mhis_legacy_posting.enabled', false)) {
            throw ValidationException::withMessages(['posting' => 'Legacy MHIS GL posting is disabled pending Tally export-gate acceptance.']);
        }
        return DB::transaction(function () use ($settlementId, $actorId) {
            // Lock in the same order used by existing verification and draft preparation.
            $openingId = MhisLegacySettlement::query()->whereKey($settlementId)->value('mhis_opening_receivable_id');
            if (! $openingId) {
                throw ValidationException::withMessages(['settlement' => 'Settlement not found.']);
            }
            $opening = MhisOpeningReceivable::query()->whereKey($openingId)->lockForUpdate()->firstOrFail();
            $settlement = MhisLegacySettlement::query()->whereKey($settlementId)->lockForUpdate()->firstOrFail();
            if ((int) $settlement->mhis_opening_receivable_id !== (int) $opening->id
                || $opening->status !== 'approved' || $settlement->status !== 'verified'
                || ! $settlement->verified_at || ! $settlement->verified_by) {
                throw ValidationException::withMessages(['settlement' => 'Verified settlement linked to approved opening balance required.']);
            }
            $actor = User::query()->whereKey($actorId)->firstOrFail();
            if (! $actor->hasPermission('finance.mhis-legacy-settlement.post')
                || ! in_array($actor->role, ['finance_manager', 'admin', 'super_admin'], true)) {
                throw ValidationException::withMessages(['actor' => 'Finance posting permission required.']);
            }
            $recon = DB::table('mhis_opening_reconciliations')
                ->where('mhis_opening_receivable_id', $openingId)->lockForUpdate()->first();
            if (! $recon || $recon->status !== 'verified' || ! $recon->gl_1210_carry_forward_confirmed
                || ! $recon->trial_balance_reference || ! $recon->supporting_evidence_reference
                || ! $recon->verified_at || ! $recon->verified_by
                || (int) $recon->prepared_by === (int) $recon->verified_by) {
                throw ValidationException::withMessages(['reconciliation' => 'Independently verified GL 1210 carry-forward reconciliation required.']);
            }
            $journal = JournalEntry::query()->whereKey($settlement->journal_entry_id)->lockForUpdate()->first();
            if (! $journal || $journal->source_type !== 'mhis_legacy_settlement'
                || (int) $journal->source_id !== (int) $settlement->id
                || $journal->source_event !== 'draft_settlement') {
                throw ValidationException::withMessages(['journal' => 'Linked legacy settlement draft journal required.']);
            }
            if ((int) $actorId === (int) $settlement->created_by
                || (int) $actorId === (int) $settlement->verified_by
                || (int) $actorId === (int) $journal->created_by
                || (int) $actorId === (int) $recon->prepared_by
                || (int) $actorId === (int) $recon->verified_by) {
                throw ValidationException::withMessages(['actor' => 'Posting requires a separate authorized person.']);
            }
            if ($journal->status === 'posted') {
                return $journal; // Idempotent: never post a second time.
            }
            if ($journal->status !== 'draft') {
                throw ValidationException::withMessages(['journal' => 'Journal is not draft or posted.']);
            }
            if (DB::table('tally_exports')->where('journal_entry_id', $journal->id)->exists()) {
                throw ValidationException::withMessages(['journal' => 'Tally export already exists for this journal.']);
            }
            // Protect against mutation of draft lines between approval and posting.
            $expected = MhisLegacySettlementJournalBlueprint::build(
                $settlement->status, (string) $settlement->net_amount,
                (string) $settlement->tds_amount, (string) $settlement->approved_deduction,
                (string) $settlement->gross_settled_amount, true, true
            );
            $lines = $journal->lines()->lockForUpdate()->get();
            if ($lines->count() !== count($expected['lines'])) {
                throw ValidationException::withMessages(['journal' => 'Draft journal line count differs from verified settlement.']);
            }
            foreach ($lines->values() as $i => $line) {
                $e = $expected['lines'][$i];
                $accountCode = (string) $line->generalLedgerAccount()->value('account_code');
                if ($accountCode !== (string) $e['account_code']
                    || (int) round((float) $line->debit * 100) !== (int) round((float) $e['debit'] * 100)
                    || (int) round((float) $line->credit * 100) !== (int) round((float) $e['credit'] * 100)) {
                    throw ValidationException::withMessages(['journal' => 'Draft journal lines no longer match verified settlement.']);
                }
            }
            // The existing posting service enforces balancing and valid GL accounts.
            // It resolves the actor from auth() or journal.posted_by.
            if (auth()->id() !== null && (int) auth()->id() !== $actorId) {
                throw ValidationException::withMessages(['actor' => 'Authenticated user differs from authorized poster.']);
            }
            $journal->posted_by = $actorId;
            $journal->save();
            return app(JournalPostingService::class)->post($journal);
        }, 3);
    }
}
