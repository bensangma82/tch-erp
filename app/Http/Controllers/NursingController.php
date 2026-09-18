<?php

namespace App\Http\Controllers;

use App\Models\Encounter;
use App\Models\EncounterVital;
use Illuminate\Http\Request;

class NursingController extends Controller
{
    /**
     * Show today's OPD patients for nursing assessment.
     */
    public function index()
    {
        $encounters = Encounter::with([
                'patient',
                'department',
                'doctor',
                'vitals',
            ])
            ->where(
                'encounter_type',
                'OPD'
            )
            ->whereDate(
                'encounter_date',
                today()
            )
            ->orderBy('queue_number')
            ->get();

        return view(
            'nursing.index',
            compact('encounters')
        );
    }


    /**
     * Show vitals entry form.
     */
    public function createVitals(
        Encounter $encounter
    ) {
        $encounter->load([
            'patient',
            'department',
            'doctor',
            'vitals',
        ]);

        return view(
            'nursing.vitals',
            compact('encounter')
        );
    }


    /**
     * Store vitals for an encounter.
     */
    public function storeVitals(
        Request $request,
        Encounter $encounter
    ) {
        $validated = $request->validate([

            'systolic_bp' => [
                'nullable',
                'integer',
                'min:30',
                'max:300',
            ],

            'diastolic_bp' => [
                'nullable',
                'integer',
                'min:20',
                'max:200',
            ],

            'pulse_rate' => [
                'nullable',
                'integer',
                'min:20',
                'max:250',
            ],

            'respiratory_rate' => [
                'nullable',
                'integer',
                'min:5',
                'max:80',
            ],

            'temperature' => [
                'nullable',
                'numeric',
                'min:30',
                'max:45',
            ],

            'spo2' => [
                'nullable',
                'integer',
                'min:20',
                'max:100',
            ],

            'weight_kg' => [
                'nullable',
                'numeric',
                'min:0.5',
                'max:500',
            ],

            'height_cm' => [
                'nullable',
                'numeric',
                'min:20',
                'max:250',
            ],

            'blood_glucose' => [
                'nullable',
                'numeric',
                'min:10',
                'max:2000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

        ]);


        EncounterVital::create([

            'encounter_id' =>
                $encounter->id,

            'systolic_bp' =>
                $validated['systolic_bp'] ?? null,

            'diastolic_bp' =>
                $validated['diastolic_bp'] ?? null,

            'pulse_rate' =>
                $validated['pulse_rate'] ?? null,

            'respiratory_rate' =>
                $validated['respiratory_rate'] ?? null,

            'temperature' =>
                $validated['temperature'] ?? null,

            'spo2' =>
                $validated['spo2'] ?? null,

            'weight_kg' =>
                $validated['weight_kg'] ?? null,

            'height_cm' =>
                $validated['height_cm'] ?? null,

            'blood_glucose' =>
                $validated['blood_glucose'] ?? null,

            'notes' =>
                $validated['notes'] ?? null,

            'recorded_by' =>
                auth()->id(),

            'recorded_at' =>
                now(),

        ]);
        
        $encounter->update([
    'status' => 'waiting_for_doctor',
]);


        return redirect()
            ->route('nursing.index')
            ->with(
                'success',
                'Vitals recorded successfully for '
                . $encounter->patient->full_name
                . '.'
            );
    }
}