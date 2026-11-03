<?php
namespace Tests\Unit;
use App\Models\JournalEntry;
use App\Services\Tally\LegacyMhisTallyExportPolicy as Policy;
use PHPUnit\Framework\TestCase;
class LegacyMhisTallyExportPolicyTest extends TestCase
{
    public function test_legacy_journal_is_blocked_even_when_posted(): void
    {
        $journal = new JournalEntry(); $journal->source_type = 'mhis_legacy_settlement'; $journal->status = 'posted';
        $this->assertFalse(Policy::ineligible()['eligible']);
        $this->expectException(\RuntimeException::class);
        Policy::assertJournalAllowed($journal);
    }
    public function test_other_journals_remain_permitted_by_this_policy(): void
    {
        $journal = new JournalEntry(); $journal->source_type = 'ip_billing_mhis_receipt';
        $this->assertFalse(Policy::isBlockedSource($journal->source_type));
        Policy::assertJournalAllowed($journal); $this->assertTrue(true);
    }
    public function test_other_sources_are_not_embargoed(): void
    {
        foreach (['invoice', 'mhis_settlement_adjustment', null] as $source) {
            $this->assertFalse(Policy::isBlockedSource($source));
        }
    }
    public function test_all_reviewed_export_services_include_gate(): void
    {
        $base = dirname(__DIR__, 2).'/app/Services/Tally/';
        foreach (['TallyJournalExportService','TallyExportService','TallyJournalRecoveryExecutionService','TallyJournalTransmissionService','TallyVoucherXmlRouter','TallyVoucherClassificationService'] as $name) {
            $this->assertStringContainsString('LegacyMhisTallyExportPolicy::', file_get_contents($base.$name.'.php'));
        }
    }
}
