<?php

namespace App\Http\Controllers;

use App\Models\EmergencyVisit;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmergencyVisitController extends Controller
{
    /**
     * Emergency patient queue / register.
     */
    public function index(Request $request): View
    {
        $query = EmergencyVisit::query()
            ->with([
                'patient',
                'latestTriage',
                'doctor',
                'admission',
            ]);

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->string('status')->toString()
            );
        }

        if ($request->filled('date')) {
            $query->whereDate(
                'arrival_at',
                $request->date('date')
            );
        }

        $emergencyVisits = $query
            ->orderByDesc('arrival_at')
            ->paginate(25)
            ->withQueryString();

        return view(
            'emergency.index',
            compact('emergencyVisits')
        );
    }


    /**
     * Emergency registration form.
     */
    public function create(): View
    {
        /*
        |--------------------------------------------------------------------------
        | Initial patient list
        |--------------------------------------------------------------------------
        |
        | We will later add proper AJAX patient search.
        | For the first working version, load the most recently registered
        | patients so an existing patient can be selected.
        |
        */

        $patients = Patient::query()
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view(
            'emergency.create',
            compact('patients')
        );
    }


    /**
     * Register a new emergency visit.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'patient_id' => [
                'required',
                'integer',
                'exists:patients,id',
            ],

            'arrival_at' => [
                'required',
                'date',
            ],

            'arrival_mode' => [
                'nullable',
                'string',
                'max:50',
            ],

            'brought_by' => [
                'nullable',
                'string',
                'max:150',
            ],

            'chief_complaint' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);


        $emergencyVisit = DB::transaction(
            function () use ($validated) {

                /*
                |--------------------------------------------------------------------------
                | Emergency number lock
                |--------------------------------------------------------------------------
                |
                | Prevents two users registering the same emergency number when
                | registrations happen simultaneously.
                |
                */

                DB::statement(
                    "SELECT pg_advisory_xact_lock(hashtext('emergency_visit_number'))"
                );


                $date =
                    now()->format('Ymd');


                $prefix =
                    'EMG-' . $date . '-';


                $lastEmergencyNo = EmergencyVisit::query()
                    ->where(
                        'emergency_no',
                        'like',
                        $prefix . '%'
                    )
                    ->orderByDesc('emergency_no')
                    ->value('emergency_no');


                $nextSequence = 1;


                if ($lastEmergencyNo) {

                    $lastSequence =
                        (int) substr(
                            $lastEmergencyNo,
                            -6
                        );

                    $nextSequence =
                        $lastSequence + 1;
                }


                $emergencyNo =
                    $prefix
                    . str_pad(
                        (string) $nextSequence,
                        6,
                        '0',
                        STR_PAD_LEFT
                    );


                return EmergencyVisit::create([
                    'emergency_no' =>
                        $emergencyNo,

                    'patient_id' =>
                        $validated['patient_id'],

                    'arrival_at' =>
                        $validated['arrival_at'],

                    'arrival_mode' =>
                        $validated['arrival_mode'] ?? null,

                    'brought_by' =>
                        $validated['brought_by'] ?? null,

                    'chief_complaint' =>
                        $validated['chief_complaint'] ?? null,

                    'status' =>
                        'registered',

                    'doctor_id' =>
                        null,

                    'disposition' =>
                        null,

                    'disposition_at' =>
                        null,

                    'admission_id' =>
                        null,

                    'created_by' =>
                        auth()->id(),
                ]);
            }
        );


        return redirect()
            ->route(
                'emergency.show',
                $emergencyVisit
            )
            ->with(
                'success',
                'Emergency patient registered successfully.'
            );
    }


    /**
     * Show one emergency visit.
     */
    public function show(
        EmergencyVisit $emergencyVisit
    ): View {

        $emergencyVisit->load([
            'patient',
            'doctor',
            'createdBy',
            'triageRecords.recordedBy',
            'latestTriage',
            'admission.department',
            'admission.consultant',
            'admission.bed.ward',
        ]);


        return view(
            'emergency.show',
            compact('emergencyVisit')
        );
    }
}