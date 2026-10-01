<?php

namespace App\Http\Controllers;

use App\Models\EmergencyClinicalNote;
use App\Models\EmergencyVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmergencyClinicalNoteController extends Controller
{
    /**
     * Show Emergency Visit Sheet form.
     */
    public function create(
        EmergencyVisit $emergencyVisit
    ): View {

        $emergencyVisit->load([
            'patient',
            'latestTriage',
            'clinicalNote',
        ]);

        return view(
            'emergency.clinical-note.create',
            compact('emergencyVisit')
        );
    }


    /**
     * Store or update Emergency Visit Sheet.
     */
    public function store(
        Request $request,
        EmergencyVisit $emergencyVisit
    ): RedirectResponse {

        $validated = $request->validate([
            'presenting_complaints' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'history' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'blood_pressure_systolic' => [
                'nullable',
                'integer',
                'between:40,300',
            ],

            'blood_pressure_diastolic' => [
                'nullable',
                'integer',
                'between:20,200',
            ],

            'pulse' => [
                'nullable',
                'integer',
                'between:20,300',
            ],

            'respiratory_rate' => [
                'nullable',
                'integer',
                'between:5,100',
            ],

            'temperature' => [
                'nullable',
                'numeric',
                'between:25,45',
            ],

            'spo2' => [
                'nullable',
                'integer',
                'between:0,100',
            ],

            'gcs' => [
                'nullable',
                'integer',
                'between:3,15',
            ],

            'pain_score' => [
                'nullable',
                'integer',
                'between:0,10',
            ],

            'oxygen_support' => [
                'nullable',
                'string',
                'max:100',
            ],

            'general_examination' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'systemic_examination' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'provisional_diagnosis' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'investigations' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'treatment_given' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'disposition' => [
                'nullable',
                'in:discharged,admitted,observation,referred,lama,absconded,death',
            ],

            'discharge_advice' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ]);


        EmergencyClinicalNote::updateOrCreate(
            [
                'emergency_visit_id' =>
                    $emergencyVisit->id,
            ],
            [
                'presenting_complaints' =>
                    $validated['presenting_complaints'] ?? null,

                'history' =>
                    $validated['history'] ?? null,

                'blood_pressure_systolic' =>
                    $validated['blood_pressure_systolic'] ?? null,

                'blood_pressure_diastolic' =>
                    $validated['blood_pressure_diastolic'] ?? null,

                'pulse' =>
                    $validated['pulse'] ?? null,

                'respiratory_rate' =>
                    $validated['respiratory_rate'] ?? null,

                'temperature' =>
                    $validated['temperature'] ?? null,

                'spo2' =>
                    $validated['spo2'] ?? null,

                'gcs' =>
                    $validated['gcs'] ?? null,

                'pain_score' =>
                    $validated['pain_score'] ?? null,

                'oxygen_support' =>
                    $validated['oxygen_support'] ?? null,

                'general_examination' =>
                    $validated['general_examination'] ?? null,

                'systemic_examination' =>
                    $validated['systemic_examination'] ?? null,

                'provisional_diagnosis' =>
                    $validated['provisional_diagnosis'] ?? null,

                'investigations' =>
                    $validated['investigations'] ?? null,

                'treatment_given' =>
                    $validated['treatment_given'] ?? null,

                'disposition' =>
                    $validated['disposition'] ?? null,

                'discharge_advice' =>
                    $validated['discharge_advice'] ?? null,

                'doctor_id' =>
                    auth()->id(),

                'created_by' =>
                    $emergencyVisit->clinicalNote?->created_by
                    ?? auth()->id(),

                'documented_at' =>
                    now(),
            ]
        );


        if (
            in_array(
                $emergencyVisit->status,
                [
                    'registered',
                    'triaged',
                ],
                true
            )
        ) {
            $emergencyVisit->update([
                'status' => 'under_treatment',
            ]);
        }


        return redirect()
            ->route(
                'emergency.show',
                $emergencyVisit
            )
            ->with(
                'success',
                'Emergency Visit Sheet saved successfully.'
            );
    }

    /**
 * Printable Emergency Visit Sheet.
 */
public function print(
    EmergencyVisit $emergencyVisit
): View {

    $emergencyVisit->load([
        'patient',
        'latestTriage',
        'clinicalNote.doctor',
        'clinicalNote.createdBy',
    ]);

    abort_unless(
        $emergencyVisit->clinicalNote,
        404,
        'Emergency Visit Sheet has not yet been recorded.'
    );

    return view(
        'emergency.clinical-note.print',
        compact('emergencyVisit')
    );
}
}
