<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\DischargeSummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DischargeSummaryController extends Controller
{
    /**
     * Show the discharge summary form.
     */
    public function edit(
        Admission $admission
    ): View {

        $admission->load([
            'patient',
            'department',
            'consultant',
            'bed.ward',
            'currentBedAllocation.bed.ward',
            'emergencyVisit',
            'dischargeSummary',
        ]);


        $summary =
            $admission->dischargeSummary;


        /*
        |--------------------------------------------------------------------------
        | Finalised summaries cannot be edited
        |--------------------------------------------------------------------------
        */

        if (
            $summary
            &&
            $summary->status === 'finalised'
        ) {
            abort(
                409,
                'This discharge summary has already been finalised and can no longer be edited.'
            );
        }


        return view(
            'ipd.discharge-summary.edit',
            compact(
                'admission',
                'summary'
            )
        );
    }


    /**
     * Create or update the discharge summary.
     */
    public function update(
        Request $request,
        Admission $admission
    ): RedirectResponse {

        /*
        |--------------------------------------------------------------------------
        | Find existing summary first
        |--------------------------------------------------------------------------
        */

        $existingSummary =
            DischargeSummary::query()
                ->where(
                    'admission_id',
                    $admission->id
                )
                ->first();


        /*
        |--------------------------------------------------------------------------
        | Finalised summaries cannot be modified
        |--------------------------------------------------------------------------
        */

        if (
            $existingSummary
            &&
            $existingSummary->status === 'finalised'
        ) {
            abort(
                409,
                'This discharge summary has already been finalised and cannot be modified.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Validate clinical content
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'final_diagnosis' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'hospital_course' => [
                'nullable',
                'string',
                'max:20000',
            ],

            'procedures_performed' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'important_investigations' => [
                'nullable',
                'string',
                'max:20000',
            ],

            'treatment_given' => [
                'nullable',
                'string',
                'max:20000',
            ],

            'condition_at_discharge' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'discharge_medications' => [
                'nullable',
                'string',
                'max:20000',
            ],

            'review_date' => [
                'nullable',
                'date',
            ],

            'follow_up_advice' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'diet_advice' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'warning_signs' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Update existing summary
        |--------------------------------------------------------------------------
        */

        if ($existingSummary) {

            $existingSummary->update([
                ...$validated,

                'updated_by' =>
                    auth()->id(),
            ]);


        /*
        |--------------------------------------------------------------------------
        | Create new draft summary
        |--------------------------------------------------------------------------
        */

        } else {

            DischargeSummary::create([
                ...$validated,

                'admission_id' =>
                    $admission->id,

                'prepared_by' =>
                    auth()->id(),

                'updated_by' =>
                    auth()->id(),

                'status' =>
                    'draft',
            ]);
        }


        return redirect()
            ->route(
                'ipd.discharge-summary.edit',
                $admission
            )
            ->with(
                'success',
                'Discharge summary saved successfully.'
            );
    }


    /**
     * Show the discharge summary.
     */
    public function show(
        Admission $admission
    ): View {

        $admission->load([
            'patient',
            'department',
            'consultant',
            'bed.ward',
            'currentBedAllocation.bed.ward',
            'emergencyVisit',
            'dischargeSummary.preparedBy',
            'dischargeSummary.updatedBy',
            'dischargeSummary.finalisedBy',
        ]);


        $summary =
            $admission->dischargeSummary;


        abort_if(
            ! $summary,
            404,
            'Discharge summary has not yet been created.'
        );


        return view(
            'ipd.discharge-summary.show',
            compact(
                'admission',
                'summary'
            )
        );
    }


    /**
     * Finalise discharge summary.
     *
     * Only the consultant assigned to this admission
     * may finalise the summary.
     */
    public function finalise(
        Admission $admission
    ): RedirectResponse {

        $admission->load([
            'consultant',
            'dischargeSummary',
        ]);


        $summary =
            $admission->dischargeSummary;


        abort_if(
            ! $summary,
            404,
            'Discharge summary has not yet been created.'
        );


        /*
        |--------------------------------------------------------------------------
        | Already finalised
        |--------------------------------------------------------------------------
        */

        if ($summary->status === 'finalised') {

            return redirect()
                ->route(
                    'ipd.discharge-summary.show',
                    $admission
                )
                ->with(
                    'success',
                    'Discharge summary is already finalised.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Assigned consultant must have linked ERP user
        |--------------------------------------------------------------------------
        */

        if (
            ! $admission->consultant
            ||
            ! $admission->consultant->user_id
        ) {
            abort(
                409,
                'The assigned consultant does not have a linked ERP user account.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Only assigned consultant may finalise
        |--------------------------------------------------------------------------
        */

        if (
            (int) $admission->consultant->user_id
            !==
            (int) auth()->id()
        ) {
            abort(
                403,
                'Only the consultant assigned to this admission may finalise the discharge summary.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Finalise
        |--------------------------------------------------------------------------
        */

        $summary->update([
            'status' =>
                'finalised',

            'finalised_by' =>
                auth()->id(),

            'finalised_at' =>
                now(),
        ]);


        return redirect()
            ->route(
                'ipd.discharge-summary.show',
                $admission
            )
            ->with(
                'success',
                'Discharge summary finalised successfully. Printing is now enabled.'
            );
    }
}