<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Display service master.
     */
    public function index(Request $request)
{
    $query = Service::with('department');

    $scope = trim(
        (string) $request->query('scope', '')
    );


    /*
    |--------------------------------------------------------------------------
    | Charge Master Scope
    |--------------------------------------------------------------------------
    |
    | Charge Master shows only services that can be added directly to an
    | inpatient running bill.
    |
    | Laboratory and Radiology remain under the diagnostic workflow.
    |
    */

    if ($scope === 'charges') {

        $query->whereIn(
            'category',
            [
                'procedure',
                'consultation',
                'nursing',
                'equipment',
                'consumable',
                'facility',
                'other',
            ]
        );
    }


    if ($request->filled('search')) {

        $search = trim(
            $request->search
        );

        $query->where(
            function ($q) use ($search) {

                $q->where(
                    'code',
                    'ilike',
                    "%{$search}%"
                )
                    ->orWhere(
                        'name',
                        'ilike',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'category',
                        'ilike',
                        "%{$search}%"
                    );

            }
        );
    }


    if ($request->filled('category')) {

        $query->where(
            'category',
            $request->category
        );
    }


    $services = $query
        ->orderBy('category')
        ->orderBy('name')
        ->paginate(25)
        ->withQueryString();


    return view(
        'services.index',
        compact(
            'services',
            'scope'
        )
    );
}

    /**
     * Show create form.
     */
    public function create()
    {
        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'services.create',
            compact('departments')
        );
    }


    /**
     * Store service.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([

            'code' => [
                'required',
                'string',
                'max:50',
                'unique:services,code',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'category' => [
                'required',
                'in:laboratory,radiology,procedure,consultation,other',
            ],

            'department_id' => [
                'nullable',
                'exists:departments,id',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
            ],

            'unit' => [
                'nullable',
                'string',
                'max:100',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'requires_sample' => [
                'nullable',
                'boolean',
            ],

            'requires_report' => [
                'nullable',
                'boolean',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);


        $validated['code'] =
            strtoupper(
                trim($validated['code'])
            );

        $validated['name'] =
            trim($validated['name']);

        $validated['requires_sample'] =
            $request->boolean('requires_sample');

        $validated['requires_report'] =
            $request->boolean('requires_report');

        $validated['is_active'] =
            $request->boolean('is_active');


        Service::create(
            $validated
        );


        return redirect()
            ->route('services.index')
            ->with(
                'success',
                'Service created successfully.'
            );
    }


    /**
     * Show edit form.
     */
    public function edit(Service $service)
    {
        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'services.edit',
            compact(
                'service',
                'departments'
            )
        );
    }


    /**
     * Update service.
     */
    public function update(
        Request $request,
        Service $service
    ) {
        $validated = $request->validate([

            'code' => [
                'required',
                'string',
                'max:50',
                'unique:services,code,' . $service->id,
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'category' => [
                'required',
                'in:laboratory,radiology,procedure,consultation,other',
            ],

            'department_id' => [
                'nullable',
                'exists:departments,id',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
            ],

            'unit' => [
                'nullable',
                'string',
                'max:100',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);


        $validated['code'] =
            strtoupper(
                trim($validated['code'])
            );

        $validated['name'] =
            trim($validated['name']);

        $validated['requires_sample'] =
            $request->boolean('requires_sample');

        $validated['requires_report'] =
            $request->boolean('requires_report');

        $validated['is_active'] =
            $request->boolean('is_active');


        $service->update(
            $validated
        );


        return redirect()
            ->route('services.index')
            ->with(
                'success',
                'Service updated successfully.'
            );
    }
}