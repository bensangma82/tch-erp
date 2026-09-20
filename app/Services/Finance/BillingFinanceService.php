<?php

namespace App\Services\Finance;

use App\Models\FinanceAccount;
use App\Models\FinanceHead;
use App\Models\Payment;
use Illuminate\Support\Collection;
use RuntimeException;

class BillingFinanceService
{
    /**
     * Resolve the Finance account for a Billing payment.
     *
     * For now:
     * cash -> Main Cash
     *
     * UPI/Card mapping can be configured later.
     */
    public function resolveAccount(Payment $payment): ?FinanceAccount
    {
        $accountCode = match (
            strtolower((string) $payment->payment_mode)
        ) {
            'cash' => 'CASH-MAIN',

            default => null,
        };

        if ($accountCode === null) {
            return null;
        }

        return FinanceAccount::query()
            ->where('code', $accountCode)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Allocate the actual amount received across Finance income heads.
     *
     * This does NOT create a FinanceVoucher.
     * Billing Payment remains the source transaction.
     */
    public function allocateRevenue(Payment $payment): Collection
    {
        $payment->loadMissing([
            'invoice.items.service',
        ]);

        $invoice = $payment->invoice;

        if (!$invoice) {
            return collect();
        }

        /*
        |--------------------------------------------------------------------------
        | OPD / Consultation
        |--------------------------------------------------------------------------
        |
        | OPD registration invoices currently have no InvoiceItems.
        | Therefore the actual payment is allocated directly to INC-OPD.
        |
        */

        if (
            strtoupper((string) $invoice->invoice_type) === 'OPD'
            && $invoice->items->isEmpty()
        ) {
            $head = $this->findIncomeHead('INC-OPD');

            return collect([
                [
                    'finance_head_id' => $head->id,
                    'finance_head_code' => $head->code,
                    'finance_head_name' => $head->name,
                    'amount' => round(
                        (float) $payment->amount,
                        2
                    ),
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Investigation / Service Invoice
        |--------------------------------------------------------------------------
        */

        if ($invoice->items->isEmpty()) {
            return collect();
        }

        $itemTotal = round(
            (float) $invoice->items->sum(
                fn ($item) => (float) $item->amount
            ),
            2
        );

        if ($itemTotal <= 0) {
            return collect();
        }

        $paymentAmount = round(
            (float) $payment->amount,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Group billed amount by Finance Head
        |--------------------------------------------------------------------------
        */

        $grouped = [];

        foreach ($invoice->items as $item) {
            $service = $item->service;

            if (!$service) {
                continue;
            }

            $headCode = $this->financeHeadCodeForServiceCategory(
                $service->category
            );

            if ($headCode === null) {
                continue;
            }

            if (!isset($grouped[$headCode])) {
                $grouped[$headCode] = 0.0;
            }

            $grouped[$headCode] +=
                (float) $item->amount;
        }

        if (empty($grouped)) {
            return collect();
        }

        /*
        |--------------------------------------------------------------------------
        | Proportional Payment Allocation
        |--------------------------------------------------------------------------
        |
        | Revenue is based on money actually received.
        |
        | Example:
        | Invoice = 1,000 Lab + 1,000 Radiology
        | Payment = 1,000
        |
        | Finance allocation:
        | Lab       = 500
        | Radiology = 500
        |
        */

        $allocations = collect();
        $allocatedAmount = 0.0;
        $headCodes = array_keys($grouped);
        $lastHeadCode = end($headCodes);

        foreach ($grouped as $headCode => $billedAmount) {
            $head = $this->findIncomeHead(
                $headCode
            );

            if ($headCode === $lastHeadCode) {
                $allocated = round(
                    $paymentAmount - $allocatedAmount,
                    2
                );
            } else {
                $allocated = round(
                    $paymentAmount
                    * ((float) $billedAmount / $itemTotal),
                    2
                );

                $allocatedAmount += $allocated;
            }

            $allocations->push([
                'finance_head_id' => $head->id,
                'finance_head_code' => $head->code,
                'finance_head_name' => $head->name,
                'amount' => $allocated,
            ]);
        }

        return $allocations;
    }

    /**
     * Produce the Finance representation of a Billing payment.
     *
     * No database record is created here.
     */
    public function summarize(Payment $payment): array
    {
        $account = $this->resolveAccount(
            $payment
        );

        return [
            'payment_id' => $payment->id,
            'receipt_no' => $payment->receipt_no,
            'payment_date' => $payment->payment_date,
            'payment_mode' => $payment->payment_mode,
            'amount' => round(
                (float) $payment->amount,
                2
            ),
            'finance_account_id' => $account?->id,
            'finance_account_code' => $account?->code,
            'finance_account_name' => $account?->name,
            'allocations' => $this
                ->allocateRevenue($payment)
                ->values()
                ->all(),
        ];
    }

    private function financeHeadCodeForServiceCategory(
        ?string $category
    ): ?string {
        return match (
            strtolower(trim((string) $category))
        ) {
            'laboratory' => 'INC-LAB',
            'radiology' => 'INC-RAD',

            /*
             * These are ready for future Service Master categories.
             */
            'ecg' => 'INC-ECG',
            'echo' => 'INC-ECHO',
            'endoscopy' => 'INC-ENDO',
            'dialysis' => 'INC-DIALYSIS',
            'procedure',
            'procedures' => 'INC-PROCEDURE',

            default => null,
        };
    }

    private function findIncomeHead(
        string $code
    ): FinanceHead {
        $head = FinanceHead::query()
            ->where('code', $code)
            ->where('head_type', 'income')
            ->where('is_active', true)
            ->first();

        if (!$head) {
            throw new RuntimeException(
                "Active Finance income head {$code} was not found."
            );
        }

        return $head;
    }
}
