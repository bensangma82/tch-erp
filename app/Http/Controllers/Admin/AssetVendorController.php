<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssetVendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetVendorController extends Controller
{
    public function index(): View
    {
        $vendors = AssetVendor::query()
            ->orderBy('name')
            ->paginate(20);

        return view('admin.asset-vendors.index', compact('vendors'));
    }

    public function create(): View
    {
        return view('admin.asset-vendors.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                'unique:asset_vendors,code',
            ],
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'contact_person' => [
                'nullable',
                'string',
                'max:150',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],
            'alternate_phone' => [
                'nullable',
                'string',
                'max:30',
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
            ],
            'address' => [
                'nullable',
                'string',
            ],
            'city' => [
                'nullable',
                'string',
                'max:100',
            ],
            'district' => [
                'nullable',
                'string',
                'max:100',
            ],
            'state' => [
                'nullable',
                'string',
                'max:100',
            ],
            'pin_code' => [
                'nullable',
                'string',
                'max:20',
            ],
            'gstin' => [
                'nullable',
                'string',
                'max:30',
            ],
            'pan_no' => [
                'nullable',
                'string',
                'max:30',
            ],
            'remarks' => [
                'nullable',
                'string',
            ],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['name'] = trim($validated['name']);

        $validated['provides_sales'] =
            $request->boolean('provides_sales');

        $validated['provides_service'] =
            $request->boolean('provides_service');

        $validated['provides_amc_cmc'] =
            $request->boolean('provides_amc_cmc');

        $validated['provides_calibration'] =
            $request->boolean('provides_calibration');

        $validated['is_active'] =
            $request->boolean('is_active');

        AssetVendor::create($validated);

        return redirect()
            ->route('admin.asset-vendors.index')
            ->with('success', 'Asset vendor created successfully.');
    }

    public function edit(AssetVendor $assetVendor): View
    {
        return view(
            'admin.asset-vendors.edit',
            compact('assetVendor')
        );
    }

    public function update(
        Request $request,
        AssetVendor $assetVendor
    ): RedirectResponse {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                'unique:asset_vendors,code,' . $assetVendor->id,
            ],
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'contact_person' => [
                'nullable',
                'string',
                'max:150',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],
            'alternate_phone' => [
                'nullable',
                'string',
                'max:30',
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
            ],
            'address' => [
                'nullable',
                'string',
            ],
            'city' => [
                'nullable',
                'string',
                'max:100',
            ],
            'district' => [
                'nullable',
                'string',
                'max:100',
            ],
            'state' => [
                'nullable',
                'string',
                'max:100',
            ],
            'pin_code' => [
                'nullable',
                'string',
                'max:20',
            ],
            'gstin' => [
                'nullable',
                'string',
                'max:30',
            ],
            'pan_no' => [
                'nullable',
                'string',
                'max:30',
            ],
            'remarks' => [
                'nullable',
                'string',
            ],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['name'] = trim($validated['name']);

        $validated['provides_sales'] =
            $request->boolean('provides_sales');

        $validated['provides_service'] =
            $request->boolean('provides_service');

        $validated['provides_amc_cmc'] =
            $request->boolean('provides_amc_cmc');

        $validated['provides_calibration'] =
            $request->boolean('provides_calibration');

        $validated['is_active'] =
            $request->boolean('is_active');

        $assetVendor->update($validated);

        return redirect()
            ->route('admin.asset-vendors.index')
            ->with('success', 'Asset vendor updated successfully.');
    }
}