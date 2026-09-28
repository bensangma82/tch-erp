<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AssetMovementController extends Controller
{
    public function create(Asset $asset): View
    {
        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $employees = Employee::query()
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('admin.assets.movements.create', compact(
            'asset',
            'departments',
            'employees'
        ));
    }

    public function store(
        Request $request,
        Asset $asset
    ): RedirectResponse {
        $validated = $request->validate([
            'movement_date' => [
                'required',
                'date',
            ],

            'movement_type' => [
                'required',
                'in:department_transfer,location_transfer,custodian_change,temporary_transfer,return,other',
            ],

            'to_department_id' => [
                'nullable',
                'exists:departments,id',
            ],

            'to_location' => [
                'nullable',
                'string',
                'max:200',
            ],

            'to_custodian_employee_id' => [
                'nullable',
                'exists:employees,id',
            ],

            'reason' => [
                'nullable',
                'string',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],
        ]);

                    $hasChanged =
    (int) ($validated['to_department_id'] ?? 0) !== (int) ($asset->department_id ?? 0)
    || trim((string) ($validated['to_location'] ?? '')) !== trim((string) ($asset->location ?? ''))
    || (int) ($validated['to_custodian_employee_id'] ?? 0) !== (int) ($asset->custodian_employee_id ?? 0);

if (! $hasChanged) {
    return back()
        ->withInput()
        ->withErrors([
            'movement' => 'No change was detected. Please change the department, location, or custodian before recording the movement.',
        ]);
}


        DB::transaction(function () use (
            $validated,
            $asset
        ) {
            AssetMovement::create([
                'asset_id' => $asset->id,

                'movement_date' => $validated['movement_date'],

                'from_department_id' => $asset->department_id,
                'to_department_id' => $validated['to_department_id'] ?? null,

                'from_location' => $asset->location,
                'to_location' => $validated['to_location'] ?? null,

                'from_custodian_employee_id' =>
                    $asset->custodian_employee_id,

                'to_custodian_employee_id' =>
                    $validated['to_custodian_employee_id'] ?? null,

                'movement_type' => $validated['movement_type'],

                'reason' => $validated['reason'] ?? null,
                'remarks' => $validated['remarks'] ?? null,

                'moved_by_user_id' => Auth::id(),
            ]);

            $asset->update([
                'department_id' =>
                    $validated['to_department_id'] ?? null,

                'location' =>
                    $validated['to_location'] ?? null,

                'custodian_employee_id' =>
                    $validated['to_custodian_employee_id'] ?? null,
            ]);
        });

        return redirect()
            ->route('admin.assets.show', $asset)
            ->with('success', 'Asset movement recorded successfully.');
    }
}