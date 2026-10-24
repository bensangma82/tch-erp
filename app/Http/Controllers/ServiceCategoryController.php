<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceCategoryController extends Controller
{
    public function index()
    {
        $categories = ServiceCategory::query()
            ->withCount('services')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view(
            'service-categories.index',
            compact('categories')
        );
    }

    public function create()
    {
        return view('service-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z][a-z0-9_]*$/',
                'unique:service_categories,code',
            ],
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:service_categories,name',
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $validated['is_active'] = true;
        $validated['is_system'] = false;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        ServiceCategory::create($validated);

        return redirect()
            ->route('service-categories.index')
            ->with('success', 'Service category created successfully.');
    }

    public function edit(ServiceCategory $serviceCategory)
    {
        return view(
            'service-categories.edit',
            compact('serviceCategory')
        );
    }

    public function update(
        Request $request,
        ServiceCategory $serviceCategory
    ) {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('service_categories', 'name')
                    ->ignore($serviceCategory->id),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $serviceCategory->update($validated);

        return redirect()
            ->route('service-categories.index')
            ->with('success', 'Service category updated successfully.');
    }

    public function toggleActive(ServiceCategory $serviceCategory)
    {
        if ($serviceCategory->is_system && $serviceCategory->is_active) {
            return back()->withErrors([
                'category' => 'System categories cannot be deactivated.',
            ]);
        }

        if ($serviceCategory->is_active) {
            $inUse = Service::query()
                ->where('category', $serviceCategory->code)
                ->exists();

            if ($inUse) {
                return back()->withErrors([
                    'category' => 'This category is used by existing services and cannot be deactivated.',
                ]);
            }
        }

        $serviceCategory->update([
            'is_active' => ! $serviceCategory->is_active,
        ]);

        return back()->with(
            'success',
            'Service category status updated successfully.'
        );
    }
}
