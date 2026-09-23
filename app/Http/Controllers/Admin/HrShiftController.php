<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HrShift;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HrShiftController extends Controller
{
    /**
     * Display Shift Master.
     */
    public function index(): View
    {
        $shifts = HrShift::query()
            ->orderBy('sort_order')
            ->orderBy('start_time')
            ->orderBy('name')
            ->get();

        return view(
            'admin.hr.shifts.index',
            compact('shifts')
        );
    }

    /**
     * Store a new shift.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                'alpha_dash',
                'unique:hr_shifts,code',
            ],

            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            'end_time' => [
                'required',
                'date_format:H:i',
            ],

            'grace_minutes' => [
                'required',
                'integer',
                'min:0',
                'max:180',
            ],

            'minimum_work_minutes' => [
                'nullable',
                'integer',
                'min:1',
                'max:1440',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];

        HrShift::create([
            'code' => Str::upper(trim($validated['code'])),

            'name' => trim($validated['name']),

            'start_time' => $startTime,

            'end_time' => $endTime,

            'grace_minutes' =>
                $validated['grace_minutes'],

            'minimum_work_minutes' =>
                $validated['minimum_work_minutes'] ?? null,

            /*
             * End time earlier than or equal to start time means
             * the shift finishes on the following calendar day.
             *
             * Example:
             * 20:00 → 08:00 = night shift.
             */
            'crosses_midnight' =>
                $endTime <= $startTime,

            'is_active' => true,

            'sort_order' =>
                $validated['sort_order'] ?? 0,

            'remarks' =>
                $validated['remarks'] ?? null,
        ]);

        return redirect()
            ->route('admin.hr.shifts.index')
            ->with(
                'success',
                'Shift created successfully.'
            );
    }

    /**
     * Update an existing shift.
     */
    public function update(
        Request $request,
        HrShift $shift
    ): RedirectResponse {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                'alpha_dash',

                Rule::unique(
                    'hr_shifts',
                    'code'
                )->ignore($shift->id),
            ],

            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            'end_time' => [
                'required',
                'date_format:H:i',
            ],

            'grace_minutes' => [
                'required',
                'integer',
                'min:0',
                'max:180',
            ],

            'minimum_work_minutes' => [
                'nullable',
                'integer',
                'min:1',
                'max:1440',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];

        $shift->update([
            'code' =>
                Str::upper(
                    trim($validated['code'])
                ),

            'name' =>
                trim($validated['name']),

            'start_time' =>
                $startTime,

            'end_time' =>
                $endTime,

            'grace_minutes' =>
                $validated['grace_minutes'],

            'minimum_work_minutes' =>
                $validated['minimum_work_minutes'] ?? null,

            'crosses_midnight' =>
                $endTime <= $startTime,

            'sort_order' =>
                $validated['sort_order'] ?? 0,

            'remarks' =>
                $validated['remarks'] ?? null,
        ]);

        return redirect()
            ->route('admin.hr.shifts.index')
            ->with(
                'success',
                'Shift updated successfully.'
            );
    }

    /**
     * Activate or deactivate a shift.
     *
     * We do not delete shifts because historical rosters and
     * attendance records may eventually reference them.
     */
    public function toggleStatus(
        HrShift $shift
    ): RedirectResponse {
        $shift->update([
            'is_active' => ! $shift->is_active,
        ]);

        $status = $shift->is_active
            ? 'activated'
            : 'deactivated';

        return redirect()
            ->route('admin.hr.shifts.index')
            ->with(
                'success',
                "Shift {$status} successfully."
            );
    }
}