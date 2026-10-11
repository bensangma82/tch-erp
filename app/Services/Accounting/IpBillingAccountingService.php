<?php

namespace App\Services\Accounting;

use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IpBillingAccountingService
{
    public function __construct(
        private JournalPostingService $postingService
    ) {}

    public function postFinalBill(
        IpBillingAccount $account
    ): ?JournalEntry {
        return DB::transaction(function () use ($account) {

            $account = IpBillingAccount::query()
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $account->status !== 'finalized'
                || ! $account->final_bill_no
                || ! $account->finalized_at
            ) {
                throw new RuntimeException(
                    'IPD bill must be finalized before accounting.'
                );
            }

            $existing = JournalEntry::query()
                ->where('source_type', 'ip_billing_account')
                ->where('source_id', (string) $account->id)
                ->where('source_event', 'finalized')
                ->first();

            if ($existing) {
                if ($existing->status !== 'posted') {
                    throw new RuntimeException(
                        'An unposted IPD journal already exists.'
                    );
                }

                return $existing;
            }

            $toCents = static function ($value): int {
                $value = (string) $value;

                if (! preg_match(
                    '/^\d+(?:\.\d{1,2})?$/',
                    $value
                )) {
                    throw new RuntimeException(
                        'Invalid IPD accounting amount.'
                    );
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

            $adjustments = $account->mhisAdjustments()
                ->where('status', 'active')
                ->count();

            if ($adjustments > 0) {
                throw new RuntimeException(
                    'MHIS package adjustments require separate accounting configuration.'
                );
            }

            $charges = $account->charges()
                ->where('status', 'active')
                ->with('service')
                ->get();

            $totalCents = $toCents($account->net_amount);
            $chargeTotalCents = 0;

            $receivable = GeneralLedgerAccount::query()
                ->where('account_code', '1200')
                ->where('is_active', true)
                ->where('allow_posting', true)
                ->firstOrFail();

            $revenueAccounts = GeneralLedgerAccount::query()
    ->where('account_type', 'income')
    ->where('is_active', true)
    ->where('allow_posting', true)
    ->whereNotNull('finance_head_id')
    ->whereNotIn('account_code', ['4101', '4102'])
    ->get()
    ->keyBy('finance_head_id');

            $bedRevenue = GeneralLedgerAccount::query()
                ->where('account_code', '4301')
                ->where('is_active', true)
                ->where('allow_posting', true)
                ->firstOrFail();

            $grouped = [];

            foreach ($charges as $charge) {
                $cents = $toCents($charge->amount);

                if ($cents === 0) {
                    continue;
                }

                $chargeTotalCents += $cents;

                if ($charge->service_id) {
                    $service = $charge->service;

                    if (
                        ! $service
                        || ! $service->finance_head_id
                    ) {
                        throw new RuntimeException(
                            'Missing finance-head mapping for IPD charge '.$charge->id
                        );
                    }

                    $revenue = $revenueAccounts->get(
                        $service->finance_head_id
                    );

                    if (! $revenue) {
                        throw new RuntimeException(
                            'Missing revenue GL account for service '.$service->code
                        );
                    }

                    $departmentId = $service->department_id;
                } elseif ($charge->charge_type === 'bed') {
                    $revenue = $bedRevenue;
                    $departmentId = null;
                } else {
                    throw new RuntimeException(
                        'Unmapped IPD charge type: '.$charge->charge_type
                    );
                }

                $key = $revenue->id.':'.($departmentId ?? 'none');

                if (! isset($grouped[$key])) {
                    $grouped[$key] = [
                        'account_id' => $revenue->id,
                        'department_id' => $departmentId,
                        'cents' => 0,
                    ];
                }

                $grouped[$key]['cents'] += $cents;
            }

            if ($chargeTotalCents !== $totalCents) {
                throw new RuntimeException(
                    'IPD charges do not reconcile with the final bill.'
                );
            }

            if ($totalCents === 0) {
                return null;
            }

            $journal = JournalEntry::create([
                'journal_no' => 'JRN-IPD-'.$account->id,
                'journal_date' => $account->finalized_at->toDateString(),
                'source_type' => 'ip_billing_account',
                'source_id' => (string) $account->id,
                'source_event' => 'finalized',
                'reference_no' => $account->final_bill_no,
                'narration' => 'IPD final bill '.$account->final_bill_no,
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
                'reference_no' => $account->final_bill_no,
                'description' => 'IPD patient receivable',
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
                    'reference_no' => $account->final_bill_no,
                    'description' => 'IPD departmental revenue',
                ]);
            }

            return $this->postingService->post($journal);
        });
    }
}