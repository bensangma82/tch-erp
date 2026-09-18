<?php

namespace App\Http\Controllers;

use App\Models\EmergencyTriage;
use App\Models\EmergencyVisit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmergencyTriageController extends Controller
{
    /**
     * Show triage entry form.
     */
    public function create(
        EmergencyVisit $emergencyVisit
    ): View {

        $emergencyVisit->load([
            'patient',
            'latestTriage',
        ]);

        return view(
            'emergency.triage.create',
            compact('emergencyVisit')
        );
    }


    /**
     * Store a new triage assessment.
     */
    public function store(
        Request $request,
        EmergencyVisit $emergencyVisit
    ): RedirectResponse {

        $validated = $request->validate([
            'temperature' => [
                'nullable',
                'numeric',
                'between:25,45',
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

            'triage_category' => [
                'required',
                'in:red,orange,yellow,green,blue',
            ],

            'oxygen_support' => [
                'nullable',
                'string',
                'max:100',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);


        EmergencyTriage::create([
            'emergency_visit_id' =>
                $emergencyVisit->id,

            'temperature' =>
                $validated['temperature'] ?? null,

            'pulse' =>
                $validated['pulse'] ?? null,

            'respiratory_rate' =>
                $validated['respiratory_rate'] ?? null,

            'blood_pressure_systolic' =>
                $validated['blood_pressure_systolic'] ?? null,

            'blood_pressure_diastolic' =>
                $validated['blood_pressure_diastolic'] ?? null,

            'spo2' =>
                $validated['spo2'] ?? null,

            'gcs' =>
                $validated['gcs'] ?? null,

            'pain_score' =>
                $validated['pain_score'] ?? null,

            'triage_category' =>
                $validated['triage_category'],

            'oxygen_support' =>
                $validated['oxygen_support'] ?? null,

            'notes' =>
                $validated['notes'] ?? null,

            'recorded_by' =>
                auth()->id(),

            'recorded_at' =>
                now(),
        ]);


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
                'status' => 'triaged',
            ]);
        }


        return redirect()
            ->route(
                'emergency.show',
                $emergencyVisit
            )
            ->with(
                'success',
                'Emergency triage recorded successfully.'
            );
    }
}