<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\SalaryComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SalaryComponentController extends Controller
{
    /**
     * Display all salary components.
     */
    public function index(): View
    {
        $components = SalaryComponent::query()
            ->with('percentageOfComponent')
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view(
            'admin.hr.payroll.salary-components.index',
            compact('components')
        );
    }

    /**
     * Show the form for creating a salary component.
     */
    public function create(): View
    {
        $baseComponents = SalaryComponent::query()
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view(
            'admin.hr.payroll.salary-components.create',
            compact('baseComponents')
        );
    }

    /**
     * Store a new salary component.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateComponent($request);

        $validated['code'] = strtoupper(
            trim($validated['code'])
        );

        $validated['name'] = trim(
            $validated['name']
        );

        $validated['is_taxable'] =
            $request->boolean('is_taxable');

        $validated['affects_gross'] =
            $request->boolean('affects_gross');

        $validated['is_statutory'] =
            $request->boolean('is_statutory');

        $validated['is_recurring'] =
            $request->boolean('is_recurring');

        $validated['is_active'] =
            $request->boolean('is_active');

        if ($validated['calculation_type'] === 'fixed') {
            $validated['percentage_of_component_id'] = null;
            $validated['default_percentage'] = null;
        } else {
            $validated['default_amount'] = null;
        }

        $validated['sort_order'] =
            $validated['sort_order'] ?? 0;

        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        SalaryComponent::create($validated);

        return redirect()
            ->route(
                'admin.hr.payroll.salary-components.index'
            )
            ->with(
                'success',
                'Salary component created successfully.'
            );
    }

    /**
     * Show the form for editing a salary component.
     */
    public function edit(
        SalaryComponent $salaryComponent
    ): View {
        $baseComponents = SalaryComponent::query()
            ->where('id', '!=', $salaryComponent->id)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view(
            'admin.hr.payroll.salary-components.edit',
            compact(
                'salaryComponent',
                'baseComponents'
            )
        );
    }

    /**
     * Update an existing salary component.
     */
    public function update(
        Request $request,
        SalaryComponent $salaryComponent
    ): RedirectResponse {
        $validated = $this->validateComponent(
            $request,
            $salaryComponent
        );

        $validated['code'] = strtoupper(
            trim($validated['code'])
        );

        $validated['name'] = trim(
            $validated['name']
        );

        $validated['is_taxable'] =
            $request->boolean('is_taxable');

        $validated['affects_gross'] =
            $request->boolean('affects_gross');

        $validated['is_statutory'] =
            $request->boolean('is_statutory');

        $validated['is_recurring'] =
            $request->boolean('is_recurring');

        $validated['is_active'] =
            $request->boolean('is_active');

        if ($validated['calculation_type'] === 'fixed') {
            $validated['percentage_of_component_id'] = null;
            $validated['default_percentage'] = null;
        } else {
            $validated['default_amount'] = null;
        }

        $validated['sort_order'] =
            $validated['sort_order'] ?? 0;

        $validated['updated_by'] = auth()->id();

        $salaryComponent->update($validated);

        return redirect()
            ->route(
                'admin.hr.payroll.salary-components.index'
            )
            ->with(
                'success',
                'Salary component updated successfully.'
            );
    }

    /**
     * Activate or deactivate a salary component.
     *
     * We do not delete salary components because they may already
     * be referenced by salary structures or historical payroll.
     */
    public function toggleStatus(
        SalaryComponent $salaryComponent
    ): RedirectResponse {
        $salaryComponent->update([
            'is_active' => ! $salaryComponent->is_active,
            'updated_by' => auth()->id(),
        ]);

        $message = $salaryComponent->is_active
            ? 'Salary component activated successfully.'
            : 'Salary component deactivated successfully.';

        return redirect()
            ->route(
                'admin.hr.payroll.salary-components.index'
            )
            ->with('success', $message);
    }

    /**
     * Validate salary component data.
     */
    private function validateComponent(
        Request $request,
        ?SalaryComponent $salaryComponent = null
    ): array {
        $componentId = $salaryComponent?->id;

        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',

                Rule::unique(
                    'salary_components',
                    'code'
                )->ignore($componentId),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'type' => [
                'required',

                Rule::in([
                    'earning',
                    'deduction',
                ]),
            ],

            'calculation_type' => [
                'required',

                Rule::in([
                    'fixed',
                    'percentage',
                ]),
            ],

            'percentage_of_component_id' => [
                Rule::requiredIf(
                    fn () =>
                        $request->input(
                            'calculation_type'
                        ) === 'percentage'
                ),

                'nullable',
                'integer',
                'exists:salary_components,id',

                function (
                    string $attribute,
                    mixed $value,
                    \Closure $fail
                ) use ($componentId) {
                    if (
                        $componentId !== null
                        && (int) $value ===
                            (int) $componentId
                    ) {
                        $fail(
                            'A salary component cannot be calculated as a percentage of itself.'
                        );
                    }
                },
            ],

            'default_percentage' => [
                Rule::requiredIf(
                    fn () =>
                        $request->input(
                            'calculation_type'
                        ) === 'percentage'
                ),

                'nullable',
                'numeric',
                'min:0',
                'max:1000',
            ],

            'default_amount' => [
                Rule::requiredIf(
                    fn () =>
                        $request->input(
                            'calculation_type'
                        ) === 'fixed'
                ),

                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'is_taxable' => [
                'nullable',
                'boolean',
            ],

            'affects_gross' => [
                'nullable',
                'boolean',
            ],

            'is_statutory' => [
                'nullable',
                'boolean',
            ],

            'is_recurring' => [
                'nullable',
                'boolean',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);
    }
}