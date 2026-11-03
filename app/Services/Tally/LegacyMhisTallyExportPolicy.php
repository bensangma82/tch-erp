<?php
namespace App\Services\Tally;
use App\Models\JournalEntry;
use RuntimeException;
final class LegacyMhisTallyExportPolicy
{
    public static function isBlockedSource(?string $sourceType): bool
    {
        return $sourceType === 'mhis_legacy_settlement';
    }
    public static function ineligible(): array
    {
        return ['eligible' => false, 'errors' => ['Legacy MHIS Tally export remains prohibited pending separate approval.'], 'export_ready' => false, 'transmitted' => false];
    }
    public static function assertJournalAllowed(JournalEntry $journal): void
    {
        if (self::isBlockedSource($journal->source_type)) {
            throw new RuntimeException(self::ineligible()['errors'][0]);
        }
    }
}
