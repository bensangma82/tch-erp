<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssetCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetCategoryController extends Controller
{
    public function index(): View
    {
        $categories = AssetCategory::query()
            ->orderBy('name')
            ->paginate(20);

        return view('admin.asset-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.asset-categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                'unique:asset_categories,code',
            ],
            'name' => [
                'required',
                'string',
                'max:150',
                'unique:asset_categories,name',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'asset_class' => [
                'required',
                'string',
                'in:biomedical,it,electrical,furniture,vehicle,building,general',
            ],
            'default_useful_life_years' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['name'] = trim($validated['name']);

        $validated['requires_preventive_maintenance'] =
            $request->boolean('requires_preventive_maintenance');

        $validated['requires_calibration'] =
            $request->boolean('requires_calibration');

        $validated['is_active'] =
            $request->boolean('is_active');

        AssetCategory::create($validated);

        return redirect()
            ->route('admin.asset-categories.index')
            ->with('success', 'Asset category created successfully.');
    }

    public function edit(AssetCategory $assetCategory): View
    {
        return view(
            'admin.asset-categories.edit',
            compact('assetCategory')
        );
    }

    public function update(
        Request $request,
        AssetCategory $assetCategory
    ): RedirectResponse {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:30',
                'unique:asset_categories,code,' . $assetCategory->id,
            ],
            'name' => [
                'required',
                'string',
                'max:150',
                'unique:asset_categories,name,' . $assetCategory->id,
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'asset_class' => [
                'required',
                'string',
                'in:biomedical,it,electrical,furniture,vehicle,building,general',
            ],
            'default_useful_life_years' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['name'] = trim($validated['name']);

        $validated['requires_preventive_maintenance'] =
            $request->boolean('requires_preventive_maintenance');

        $validated['requires_calibration'] =
            $request->boolean('requires_calibration');

        $validated['is_active'] =
            $request->boolean('is_active');

        $assetCategory->update($validated);

        return redirect()
            ->route('admin.asset-categories.index')
            ->with('success', 'Asset category updated successfully.');
    }
}