<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Bed;
use App\Models\BedAllocation;
use App\Models\Ward;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BedTransferController extends Controller
{
    /**
     * Show bed transfer form.
     */
    public function create(
        Admission $admission
    ): View {

        $admission->load([
            'patient',
            'department',
            'consultant',
            'bed.ward',
            'currentBedAllocation.bed.ward',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Only active admissions can be transferred
        |--------------------------------------------------------------------------
        */

        if ($admission->status !== 'admitted') {

            abort(
                409,
                'Only currently admitted patients can be transferred.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Current bed
        |--------------------------------------------------------------------------
        */

        $currentBed =
            $admission->currentBedAllocation?->bed
            ??
            $admission->bed;


        /*
        |--------------------------------------------------------------------------
        | Active wards
        |--------------------------------------------------------------------------
        */

        $wards = Ward::query()
            ->where(
                'is_active',
                true
            )
            ->orderBy('name')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Available beds
        |--------------------------------------------------------------------------
        */

        $availableBeds = Bed::query()
            ->with('ward')
            ->where(
                'is_active',
                true
            )
            ->where(
                'status',
                'available'
            )
            ->when(
                $currentBed,
                function ($query) use ($currentBed) {

                    $query->where(
                        'id',
                        '!=',
                        $currentBed->id
                    );

                }
            )
            ->orderBy('ward_id')
            ->orderBy('bed_number')
            ->get();


        return view(
            'ipd.transfer.create',
            compact(
                'admission',
                'currentBed',
                'wards',
                'availableBeds'
            )
        );
    }


    /**
     * Transfer an admitted patient to another bed.
     */
    public function store(
        Request $request,
        Admission $admission
    ): RedirectResponse {

        $validated = $request->validate([
            'transferred_at' => [
                'required',
                'date',
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

            'remarks' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);


        DB::transaction(
            function () use (
                $validated,
                $admission
            ) {

                /*
                |--------------------------------------------------------------------------
                | Lock admission
                |--------------------------------------------------------------------------
                */

                $lockedAdmission =
                    Admission::query()
                        ->whereKey(
                            $admission->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                /*
                |--------------------------------------------------------------------------
                | Confirm patient is still admitted
                |--------------------------------------------------------------------------
                */

                if (
                    $lockedAdmission->status
                    !==
                    'admitted'
                ) {

                    throw ValidationException::withMessages([
                        'bed_id' =>
                            'This admission is no longer active and cannot be transferred.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Lock current active bed allocation
                |--------------------------------------------------------------------------
                */

                $currentAllocation =
                    BedAllocation::query()
                        ->where(
                            'admission_id',
                            $lockedAdmission->id
                        )
                        ->where(
                            'status',
                            'active'
                        )
                        ->lockForUpdate()
                        ->orderByDesc(
                            'allocated_at'
                        )
                        ->first();


                if (! $currentAllocation) {

                    throw ValidationException::withMessages([
                        'bed_id' =>
                            'No active bed allocation was found for this admission.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Prevent same-bed transfer
                |--------------------------------------------------------------------------
                */

                if (
                    (int) $currentAllocation->bed_id
                    ===
                    (int) $validated['bed_id']
                ) {

                    throw ValidationException::withMessages([
                        'bed_id' =>
                            'The patient is already allocated to this bed.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Lock old and new beds
                |--------------------------------------------------------------------------
                |
                | Locking in ID order reduces deadlock risk when multiple
                | transfers are processed at the same time.
                |
                */

                $bedIds = [
                    (int) $currentAllocation->bed_id,
                    (int) $validated['bed_id'],
                ];

                sort($bedIds);


                $lockedBeds =
                    Bed::query()
                        ->whereIn(
                            'id',
                            $bedIds
                        )
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get()
                        ->keyBy('id');


                $oldBed =
                    $lockedBeds->get(
                        $currentAllocation->bed_id
                    );


                $newBed =
                    $lockedBeds->get(
                        $validated['bed_id']
                    );


                if (! $oldBed) {

                    throw ValidationException::withMessages([
                        'bed_id' =>
                            'The current bed could not be found.',
                    ]);
                }


                if (! $newBed) {

                    throw ValidationException::withMessages([
                        'bed_id' =>
                            'The selected destination bed could not be found.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Verify ward / bed relationship
                |--------------------------------------------------------------------------
                */

                if (
                    (int) $newBed->ward_id
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
                | Destination bed must be active
                |--------------------------------------------------------------------------
                */

                if (! $newBed->is_active) {

                    throw ValidationException::withMessages([
                        'bed_id' =>
                            'The selected destination bed is inactive.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Destination bed must still be available
                |--------------------------------------------------------------------------
                */

                if (
                    $newBed->status
                    !==
                    'available'
                ) {

                    throw ValidationException::withMessages([
                        'bed_id' =>
                            'The selected destination bed is no longer available. Please choose another bed.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Check target bed has no active allocation
                |--------------------------------------------------------------------------
                */

                $targetAllocationExists =
                    BedAllocation::query()
                        ->where(
                            'bed_id',
                            $newBed->id
                        )
                        ->where(
                            'status',
                            'active'
                        )
                        ->exists();


                if ($targetAllocationExists) {

                    throw ValidationException::withMessages([
                        'bed_id' =>
                            'The selected destination bed already has an active patient allocation.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Transfer date/time
                |--------------------------------------------------------------------------
                */

                $transferredAt =
                    Carbon::parse(
                        $validated['transferred_at']
                    );


                /*
                |--------------------------------------------------------------------------
                | Close current allocation
                |--------------------------------------------------------------------------
                */

                $currentAllocation->update([
                    'released_at' =>
                        $transferredAt,

                    'status' =>
                        'released',

                    'released_by' =>
                        auth()->id(),
                ]);


                /*
                |--------------------------------------------------------------------------
                | Release old bed
                |--------------------------------------------------------------------------
                */

                $oldBed->update([
                    'status' =>
                        'available',
                ]);


                /*
                |--------------------------------------------------------------------------
                | Create destination allocation
                |--------------------------------------------------------------------------
                */

                BedAllocation::create([
                    'admission_id' =>
                        $lockedAdmission->id,

                    'bed_id' =>
                        $newBed->id,

                    'allocated_at' =>
                        $transferredAt,

                    'released_at' =>
                        null,

                    'status' =>
                        'active',

                    'allocation_type' =>
                        'transfer',

                    'remarks' =>
                        $validated['remarks']
                        ?? 'IPD bed transfer.',

                    'allocated_by' =>
                        auth()->id(),

                    'released_by' =>
                        null,
                ]);


                /*
                |--------------------------------------------------------------------------
                | Occupy destination bed
                |--------------------------------------------------------------------------
                */

                $newBed->update([
                    'status' =>
                        'occupied',
                ]);


                /*
                |--------------------------------------------------------------------------
                | Update admission's current bed
                |--------------------------------------------------------------------------
                |
                | Admission remains "admitted".
                | Transfer history is recorded in bed_allocations.
                |
                */

                $lockedAdmission->update([
                    'bed_id' =>
                        $newBed->id,
                ]);
            }
        );


        return redirect()
            ->route(
                'ipd.show',
                $admission
            )
            ->with(
                'success',
                'Patient transferred to the new bed successfully.'
            );
    }
}