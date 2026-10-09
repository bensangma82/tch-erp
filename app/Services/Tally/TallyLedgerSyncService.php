<?php

namespace App\Services\Tally;

use App\Models\TallyLedgerMaster;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TallyLedgerSyncService
{
    public function __construct(
        private readonly TallyQueryService $tallyQueryService
    ) {}

    public function sync(): array
    {
        $company = trim((string) config('tally.company'));

        if ($company === '') {
            throw new RuntimeException(
                'Tally company is not configured.'
            );
        }

        // Read-only requests to TallyPrime.
        $ledgers = $this->tallyQueryService->ledgers();
        $groups = $this->tallyQueryService->groups();

        if (empty($ledgers)) {
            throw new RuntimeException(
                'Tally returned no ledgers. Synchronization cancelled.'
            );
        }

        $groupNames = collect($groups)
            ->pluck('name')
            ->filter()
            ->map(fn ($name) => mb_strtolower(trim((string) $name)))
            ->all();

        $seen = [];
        $prepared = [];

        foreach ($ledgers as $ledger) {
            $name = trim((string) ($ledger['name'] ?? ''));
            $parent = trim((string) ($ledger['parent'] ?? ''));

            if ($name === '' || mb_strlen($name) > 255) {
                throw new RuntimeException(
                    'Tally returned an invalid ledger name.'
                );
            }

            $key = mb_strtolower($name);

            if (isset($seen[$key])) {
                throw new RuntimeException(
                    "Duplicate Tally ledger name: {$name}"
                );
            }

            $seen[$key] = true;

           if (
    $parent !== ''
    && mb_strtolower($parent) !== 'primary'
    && ! in_array(
        mb_strtolower($parent),
        $groupNames,
        true
    )
) {
                throw new RuntimeException(
                    "Unknown Tally parent group for ledger: {$name}"
                );
            }

            $prepared[] = [
                'ledger_name' => $name,
                'parent_group' => $parent !== '' ? $parent : null,
                'closing_balance' => $ledger['closing_balance'] ?? null,
            ];
        }

        return DB::transaction(function () use ($company, $prepared) {
            $summary = [
                'received' => count($prepared),
                'created' => 0,
                'updated' => 0,
            ];

            foreach ($prepared as $ledger) {
                $record = TallyLedgerMaster::query()
                    ->where('tally_company', $company)
                    ->where('ledger_name', $ledger['ledger_name'])
                    ->lockForUpdate()
                    ->first();

                if ($record === null) {
                    $record = new TallyLedgerMaster([
                        'tally_company' => $company,
                        'ledger_name' => $ledger['ledger_name'],
                    ]);

                    $summary['created']++;
                } else {
                    $summary['updated']++;
                }

                $record->parent_group = $ledger['parent_group'];
                $record->closing_balance = $ledger['closing_balance'];
                $record->last_synced_at = now();
                $record->save();
            }

            return $summary;
        });
    }
}
