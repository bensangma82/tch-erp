<?php

namespace App\Http\Controllers;

use App\Models\Bed;
use App\Models\Ward;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BedManagementController extends Controller
{
    /**
     * Operational bed-management dashboard.
     */
    public function index(Request $request): View
    {
        $query = Bed::query()
            ->with([
                'ward',
                'room',
                'activeAllocation.admission.patient',
                'activeAllocation.admission.consultant',
            ])
            ->where('is_active', true);

        /*
        |--------------------------------------------------------------------------
        | Filters
        |--------------------------------------------------------------------------
        */

        if ($request->filled('ward_id')) {
            $query->where(
                'ward_id',
                $request->integer('ward_id')
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->string('status')->toString()
            );
        }

        if ($request->filled('search')) {
            $search = trim(
                $request->string('search')->toString()
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'bed_number',
                    'ilike',
                    "%{$search}%"
                )
                ->orWhereHas(
                    'activeAllocation.admission.patient',
                    function ($patientQuery) use ($search) {

                        $patientQuery
                            ->where(
                                'name',
                                'ilike',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'uhid',
                                'ilike',
                                "%{$search}%"
                            );
                    }
                );
            });
        }

        $beds = $query
            ->orderBy('ward_id')
            ->orderBy('room_id')
            ->orderBy('bed_number')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Summary counts
        |--------------------------------------------------------------------------
        */

        $summary = [
            'total' =>
                Bed::query()
                    ->where('is_active', true)
                    ->count(),

            'available' =>
                Bed::query()
                    ->where('is_active', true)
                    ->where('status', 'available')
                    ->count(),

            'occupied' =>
                Bed::query()
                    ->where('is_active', true)
                    ->where('status', 'occupied')
                    ->count(),

            'cleaning' =>
                Bed::query()
                    ->where('is_active', true)
                    ->where('status', 'cleaning')
                    ->count(),

            'reserved' =>
                Bed::query()
                    ->where('is_active', true)
                    ->where('status', 'reserved')
                    ->count(),

            'maintenance' =>
                Bed::query()
                    ->where('is_active', true)
                    ->where('status', 'maintenance')
                    ->count(),
        ];

        $wards = Ward::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'ipd.bed-management.index',
            compact(
                'beds',
                'summary',
                'wards'
            )
        );
    }


    /**
     * Change operational status of a vacant bed.
     */
    public function updateStatus(
        Request $request,
        Bed $bed
    ): RedirectResponse {

        $validated = $request->validate([
            'status' => [
                'required',
                'in:available,cleaning,reserved,maintenance',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Occupied beds are controlled only by admission / transfer / discharge
        |--------------------------------------------------------------------------
        */

        if ($bed->activeAllocation) {

            throw ValidationException::withMessages([
                'status' =>
                    'This bed currently has an admitted patient and its status cannot be changed manually.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Never manually set occupied
        |--------------------------------------------------------------------------
        */

        if ($bed->status === 'occupied') {

            $bed->refresh();

            if ($bed->activeAllocation) {
                throw ValidationException::withMessages([
                    'status' =>
                        'This bed is currently occupied.',
                ]);
            }
        }

        $bed->update([
            'status' =>
                $validated['status'],
        ]);

        return back()->with(
            'success',
            'Bed status updated successfully.'
        );
    }
}