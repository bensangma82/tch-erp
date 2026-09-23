<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveTypeController extends Controller
{
    /**
     * Display the Leave Type Master.
     */
    public function index(): View
    {
        $leaveTypes = LeaveType::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view(
            'admin.hr.leave-types.index',
            compact('leaveTypes')
        );
    }


    /**
     * Store a new leave type.
     */
    public function store(
        Request $request
    ): RedirectResponse {

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                'unique:leave_types,code',
            ],

            'name' => [
                'required',
                'string',
                'max:120',
            ],

            'default_annual_entitlement' => [
                'required',
                'numeric',
                'min:0',
                'max:366',
            ],

            'is_paid' => [
                'nullable',
                'boolean',
            ],

            'allow_carry_forward' => [
                'nullable',
                'boolean',
            ],

            'max_carry_forward' => [
                'nullable',
                'numeric',
                'min:0',
                'max:366',
            ],

            'requires_approval' => [
                'nullable',
                'boolean',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);


        $allowCarryForward =
            $request->boolean(
                'allow_carry_forward'
            );


        LeaveType::create([
            'code' => strtoupper(
                trim($validated['code'])
            ),

            'name' => trim(
                $validated['name']
            ),

            'default_annual_entitlement' =>
                $validated[
                    'default_annual_entitlement'
                ],

            'is_paid' =>
                $request->boolean(
                    'is_paid'
                ),

            'allow_carry_forward' =>
                $allowCarryForward,

            'max_carry_forward' =>
                $allowCarryForward
                    ? (
                        $validated[
                            'max_carry_forward'
                        ]
                        ?? 0
                    )
                    : 0,

            'requires_approval' =>
                $request->boolean(
                    'requires_approval'
                ),

            'is_active' =>
                $request->boolean(
                    'is_active'
                ),

            'sort_order' =>
                $validated[
                    'sort_order'
                ]
                ?? 0,

            'description' =>
                $validated[
                    'description'
                ]
                ?? null,
        ]);


        return redirect()
            ->route(
                'admin.hr.leave-types.index'
            )
            ->with(
                'success',
                'Leave type created successfully.'
            );
    }


    /**
     * Update an existing leave type.
     */
    public function update(
        Request $request,
        LeaveType $leaveType
    ): RedirectResponse {

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                'unique:leave_types,code,'
                    . $leaveType->id,
            ],

            'name' => [
                'required',
                'string',
                'max:120',
            ],

            'default_annual_entitlement' => [
                'required',
                'numeric',
                'min:0',
                'max:366',
            ],

            'is_paid' => [
                'nullable',
                'boolean',
            ],

            'allow_carry_forward' => [
                'nullable',
                'boolean',
            ],

            'max_carry_forward' => [
                'nullable',
                'numeric',
                'min:0',
                'max:366',
            ],

            'requires_approval' => [
                'nullable',
                'boolean',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:9999',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);


        $allowCarryForward =
            $request->boolean(
                'allow_carry_forward'
            );


        $leaveType->update([
            'code' => strtoupper(
                trim($validated['code'])
            ),

            'name' => trim(
                $validated['name']
            ),

            'default_annual_entitlement' =>
                $validated[
                    'default_annual_entitlement'
                ],

            'is_paid' =>
                $request->boolean(
                    'is_paid'
                ),

            'allow_carry_forward' =>
                $allowCarryForward,

            'max_carry_forward' =>
                $allowCarryForward
                    ? (
                        $validated[
                            'max_carry_forward'
                        ]
                        ?? 0
                    )
                    : 0,

            'requires_approval' =>
                $request->boolean(
                    'requires_approval'
                ),

            'is_active' =>
                $request->boolean(
                    'is_active'
                ),

            'sort_order' =>
                $validated[
                    'sort_order'
                ]
                ?? 0,

            'description' =>
                $validated[
                    'description'
                ]
                ?? null,
        ]);


        return redirect()
            ->route(
                'admin.hr.leave-types.index'
            )
            ->with(
                'success',
                'Leave type updated successfully.'
            );
    }
}
