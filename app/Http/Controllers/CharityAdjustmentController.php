<?php

namespace App\Http\Controllers;

use App\Models\CharityAdjustment;
use App\Models\Invoice;
use App\Models\IpBillingAccount;
use App\Services\CharityAdjustmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class CharityAdjustmentController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Store Charity / Write-off Request
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ): RedirectResponse
    {
        $validated =
            $request->validate([
                'patient_id' => [
                    'required',
                    'exists:patients,id',
                ],

                'invoice_id' => [
                    'nullable',
                    'exists:invoices,id',
                ],

                'encounter_id' => [
                    'nullable',
                    'exists:encounters,id',
                ],

                'admission_id' => [
                    'nullable',
                    'exists:admissions,id',
                ],

                'ip_billing_account_id' => [
                    'nullable',
                    'exists:ip_billing_accounts,id',
                ],

                'adjustment_type' => [
                    'required',
                    Rule::in([
                        'charity',
                        'write_off',
                    ]),
                ],

                'requested_amount' => [
                    'required',
                    'numeric',
                    'min:0.01',
                ],

                'reason' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'remarks' => [
                    'nullable',
                    'string',
                    'max:5000',
                ],
            ]);


        /*
        |--------------------------------------------------------------------------
        | Require Exactly One Billing Source
        |--------------------------------------------------------------------------
        */

        if (
            empty(
                $validated['invoice_id']
            )
            &&
            empty(
                $validated[
                    'ip_billing_account_id'
                ]
            )
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'requested_amount' =>
                        'The charity request must be linked to an invoice or IP billing account.',
                ]);
        }


        if (
            ! empty(
                $validated['invoice_id']
            )
            &&
            ! empty(
                $validated[
                    'ip_billing_account_id'
                ]
            )
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'requested_amount' =>
                        'A charity request cannot be linked to both an invoice and an IP billing account.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Against Current Balance
        |--------------------------------------------------------------------------
        */

        $currentBalance = 0;


        if (
            ! empty(
                $validated['invoice_id']
            )
        ) {
            $invoice =
                Invoice::query()
                    ->findOrFail(
                        $validated[
                            'invoice_id'
                        ]
                    );

            if (
                (int) $invoice->patient_id
                !==
                (int) $validated['patient_id']
            ) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'patient_id' =>
                            'The selected invoice does not belong to this patient.',
                    ]);
            }

            $currentBalance =
                round(
                    (float) $invoice
                        ->balance_amount,
                    2
                );
        }


        if (
            ! empty(
                $validated[
                    'ip_billing_account_id'
                ]
            )
        ) {
            $account =
                IpBillingAccount::query()
                    ->findOrFail(
                        $validated[
                            'ip_billing_account_id'
                        ]
                    );

            if (
                (int) $account->patient_id
                !==
                (int) $validated['patient_id']
            ) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'patient_id' =>
                            'The selected IP billing account does not belong to this patient.',
                    ]);
            }

            $currentBalance =
                round(
                    (float) $account
                        ->balance_amount,
                    2
                );
        }


        $requestedAmount =
            round(
                (float) $validated[
                    'requested_amount'
                ],
                2
            );


        if (
            $requestedAmount >
            $currentBalance
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'requested_amount' =>
                        'Requested charity amount cannot exceed the current outstanding balance of ₹'
                        . number_format(
                            $currentBalance,
                            2
                        )
                        . '.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Create Pending Request
        |--------------------------------------------------------------------------
        */

        CharityAdjustment::create([
            'patient_id' =>
                $validated['patient_id'],

            'invoice_id' =>
                $validated['invoice_id']
                ?? null,

            'encounter_id' =>
                $validated['encounter_id']
                ?? null,

            'admission_id' =>
                $validated['admission_id']
                ?? null,

            'ip_billing_account_id' =>
                $validated[
                    'ip_billing_account_id'
                ]
                ?? null,

            'adjustment_type' =>
                $validated[
                    'adjustment_type'
                ],

            'requested_amount' =>
                $requestedAmount,

            'approved_amount' =>
                null,

            'reason' =>
                $validated['reason'],

            'remarks' =>
                $validated['remarks']
                ?? null,

            'status' =>
                'pending',

            'requested_by' =>
                auth()->id(),

            'requested_at' =>
                now(),
        ]);


        return back()->with(
            'success',
            'Charity / write-off request submitted for approval.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Approve Request
    |--------------------------------------------------------------------------
    */

    public function approve(
        Request $request,
        CharityAdjustment $charityAdjustment
    ): RedirectResponse
    {
        if (
            ! auth()->user()
            || ! auth()->user()
                ->canApproveCharity()
        ) {
            abort(403);
        }


        if (
            $charityAdjustment->status !==
            'pending'
        ) {
            return back()->withErrors([
                'charity' =>
                    'Only pending charity requests can be approved.',
            ]);
        }


        $validated =
            $request->validate([
                'approved_amount' => [
                    'required',
                    'numeric',
                    'min:0.01',
                ],
            ]);


        $approvedAmount =
            round(
                (float) $validated[
                    'approved_amount'
                ],
                2
            );


        if (
            $approvedAmount >
            (float) $charityAdjustment
                ->requested_amount
        ) {
            return back()->withErrors([
                'approved_amount' =>
                    'Approved amount cannot exceed the requested amount.',
            ]);
        }


        $currentBalance =
            $this->currentBalance(
                $charityAdjustment
            );


        if (
            $approvedAmount >
            $currentBalance
        ) {
            return back()->withErrors([
                'approved_amount' =>
                    'Approved amount cannot exceed the current outstanding balance of ₹'
                    . number_format(
                        $currentBalance,
                        2
                    )
                    . '.',
            ]);
        }


        $charityAdjustment->update([
            'approved_amount' =>
                $approvedAmount,

            'status' =>
                'approved',

            'approved_by' =>
                auth()->id(),

            'approved_at' =>
                now(),
        ]);


        return back()->with(
            'success',
            'Charity / write-off request approved.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Reject Request
    |--------------------------------------------------------------------------
    */

    public function reject(
        Request $request,
        CharityAdjustment $charityAdjustment
    ): RedirectResponse
    {
        if (
            ! auth()->user()
            || ! auth()->user()
                ->canApproveCharity()
        ) {
            abort(403);
        }


        if (
            $charityAdjustment->status !==
            'pending'
        ) {
            return back()->withErrors([
                'charity' =>
                    'Only pending charity requests can be rejected.',
            ]);
        }


        $validated =
            $request->validate([
                'remarks' => [
                    'required',
                    'string',
                    'max:5000',
                ],
            ]);


        $existingRemarks =
            trim(
                (string) $charityAdjustment
                    ->remarks
            );


        $rejectionRemarks =
            'Rejection: '
            . $validated['remarks'];


        $charityAdjustment->update([
            'status' =>
                'rejected',

            'remarks' =>
                $existingRemarks !== ''
                    ? $existingRemarks
                        . PHP_EOL
                        . PHP_EOL
                        . $rejectionRemarks
                    : $rejectionRemarks,
        ]);


        return back()->with(
            'success',
            'Charity / write-off request rejected.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Apply Approved Adjustment
    |--------------------------------------------------------------------------
    */

    public function apply(
        CharityAdjustment $charityAdjustment,
        CharityAdjustmentService $service
    ): RedirectResponse
    {
        if (
            ! auth()->user()
            || ! auth()->user()
                ->canApproveCharity()
        ) {
            abort(403);
        }


        try {

            $service->apply(
                $charityAdjustment
            );

        } catch (
            RuntimeException $exception
        ) {

            return back()->withErrors([
                'charity' =>
                    $exception->getMessage(),
            ]);
        }


        return back()->with(
            'success',
            'Charity / write-off adjustment applied successfully.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Current Outstanding Balance
    |--------------------------------------------------------------------------
    */

    protected function currentBalance(
        CharityAdjustment $adjustment
    ): float
    {
        if ($adjustment->invoice_id) {

            $invoice =
                Invoice::query()
                    ->findOrFail(
                        $adjustment->invoice_id
                    );

            return round(
                (float) $invoice
                    ->balance_amount,
                2
            );
        }


        if (
            $adjustment
                ->ip_billing_account_id
        ) {
            $account =
                IpBillingAccount::query()
                    ->findOrFail(
                        $adjustment
                            ->ip_billing_account_id
                    );

            return round(
                (float) $account
                    ->balance_amount,
                2
            );
        }


        return 0;
    }
}