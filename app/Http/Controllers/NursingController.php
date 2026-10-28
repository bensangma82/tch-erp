<?php
namespace App\Http\Controllers;
use App\Models\Encounter;
use App\Models\EncounterVital;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'mark_seen' => [
                'sometimes',
                'boolean',
            ],
        ]);
        DB::transaction(function () use ($encounter, $validated, $request) {
            $locked = Encounter::query()
                ->lockForUpdate()
                ->findOrFail($encounter->id);
            abort_unless($locked->encounter_type === 'OPD', 422);
            if ($request->boolean('mark_seen')) {
                abort_unless(
                    auth()->user()?->hasPermission('nursing.vitals'),
                    403
                );
                abort_unless(
                    $locked->doctor_id !== null,
                    422,
                    'An assigned doctor is required.'
                );
                abort_unless(
                    in_array($locked->status, ['waiting', 'waiting_for_doctor'], true)
                    && $locked->seen_at === null,
                    409,
                    'This consultation cannot be marked as seen.'
                );
            }
        if (
                filled($validated['systolic_bp'] ?? null) ||
                filled($validated['diastolic_bp'] ?? null) ||
                filled($validated['pulse_rate'] ?? null) ||
                filled($validated['respiratory_rate'] ?? null) ||
                filled($validated['temperature'] ?? null) ||
                filled($validated['spo2'] ?? null) ||
                filled($validated['weight_kg'] ?? null) ||
                filled($validated['height_cm'] ?? null) ||
                filled($validated['blood_glucose'] ?? null) ||
                filled($validated['notes'] ?? null)
            ) {
            EncounterVital::create([
                'encounter_id' =>
                    $locked->id,
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
            }
            if ($locked->status !== 'completed') {
                if ($request->boolean('mark_seen')) {
                    $locked->update([
                        'status' => 'completed',
                        'seen_at' => now(),
                        'seen_by' => auth()->id(),
                    ]);
                } elseif ($locked->status === 'waiting') {
                    $locked->update([
                        'status' => 'waiting_for_doctor',
                    ]);
                }
            }
        });
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
