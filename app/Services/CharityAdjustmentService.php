<?php

namespace App\Services;

use App\Models\CharityAdjustment;
use App\Models\Invoice;
use App\Models\IpBillingAccount;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CharityAdjustmentService
{
    public function apply(
        CharityAdjustment $adjustment
    ): CharityAdjustment
    {
        return DB::transaction(
            function () use ($adjustment) {

                $adjustment =
                    CharityAdjustment::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $adjustment->id
                        );

                if (
                    $adjustment->status !==
                    'approved'
                ) {
                    throw new RuntimeException(
                        'Only approved charity adjustments can be applied.'
                    );
                }

                $approvedAmount =
                    round(
                        (float) $adjustment
                            ->approved_amount,
                        2
                    );

                if ($approvedAmount <= 0) {
                    throw new RuntimeException(
                        'Approved charity amount must be greater than zero.'
                    );
                }


                if ($adjustment->invoice_id) {

                    $this->applyToInvoice(
                        $adjustment,
                        $approvedAmount
                    );

                } elseif (
                    $adjustment
                        ->ip_billing_account_id
                ) {

                    $this->applyToIpAccount(
                        $adjustment,
                        $approvedAmount
                    );

                } else {

                    throw new RuntimeException(
                        'Charity adjustment is not linked to a bill.'
                    );
                }


                $adjustment->update([
                    'status' => 'applied',
                    'applied_at' => now(),
                ]);

                return $adjustment->fresh();
            }
        );
    }


    protected function applyToInvoice(
        CharityAdjustment $adjustment,
        float $approvedAmount
    ): void
    {
        $invoice =
            Invoice::query()
                ->lockForUpdate()
                ->findOrFail(
                    $adjustment->invoice_id
                );

        $currentBalance =
            round(
                (float) $invoice
                    ->balance_amount,
                2
            );

        if (
            $approvedAmount >
            $currentBalance
        ) {
            throw new RuntimeException(
                'Charity amount cannot exceed the invoice balance.'
            );
        }

        $newBalance =
            round(
                $currentBalance
                - $approvedAmount,
                2
            );

        $invoice->balance_amount =
            max(
                0,
                $newBalance
            );

        if (
            $invoice->balance_amount <= 0
        ) {
            $invoice->status =
                'paid';
        }

        $invoice->save();
    }


    protected function applyToIpAccount(
        CharityAdjustment $adjustment,
        float $approvedAmount
    ): void
    {
        $account =
            IpBillingAccount::query()
                ->lockForUpdate()
                ->findOrFail(
                    $adjustment
                        ->ip_billing_account_id
                );

        $currentBalance =
            round(
                (float) $account
                    ->balance_amount,
                2
            );

        if (
            $approvedAmount >
            $currentBalance
        ) {
            throw new RuntimeException(
                'Charity amount cannot exceed the IP billing balance.'
            );
        }

        $newBalance =
            round(
                $currentBalance
                - $approvedAmount,
                2
            );

        $account->balance_amount =
            max(
                0,
                $newBalance
            );

        $account->save();
    }
}