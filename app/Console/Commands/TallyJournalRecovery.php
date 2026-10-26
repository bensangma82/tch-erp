<?php

namespace App\Console\Commands;

use App\Models\TallyExport;
use App\Services\Tally\TallyJournalRecoveryService;
use App\Services\Tally\TallyJournalRecoveryTransmissionService;
use Illuminate\Console\Command;
use Throwable;

class TallyJournalRecovery extends Command
{
    protected $signature = 'tally:journal-recovery {exportId : Existing export ID} {--user= : Authorizing user ID} {--approve : Record approval only} {--send : Consume a prior approval and transmit once} {--reason= : Reason for the rejection} {--evidence= : Tally Day Book verification including ERP journal number}';
    protected $description = 'Controlled manual recovery of a rejected Tally GL journal (disabled by default)';

    public function handle(): int
    {
        if (! config('tally.journal_export_enabled') || ! config('tally.journal_recovery_enabled', false)) {
            $this->error('Recovery disabled by configuration.');
            return self::FAILURE;
        }
        $company = (string) config('tally.company');
        if (! str_contains(strtolower($company), '-test')) {
            $this->error('Recovery CLI restricted to explicitly named -Test Tally company.');
            return self::FAILURE;
        }
        if ((! $this->option('approve') && ! $this->option('send')) || ($this->option('approve') && $this->option('send'))) {
            $this->error('Specify exactly one of --approve or --send.');
            return self::FAILURE;
        }
        $userId = (int) $this->option('user');
        if ($userId <= 0) {
            $this->error('--user must identify an authorized ERP user.');
            return self::FAILURE;
        }
        try {
            $export = TallyExport::with('journalEntry')->findOrFail((int)$this->argument('exportId'));
            $journalNo = $export->journalEntry?->journal_no ?? 'UNKNOWN';
            $this->line("Company: {$company}; Export: {$export->id}; Journal: {$journalNo}; Status: {$export->status}");
            if ($this->option('approve')) {
                $attempt = app(TallyJournalRecoveryService::class)->authorizeRecovery(
                    $export->id, $userId, (string)$this->option('reason'), (string)$this->option('evidence')
                );
                $this->info("Approval recorded. Attempt ID {$attempt->id}. No Tally transmission occurred.");
                return self::SUCCESS;
            }
            if (! $this->confirm('Have you freshly verified in the named Tally test company that the voucher is ABSENT, checked Day Book and exact ERP journal number, and confirmed the original rejection? Send ONCE?', false)) {
                $this->warn('Cancelled; no HTTP request made.');
                return self::FAILURE;
            }
            $result = app(TallyJournalRecoveryTransmissionService::class)->send($export->id, $userId);
            $this->line('Recovery attempt status: '.$result['status'].'; ID: '.$result['attempt_id']);
            return $result['status'] === 'confirmed' ? self::SUCCESS : self::FAILURE;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
