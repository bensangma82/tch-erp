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


        $summary =
            DischargeSummary::query()
                ->where(
                    'admission_id',
                    $admission->id
                )
                ->first();


        if ($summary) {

            $summary->update([
                ...$validated,

                'updated_by' =>
                    auth()->id(),
            ]);

        } else {

            $summary =
                DischargeSummary::create([
                    ...$validated,

                    'admission_id' =>
                        $admission->id,

                    'prepared_by' =>
                        auth()->id(),

                    'updated_by' =>
                        auth()->id(),
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
     * Show the completed discharge summary.
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
}