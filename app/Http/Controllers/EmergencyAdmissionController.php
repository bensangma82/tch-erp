<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Bed;
use App\Models\BedAllocation;
use App\Models\Department;
use App\Models\EmergencyVisit;
use App\Models\Employee;
use App\Models\Ward;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmergencyAdmissionController extends Controller
{
    /**
     * Show the Emergency -> IPD admission form.
     */
    public function create(
        EmergencyVisit $emergencyVisit
    ): View {

        $emergencyVisit->load([
            'patient',
            'latestTriage',
            'admission',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate admission
        |--------------------------------------------------------------------------
        */

        if (
            $emergencyVisit->admission_id
            ||
            $emergencyVisit->admission
            ||
            $emergencyVisit->status === 'admitted'
        ) {
            abort(
                409,
                'This emergency visit has already been admitted.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Departments
        |--------------------------------------------------------------------------
        */

        $departments = Department::query()
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Consultants
        |--------------------------------------------------------------------------
        |
        | Doctors are maintained in the employees master.
        | A doctor does not need to have a login account to be selected as
        | the admitting consultant.
        |
        */

        $consultants = Employee::query()
            ->where('is_doctor', true)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('last_name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Active wards
        |--------------------------------------------------------------------------
        */

        $wards = Ward::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Available beds
        |--------------------------------------------------------------------------
        */

        $availableBeds = Bed::query()
            ->with('ward')
            ->where('is_active', true)
            ->where('status', 'available')
            ->orderBy('ward_id')
            ->orderBy('bed_number')
            ->get();


        return view(
            'emergency.admission.create',
            compact(
                'emergencyVisit',
                'departments',
                'consultants',
                'wards',
                'availableBeds'
            )
        );
    }


    /**
     * Admit an Emergency patient to IPD.
     */
    public function store(
        Request $request,
        EmergencyVisit $emergencyVisit
    ): RedirectResponse {

        $validated = $request->validate([
            'admitted_at' => [
                'required',
                'date',
            ],

            'department_id' => [
                'required',
                'integer',
                'exists:departments,id',
            ],

            'consultant_id' => [
                'nullable',
                'integer',
                'exists:employees,id',
            ],

            'ward_id' => [
                'required',
                'integer',
                'exists:wards,id',
            ],

            'bed_id' => [
                'required',
                'integer',
                'exists:beds,id',
            ],

            'admission_reason' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'provisional_diagnosis' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Validate consultant is an active doctor
        |--------------------------------------------------------------------------
        */

        if (! empty($validated['consultant_id'])) {

            $validConsultant = Employee::query()
                ->whereKey(
                    $validated['consultant_id']
                )
                ->where('is_doctor', true)
                ->where('is_active', true)
                ->exists();


            if (! $validConsultant) {

                throw ValidationException::withMessages([
                    'consultant_id' =>
                        'The selected consultant is not an active doctor.',
                ]);
            }
        }


        $admission = DB::transaction(
            function () use (
                $validated,
                $emergencyVisit
            ) {

                /*
                |--------------------------------------------------------------------------
                | Lock Emergency Visit
                |--------------------------------------------------------------------------
                |
                | Prevents two users admitting the same Emergency visit at the
                | same time.
                |
                */

                $lockedEmergencyVisit =
                    EmergencyVisit::query()
                        ->whereKey(
                            $emergencyVisit->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                /*
                |--------------------------------------------------------------------------
                | Confirm Emergency visit is still eligible
                |--------------------------------------------------------------------------
                */

                if (
                    $lockedEmergencyVisit->admission_id
                    ||
                    $lockedEmergencyVisit->status === 'admitted'
                ) {
                    throw ValidationException::withMessages([
                        'bed_id' =>
                            'This Emergency visit has already been admitted.',
                    ]);
                }


                if (
                    in_array(
                        $lockedEmergencyVisit->status,
                        [
                            'discharged',
                            'referred',
                            'death',
                            'cancelled',
                        ],
                        true
                    )
                ) {
                    throw ValidationException::withMessages([
                        'bed_id' =>
                            'This Emergency visit is already closed and cannot be admitted.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Lock Selected Bed
                |--------------------------------------------------------------------------
                |
                | Prevents two users assigning the same bed simultaneously.
                |
                */

                $bed = Bed::query()
                    ->with('ward')
                    ->whereKey(
                        $validated['bed_id']
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


                /*
                |--------------------------------------------------------------------------
                | Verify Ward / Bed relationship
                |--------------------------------------------------------------------------
                */

                if (
                    (int) $bed->ward_id
                    !==
                    (int) $validated['ward_id']
                ) {
                    throw ValidationException::withMessages([
                        'bed_id' =>
                            'The selected bed does not belong to the selected ward.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Verify Bed is active
                |--------------------------------------------------------------------------
                */

                if (! $bed->is_active) {

                    throw ValidationException::withMessages([
                        'bed_id' =>
                            'The selected bed is inactive.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Verify Bed is available
                |--------------------------------------------------------------------------
                */

                if ($bed->status !== 'available') {

                    throw ValidationException::withMessages([
                        'bed_id' =>
                            'The selected bed is no longer available. Please choose another bed.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Check for existing active allocation
                |--------------------------------------------------------------------------
                */

                $activeAllocationExists =
                    BedAllocation::query()
                        ->where(
                            'bed_id',
                            $bed->id
                        )
                        ->where(
                            'status',
                            'active'
                        )
                        ->exists();


                if ($activeAllocationExists) {

                    throw ValidationException::withMessages([
                        'bed_id' =>
                            'The selected bed already has an active patient allocation.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Generate Admission Number
                |--------------------------------------------------------------------------
                |
                | Example:
                | IPD-20260915-000001
                |
                */

                DB::statement(
                    "SELECT pg_advisory_xact_lock(hashtext('ipd_admission_number'))"
                );


                $admittedAt =
                    Carbon::parse(
                        $validated['admitted_at']
                    );


                $date =
                    $admittedAt->format('Ymd');


                $prefix =
                    'IPD-' . $date . '-';


                $lastAdmissionNo =
                    Admission::query()
                        ->where(
                            'admission_no',
                            'like',
                            $prefix . '%'
                        )
                        ->orderByDesc(
                            'admission_no'
                        )
                        ->value(
                            'admission_no'
                        );


                $nextSequence = 1;


                if ($lastAdmissionNo) {

                    $lastSequence =
                        (int) substr(
                            $lastAdmissionNo,
                            -6
                        );


                    $nextSequence =
                        $lastSequence + 1;
                }


                $admissionNo =
                    $prefix
                    . str_pad(
                        (string) $nextSequence,
                        6,
                        '0',
                        STR_PAD_LEFT
                    );


                /*
                |--------------------------------------------------------------------------
                | Create Admission
                |--------------------------------------------------------------------------
                */

                $admission =
                    Admission::create([
                        'admission_no' =>
                            $admissionNo,

                        'patient_id' =>
                            $lockedEmergencyVisit->patient_id,

                        'source_type' =>
                            'emergency',

                        'source_id' =>
                            $lockedEmergencyVisit->id,

                        'admitted_at' =>
                            $admittedAt,

                        'department_id' =>
                            $validated['department_id'],

                        'consultant_id' =>
                            $validated['consultant_id']
                            ?? null,

                        'admission_type' =>
                            'emergency',

                        'admission_reason' =>
                            $validated['admission_reason']
                            ?? null,

                        'provisional_diagnosis' =>
                            $validated['provisional_diagnosis']
                            ?? null,

                        'bed_id' =>
                            $bed->id,

                        'status' =>
                            'admitted',

                        'discharged_at' =>
                            null,

                        'created_by' =>
                            auth()->id(),
                    ]);


                /*
                |--------------------------------------------------------------------------
                | Create Bed Allocation
                |--------------------------------------------------------------------------
                */

                BedAllocation::create([
                    'admission_id' =>
                        $admission->id,

                    'bed_id' =>
                        $bed->id,

                    'allocated_at' =>
                        $admittedAt,

                    'released_at' =>
                        null,

                    'status' =>
                        'active',

                    'allocation_type' =>
                        'admission',

                    'remarks' =>
                        'Initial bed allocation from Emergency admission.',

                    'allocated_by' =>
                        auth()->id(),

                    'released_by' =>
                        null,
                ]);


                /*
                |--------------------------------------------------------------------------
                | Mark Bed Occupied
                |--------------------------------------------------------------------------
                */

                $bed->update([
                    'status' =>
                        'occupied',
                ]);


                /*
                |--------------------------------------------------------------------------
                | Update Emergency Visit
                |--------------------------------------------------------------------------
                */

                $lockedEmergencyVisit->update([
                    'status' =>
                        'admitted',

                    'disposition' =>
                        'admitted',

                    'disposition_at' =>
                        now(),

                    'admission_id' =>
                        $admission->id,
                ]);


                return $admission;
            }
        );


        return redirect()
            ->route(
                'emergency.show',
                $emergencyVisit
            )
            ->with(
                'success',
                'Patient admitted successfully. Admission No: '
                . $admission->admission_no
            );
    }
}