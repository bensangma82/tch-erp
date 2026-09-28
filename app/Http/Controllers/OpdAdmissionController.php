<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\IpBillingAccount;
use App\Models\Bed;
use App\Models\BedAllocation;
use App\Models\Department;
use App\Models\Encounter;
use App\Models\Employee;
use App\Models\Ward;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OpdAdmissionController extends Controller
{
    /**
     * Show the OPD -> IPD admission form.
     */
    public function create(
        Encounter $encounter
    ): View {

        $encounter->load([
            'patient',
            'department',
            'doctor',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate admission
        |--------------------------------------------------------------------------
        */

        $existingAdmission = Admission::query()
            ->where('source_type', 'opd')
            ->where('source_id', $encounter->id)
            ->first();

        if ($existingAdmission) {
            abort(
                409,
                'This OPD encounter has already been admitted.'
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
            'opd.admission.create',
            compact(
                'encounter',
                'departments',
                'consultants',
                'wards',
                'availableBeds'
            )
        );
    }

    /**
     * Admit an OPD patient to IPD.
     */
    public function store(
        Request $request,
        Encounter $encounter
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
                $encounter
            ) {

                /*
                |--------------------------------------------------------------------------
                | Lock OPD Encounter
                |--------------------------------------------------------------------------
                |
                | Prevents two users admitting the same OPD encounter at the
                | same time.
                |
                */

                $lockedEncounter = Encounter::query()
                    ->whereKey(
                        $encounter->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

                /*
                |--------------------------------------------------------------------------
                | Prevent duplicate OPD admission
                |--------------------------------------------------------------------------
                */

                $existingAdmission = Admission::query()
                    ->where(
                        'source_type',
                        'opd'
                    )
                    ->where(
                        'source_id',
                        $lockedEncounter->id
                    )
                    ->exists();

                if ($existingAdmission) {
                    throw ValidationException::withMessages([
                        'bed_id' =>
                            'This OPD encounter has already been admitted.',
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

                $activeAllocationExists = BedAllocation::query()
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
                | IPD-20260928-000001
                |
                */

                DB::statement(
                    "SELECT pg_advisory_xact_lock(hashtext('ipd_admission_number'))"
                );

                $admittedAt = Carbon::parse(
                    $validated['admitted_at']
                );

                $date = $admittedAt->format('Ymd');

                $prefix = 'IPD-' . $date . '-';

                $lastAdmissionNo = Admission::query()
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

                    $lastSequence = (int) substr(
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

                $admission = Admission::create([
                    'admission_no' =>
                        $admissionNo,

                    'patient_id' =>
                        $lockedEncounter->patient_id,

                    'source_type' =>
                        'opd',

                    'source_id' =>
                        $lockedEncounter->id,

                    'admitted_at' =>
                        $admittedAt,

                    'department_id' =>
                        $validated['department_id'],

                    'consultant_id' =>
                        $validated['consultant_id']
                        ?? null,

                    'admission_type' =>
                        'opd',

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
                | Create IP Billing Account
                |--------------------------------------------------------------------------
                */

                IpBillingAccount::firstOrCreate(
                    [
                        'admission_id' =>
                            $admission->id,
                    ],
                    [
                        'patient_id' =>
                            $admission->patient_id,

                        'account_no' =>
                            'IPB-'
                            . now()->format('Ymd')
                            . '-'
                            . str_pad(
                                (string) $admission->id,
                                6,
                                '0',
                                STR_PAD_LEFT
                            ),

                        'opened_at' =>
                            now(),

                        'status' =>
                            'open',

                        'subtotal' =>
                            0,

                        'discount_amount' =>
                            0,

                        'net_amount' =>
                            0,

                        'advance_amount' =>
                            0,

                        'paid_amount' =>
                            0,

                        'balance_amount' =>
                            0,

                        'created_by' =>
                            auth()->id(),
                    ]
                );

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
                        'Initial bed allocation from OPD admission.',

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

                return $admission;
            }
        );

        return redirect()
            ->route(
                'ipd.show',
                $admission
            )
            ->with(
                'success',
                'Patient admitted successfully from OPD. Admission No: '
                . $admission->admission_no
            );
    }
}