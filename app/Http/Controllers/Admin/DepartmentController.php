<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    /**
     * Display department master.
     */
    public function index()
    {
        $departments = Department::query()
            ->orderBy('name')
            ->paginate(25);

        return view(
            'admin.departments.index',
            compact('departments')
        );
    }


    /**
     * Show department creation form.
     */
    public function create()
    {
        return view(
            'admin.departments.create'
        );
    }


    /**
     * Store new department.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:departments,code',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                'unique:departments,name',
            ],

            'type' => [
                'required',
                Rule::in([
                    'clinical',
                    'diagnostic',
                    'support',
                    'administrative',
                ]),
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);


        Department::create([
            'code' =>
                strtoupper(
                    trim(
                        $validated['code']
                    )
                ),

            'name' =>
                trim(
                    $validated['name']
                ),

            'type' =>
                $validated['type'],

            'is_active' =>
                (bool) $validated['is_active'],

            'remarks' =>
                $validated['remarks']
                ?? null,
        ]);


        return redirect()
            ->route(
                'admin.departments.index'
            )
            ->with(
                'success',
                'Department created successfully.'
            );
    }


    /**
     * Show department edit form.
     */
    public function edit(
        Department $department
    ) {
        return view(
            'admin.departments.edit',
            compact('department')
        );
    }


    /**
     * Update department.
     */
    public function update(
        Request $request,
        Department $department
    ) {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique(
                    'departments',
                    'code'
                )->ignore(
                    $department->id
                ),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(
                    'departments',
                    'name'
                )->ignore(
                    $department->id
                ),
            ],

            'type' => [
                'required',
                Rule::in([
                    'clinical',
                    'diagnostic',
                    'support',
                    'administrative',
                ]),
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);


        $department->update([
            'code' =>
                strtoupper(
                    trim(
                        $validated['code']
                    )
                ),

            'name' =>
                trim(
                    $validated['name']
                ),

            'type' =>
                $validated['type'],

            'is_active' =>
                (bool) $validated['is_active'],

            'remarks' =>
                $validated['remarks']
                ?? null,
        ]);


        return redirect()
            ->route(
                'admin.departments.index'
            )
            ->with(
                'success',
                'Department updated successfully.'
            );
    }
}