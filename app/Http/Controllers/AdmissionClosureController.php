<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Bed;
use App\Models\BedAllocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdmissionClosureController extends Controller
{
    /**
     * Show the IPD closure form.
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


        if ($admission->status !== 'admitted') {

            abort(
                409,
                'Only currently admitted patients can be closed.'
            );
        }


        $currentBed =
            $admission->currentBedAllocation?->bed
            ??
            $admission->bed;


        return view(
            'ipd.closure.create',
            compact(
                'admission',
                'currentBed'
            )
        );
    }


    /**
     * Close the IPD admission.
     */
    public function store(
        Request $request,
        Admission $admission
    ): RedirectResponse {

        $validated = $request->validate([
            'closure_type' => [
                'required',
                'in:discharged,referred,death',
            ],

            'closed_at' => [
                'required',
                'date',
            ],

            'closure_notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'referral_destination' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);


        if (
            $validated['closure_type'] === 'referred'
            &&
            empty(
                trim(
                    $validated['referral_destination']
                    ?? ''
                )
            )
        ) {

            throw ValidationException::withMessages([
                'referral_destination' =>
                    'Referral destination is required when closing the admission as referred.',
            ]);
        }


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
                | Admission must still be active
                |--------------------------------------------------------------------------
                */

                if (
                    $lockedAdmission->status
                    !==
                    'admitted'
                ) {

                    throw ValidationException::withMessages([
                        'closure_type' =>
                            'This admission is no longer active.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Lock current bed allocation
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


                /*
                |--------------------------------------------------------------------------
                | Lock current bed
                |--------------------------------------------------------------------------
                */

                $currentBed = null;


                if ($currentAllocation) {

                    $currentBed =
                        Bed::query()
                            ->whereKey(
                                $currentAllocation->bed_id
                            )
                            ->lockForUpdate()
                            ->first();
                }


                /*
                |--------------------------------------------------------------------------
                | Close active bed allocation
                |--------------------------------------------------------------------------
                */

                if ($currentAllocation) {

                    $currentAllocation->update([
                        'released_at' =>
                            $validated['closed_at'],

                        'status' =>
                            'released',

                        'released_by' =>
                            auth()->id(),
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Release bed
                |--------------------------------------------------------------------------
                */

                if ($currentBed) {

                    $currentBed->update([
                        'status' =>
                            'available',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Close admission
                |--------------------------------------------------------------------------
                */

                $updateData = [
                    'status' =>
                        $validated['closure_type'],

                    'closed_at' =>
                        $validated['closed_at'],

                    'closure_notes' =>
                        $validated['closure_notes']
                        ?? null,

                    'referral_destination' =>
                        $validated['closure_type'] === 'referred'
                            ? (
                                $validated['referral_destination']
                                ?? null
                            )
                            : null,

                    'closed_by' =>
                        auth()->id(),
                ];


                /*
                |--------------------------------------------------------------------------
                | discharged_at is specifically for discharged patients
                |--------------------------------------------------------------------------
                */

                if (
                    $validated['closure_type']
                    ===
                    'discharged'
                ) {

                    $updateData['discharged_at'] =
                        $validated['closed_at'];

                } else {

                    $updateData['discharged_at'] =
                        null;
                }


                $lockedAdmission->update(
                    $updateData
                );
            }
        );


        return redirect()
            ->route(
                'ipd.show',
                $admission
            )
            ->with(
                'success',
                'Admission closed successfully.'
            );
    }
}