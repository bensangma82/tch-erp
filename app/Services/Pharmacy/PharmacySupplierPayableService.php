<?php

namespace App\Services\Pharmacy;

use App\Models\PharmacyPurchaseReturnItem;
use App\Models\PharmacySupplierCredit;
use App\Models\PharmacySupplierPayable;
use App\Models\PharmacySupplierPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PharmacySupplierPayableService
{
    /**
     * Recalculate the complete financial state of a supplier payable.
     *
     * The payable should already be locked with lockForUpdate()
     * by the calling transaction.
     */
    public function recalculate(
        PharmacySupplierPayable $payable,
        int $userId
    ): void {

        /*
        |--------------------------------------------------------------------------
        | Purchase Return Adjustment
        |--------------------------------------------------------------------------
        */

        $returnAdjustment =
            0.00;


        if ($payable->pharmacy_grn_id) {

            $returnAdjustment =
                round(
                    (float)
                    PharmacyPurchaseReturnItem::query()
                        ->whereHas(
                            'grnItem',
                            function ($query) use ($payable) {

                                $query->where(
                                    'pharmacy_grn_id',
                                    $payable->pharmacy_grn_id
                                );
                            }
                        )
                        ->sum(
                            'line_total'
                        ),
                    2
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Supplier Payments
        |--------------------------------------------------------------------------
        */

        $paidAmount =
            round(
                (float)
                PharmacySupplierPayment::query()
                    ->where(
                        'pharmacy_supplier_payable_id',
                        $payable->id
                    )
                    ->sum(
                        'amount'
                    ),
                2
            );


        /*
        |--------------------------------------------------------------------------
        | Original / Other Adjustments
        |--------------------------------------------------------------------------
        */

        $originalAmount =
            round(
                (float)
                $payable->original_amount,
                2
            );


        $otherAdjustment =
            round(
                (float)
                $payable->other_adjustment,
                2
            );


        /*
        |--------------------------------------------------------------------------
        | Raw Supplier Balance
        |--------------------------------------------------------------------------
        |
        | Positive = hospital still owes supplier
        | Zero     = exactly settled
        | Negative = supplier owes hospital / supplier credit
        |
        */

        $rawBalance =
            round(
                $originalAmount
                - $returnAdjustment
                - $otherAdjustment
                - $paidAmount,
                2
            );


        $outstanding =
            max(
                0,
                $rawBalance
            );


        $creditAmount =
            max(
                0,
                round(
                    -$rawBalance,
                    2
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Payable Status
        |--------------------------------------------------------------------------
        */

        if ($outstanding <= 0) {

            $status =
                'paid';

        } elseif ($paidAmount > 0) {

            $status =
                'partially_paid';

        } else {

            $status =
                'unpaid';
        }


        /*
        |--------------------------------------------------------------------------
        | Update Payable
        |--------------------------------------------------------------------------
        */

        $payable->update([

            'return_adjustment' =>
                $returnAdjustment,

            'paid_amount' =>
                $paidAmount,

            'outstanding_amount' =>
                $outstanding,

            'status' =>
                $status,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Synchronise Supplier Credit
        |--------------------------------------------------------------------------
        */

        $credit =
            PharmacySupplierCredit::query()
                ->where(
                    'source_payable_id',
                    $payable->id
                )
                ->lockForUpdate()
                ->first();


        /*
        |--------------------------------------------------------------------------
        | No Supplier Credit Exists
        |--------------------------------------------------------------------------
        */

        if ($creditAmount <= 0) {

            if ($credit) {

                $utilisedAmount =
                    round(
                        (float)
                        $credit->utilised_amount,
                        2
                    );


                /*
                |--------------------------------------------------------------------------
                | Safety
                |--------------------------------------------------------------------------
                |
                | A credit that has already been used cannot simply disappear.
                |
                */

                if ($utilisedAmount > 0) {

                    throw ValidationException::withMessages([
                        'supplier_credit' =>
                            'Supplier credit has already been utilised and cannot be reduced below the utilised amount.',
                    ]);
                }


                $credit->update([

                    'original_credit_amount' =>
                        0,

                    'available_amount' =>
                        0,

                    'status' =>
                        'cancelled',

                    'remarks' =>
                        'Supplier credit automatically cleared after payable recalculation.',
                ]);
            }


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Existing Supplier Credit
        |--------------------------------------------------------------------------
        */

        if ($credit) {

            $utilisedAmount =
                round(
                    (float)
                    $credit->utilised_amount,
                    2
                );


            if (
                $creditAmount
                < $utilisedAmount
            ) {

                throw ValidationException::withMessages([
                    'supplier_credit' =>
                        'Recalculated supplier credit is less than the amount already utilised.',
                ]);
            }


            $availableAmount =
                round(
                    $creditAmount
                    - $utilisedAmount,
                    2
                );


            if ($availableAmount <= 0) {

                $creditStatus =
                    'utilised';

            } elseif ($utilisedAmount > 0) {

                $creditStatus =
                    'partially_utilised';

            } else {

                $creditStatus =
                    'available';
            }


            $credit->update([

                'original_credit_amount' =>
                    $creditAmount,

                'available_amount' =>
                    $availableAmount,

                'status' =>
                    $creditStatus,

                'remarks' =>
                    'Automatically recalculated from '
                    . $payable->payable_no
                    . '.',
            ]);


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Create New Supplier Credit
        |--------------------------------------------------------------------------
        */

        DB::statement(
            "SELECT pg_advisory_xact_lock(hashtext('pharmacy_supplier_credit_number'))"
        );


        PharmacySupplierCredit::create([

            'credit_no' =>
                $this->generateCreditNumber(),

            'pharmacy_supplier_id' =>
                $payable->pharmacy_supplier_id,

            'source_payable_id' =>
                $payable->id,

            'source_grn_id' =>
                $payable->pharmacy_grn_id,

            'credit_date' =>
                now()->toDateString(),

            'original_credit_amount' =>
                $creditAmount,

            'utilised_amount' =>
                0,

            'available_amount' =>
                $creditAmount,

            'status' =>
                'available',

            'source_type' =>
                'overpayment_after_return',

            'remarks' =>
                'Automatically created from excess supplier payment after adjustment of '
                . $payable->payable_no
                . '.',

            'created_by' =>
                $userId,
        ]);
    }



    /**
     * Generate concurrency-safe supplier credit number.
     *
     * Example:
     * SC-20260914-000001
     *
     * Caller must hold:
     * pharmacy_supplier_credit_number
     */
    private function generateCreditNumber(): string
    {
        $prefix =
            'SC-'
            . now()->format('Ymd')
            . '-';


        $last =
            PharmacySupplierCredit::query()
                ->where(
                    'credit_no',
                    'like',
                    $prefix . '%'
                )
                ->orderByDesc('id')
                ->first();


        $next =
            1;


        if ($last) {

            $lastSequence =
                (int)
                substr(
                    $last->credit_no,
                    -6
                );


            $next =
                $lastSequence
                + 1;
        }


        return
            $prefix
            . str_pad(
                (string)
                $next,
                6,
                '0',
                STR_PAD_LEFT
            );
    }
}