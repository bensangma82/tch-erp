<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetVendor;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    public function index(Request $request): View
    {
        $query = Asset::query()
            ->with([
                'category',
                'department',
                'custodian',
                'vendor',
            ]);

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('asset_code', 'ilike', "%{$search}%")
                    ->orWhere('asset_name', 'ilike', "%{$search}%")
                    ->orWhere('serial_number', 'ilike', "%{$search}%")
                    ->orWhere('manufacturer', 'ilike', "%{$search}%")
                    ->orWhere('model', 'ilike', "%{$search}%")
                    ->orWhere('location', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('asset_category_id')) {
            $query->where(
                'asset_category_id',
                $request->integer('asset_category_id')
            );
        }

        if ($request->filled('department_id')) {
            $query->where(
                'department_id',
                $request->integer('department_id')
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('criticality')) {
            $query->where('criticality', $request->criticality);
        }

        $assets = $query
            ->orderBy('asset_name')
            ->paginate(25)
            ->withQueryString();

        $categories = AssetCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.assets.index', compact(
            'assets',
            'categories',
            'departments'
        ));
    }

    public function create(): View
    {
        return view('admin.assets.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateAsset($request);

        $validated['asset_code'] =
            strtoupper(trim($validated['asset_code']));

        $validated['asset_name'] =
            trim($validated['asset_name']);

        $validated['manufacturer'] =
            $validated['manufacturer'] ?? null;

        $validated['model'] =
            $validated['model'] ?? null;

        $validated['serial_number'] =
            $validated['serial_number'] ?? null;

        $validated['requires_preventive_maintenance'] =
            $request->boolean('requires_preventive_maintenance');

        $validated['requires_calibration'] =
            $request->boolean('requires_calibration');

        $validated['is_active'] =
            $request->boolean('is_active');

        $asset = Asset::create($validated);

        return redirect()
            ->route('admin.assets.show', $asset)
            ->with('success', 'Asset registered successfully.');
    }

    public function show(Asset $asset): View
    {
        $asset->load([
    'category',
    'department',
    'custodian',
    'vendor',

    'movements.fromDepartment',
    'movements.toDepartment',
    'movements.fromCustodian',
    'movements.toCustodian',
    'movements.movedBy',
]);

        return view('admin.assets.show', compact('asset'));
    }

    public function edit(Asset $asset): View
    {
        return view(
            'admin.assets.edit',
            array_merge(
                ['asset' => $asset],
                $this->formData()
            )
        );
    }

    public function update(
        Request $request,
        Asset $asset
    ): RedirectResponse {
        $validated = $this->validateAsset(
            $request,
            $asset
        );

        $validated['asset_code'] =
            strtoupper(trim($validated['asset_code']));

        $validated['asset_name'] =
            trim($validated['asset_name']);

        $validated['requires_preventive_maintenance'] =
            $request->boolean('requires_preventive_maintenance');

        $validated['requires_calibration'] =
            $request->boolean('requires_calibration');

        $validated['is_active'] =
            $request->boolean('is_active');

        $asset->update($validated);

        return redirect()
            ->route('admin.assets.show', $asset)
            ->with('success', 'Asset updated successfully.');
    }

    private function formData(): array
    {
        return [
            'categories' => AssetCategory::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'departments' => Department::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),

            'employees' => Employee::query()
                ->where('is_active', true)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(),

            'vendors' => AssetVendor::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
        ];
    }

    private function validateAsset(
        Request $request,
        ?Asset $asset = null
    ): array {
        $assetCodeRule = Rule::unique('assets', 'asset_code');

if ($asset) {
    $assetCodeRule->ignore($asset->id);
}

return $request->validate([
            'asset_code' => [
    'required',
    'string',
    'max:50',
    $assetCodeRule,
],

            'asset_name' => [
                'required',
                'string',
                'max:200',
            ],

            'asset_category_id' => [
                'required',
                'exists:asset_categories,id',
            ],

            'department_id' => [
                'nullable',
                'exists:departments,id',
            ],

            'custodian_employee_id' => [
                'nullable',
                'exists:employees,id',
            ],

            'asset_vendor_id' => [
                'nullable',
                'exists:asset_vendors,id',
            ],

            'manufacturer' => [
                'nullable',
                'string',
                'max:150',
            ],

            'model' => [
                'nullable',
                'string',
                'max:150',
            ],

            'serial_number' => [
                'nullable',
                'string',
                'max:150',
            ],

            'purchase_date' => [
                'nullable',
                'date',
            ],

            'purchase_cost' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'invoice_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'purchase_order_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'installation_date' => [
                'nullable',
                'date',
            ],

            'commissioning_date' => [
                'nullable',
                'date',
            ],

            'warranty_start_date' => [
                'nullable',
                'date',
            ],

            'warranty_end_date' => [
                'nullable',
                'date',
                'after_or_equal:warranty_start_date',
            ],

            'location' => [
                'nullable',
                'string',
                'max:200',
            ],

            'status' => [
                'required',
                'in:active,under_maintenance,out_of_service,condemned,disposed,lost',
            ],

            'criticality' => [
                'required',
                'in:low,medium,high,critical',
            ],

            'useful_life_years' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],
        ]);
    }
}