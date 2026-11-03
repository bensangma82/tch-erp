<?php

namespace App\Services\Accounting;

use App\Models\IpBillingAccount;
use App\Models\JournalEntry;
use RuntimeException;

final class StaffMedicalBenefitReconciliationService
{
    public function reconcile(IpBillingAccount $account): int
    {
        $totalPaise = 0;

        foreach ($account->staffMedicalBenefitTransactions()->get() as $benefit) {
            if (
                $benefit->source_type !== 'ip_billing_account'
                || (int) $benefit->source_id !== (int) $account->id
                || $benefit->transaction_type !== 'utilization'
                || $benefit->status !== 'active'
            ) {
                throw new RuntimeException(
                    'Staff Medical Benefit transaction requires reconciliation.'
                );
            }

            $amount = $this->paise($benefit->amount);

            if ($amount <= 0) {
                throw new RuntimeException('Invalid Staff Medical Benefit amount.');
            }

            $journals = JournalEntry::query()
                ->where('source_type', 'staff_medical_benefit_transaction')
                ->where('source_id', (string) $benefit->id)
                ->where('source_event', 'utilized')
                ->with('lines.generalLedgerAccount')
                ->get();

            if (
                $journals->count() !== 1
                || $journals->first()->status !== 'posted'
                || $journals->first()->journal_no !== 'JRN-IPD-SMB-'.$benefit->id
            ) {
                throw new RuntimeException(
                    'Staff Medical Benefit journal is missing, duplicated or unposted.'
                );
            }

            $journal = $journals->first();

            if ($journal->lines->count() !== 2) {
                throw new RuntimeException(
                    'Staff Medical Benefit journal has unexpected GL lines.'
                );
            }

            $debit6113 = 0;
            $credit1200 = 0;

            foreach ($journal->lines as $line) {
                $code = (string) $line->generalLedgerAccount?->account_code;
                $debit = $this->paise($line->debit);
                $credit = $this->paise($line->credit);

                if ($code === '6113' && $debit === $amount && $credit === 0) {
                    $debit6113++;
                } elseif ($code === '1200' && $credit === $amount && $debit === 0) {
                    $credit1200++;
                } else {
                    throw new RuntimeException(
                        'Staff Medical Benefit journal GL mapping does not reconcile.'
                    );
                }
            }

            if ($debit6113 !== 1 || $credit1200 !== 1) {
                throw new RuntimeException(
                    'Staff Medical Benefit journal amount does not reconcile.'
                );
            }

            $totalPaise += $amount;
        }

        return $totalPaise;
    }

    private function paise(mixed $value): int
    {
        $value = (string) $value;

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new RuntimeException('Invalid accounting amount.');
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '0');

        return ((int) $whole * 100)
            + (int) str_pad($fraction, 2, '0');
    }
}
