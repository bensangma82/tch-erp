<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeSalaryStructure;
use App\Models\SalaryComponent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeSalaryStructureController extends Controller
{
    /**
     * Display all employee salary structures.
     */
    public function index(Request $request): View
    {
        $query = EmployeeSalaryStructure::query()
            ->with([
                'employee.department',
                'items.salaryComponent',
            ]);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $salaryStructures = $query
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $employees = Employee::query()
            ->where('is_active', true)
            ->orderBy('employee_code')
            ->get();

        return view(
            'admin.hr.payroll.salary-structures.index',
            compact('salaryStructures', 'employees')
        );
    }

    /**
     * Show the form for creating a salary structure.
     */
    public function create(Request $request): View
    {
        $employees = Employee::query()
            ->where('is_active', true)
            ->with('department')
            ->orderBy('employee_code')
            ->get();

        $components = SalaryComponent::query()
            ->where('is_active', true)
            ->with('percentageOfComponent')
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $selectedEmployeeId = $request->integer('employee_id') ?: null;

        return view(
            'admin.hr.payroll.salary-structures.create',
            compact(
                'employees',
                'components',
                'selectedEmployeeId'
            )
        );
    }

    /**
     * Store a new salary structure as a draft.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateSalaryStructure($request);

        $salaryStructure = DB::transaction(function () use ($validated) {
            $structure = EmployeeSalaryStructure::create([
                'employee_id' => $validated['employee_id'],
                'effective_from' => $validated['effective_from'],
                'effective_to' => $validated['effective_to'] ?? null,
                'monthly_salary' => $validated['monthly_salary'] ?? null,
                'status' => 'draft',
                'remarks' => $validated['remarks'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->saveItems(
                $structure,
                $validated['items'] ?? []
            );

            return $structure;
        });

        return redirect()
            ->route(
                'admin.hr.payroll.salary-structures.edit',
                $salaryStructure
            )
            ->with(
                'success',
                'Employee salary structure created successfully.'
            );
    }

    /**
     * Show the salary structure edit form.
     */
    public function edit(
        EmployeeSalaryStructure $salaryStructure
    ): View {
        $salaryStructure->load([
            'employee.department',
            'items.salaryComponent',
            'items.percentageOfComponent',
        ]);

        $employees = Employee::query()
            ->where('is_active', true)
            ->with('department')
            ->orderBy('employee_code')
            ->get();

        $components = SalaryComponent::query()
            ->where('is_active', true)
            ->with('percentageOfComponent')
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view(
            'admin.hr.payroll.salary-structures.edit',
            compact(
                'salaryStructure',
                'employees',
                'components'
            )
        );
    }

    /**
     * Update an existing draft salary structure.
     */
    public function update(
        Request $request,
        EmployeeSalaryStructure $salaryStructure
    ): RedirectResponse {
        if (! $salaryStructure->isDraft()) {
            return back()->with(
                'error',
                'Only draft salary structures can be edited.'
            );
        }

        $validated = $this->validateSalaryStructure(
            $request,
            $salaryStructure
        );

        DB::transaction(function () use (
            $validated,
            $salaryStructure
        ) {
            $salaryStructure->update([
                'employee_id' => $validated['employee_id'],
                'effective_from' => $validated['effective_from'],
                'effective_to' => $validated['effective_to'] ?? null,
                'monthly_salary' => $validated['monthly_salary'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'updated_by' => auth()->id(),
            ]);

            $salaryStructure->items()->delete();

            $this->saveItems(
                $salaryStructure,
                $validated['items'] ?? []
            );
        });

        return redirect()
            ->route(
                'admin.hr.payroll.salary-structures.edit',
                $salaryStructure
            )
            ->with(
                'success',
                'Employee salary structure updated successfully.'
            );
    }

    /**
     * Activate a draft salary structure.
     *
     * When a new structure becomes active, any currently active
     * structure for the same employee is closed on the day before
     * the new structure becomes effective.
     */
    public function activate(
        EmployeeSalaryStructure $salaryStructure
    ): RedirectResponse {
        if (! $salaryStructure->isDraft()) {
            return back()->with(
                'error',
                'Only a draft salary structure can be activated.'
            );
        }

        if ($salaryStructure->items()->where('is_active', true)->doesntExist()) {
            return back()->with(
                'error',
                'Add at least one salary component before activating this structure.'
            );
        }

        DB::transaction(function () use ($salaryStructure) {
            $structure = EmployeeSalaryStructure::query()
                ->whereKey($salaryStructure->id)
                ->lockForUpdate()
                ->firstOrFail();

            $effectiveFrom = $structure->effective_from->copy()->startOfDay();

            $previousStructures = EmployeeSalaryStructure::query()
                ->where('employee_id', $structure->employee_id)
                ->where('id', '<>', $structure->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->get();

            foreach ($previousStructures as $previous) {
                if ($previous->effective_from->lt($effectiveFrom)) {
                    $previous->update([
                        'effective_to' => $effectiveFrom
                            ->copy()
                            ->subDay()
                            ->toDateString(),
                        'status' => 'superseded',
                        'updated_by' => auth()->id(),
                    ]);
                } else {
                    $previous->update([
                        'status' => 'superseded',
                        'updated_by' => auth()->id(),
                    ]);
                }
            }

            $structure->update([
                'status' => 'active',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'updated_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('admin.hr.payroll.salary-structures.index')
            ->with(
                'success',
                'Salary structure activated successfully.'
            );
    }

    /**
     * Validate salary structure input.
     */
    private function validateSalaryStructure(
        Request $request,
        ?EmployeeSalaryStructure $salaryStructure = null
    ): array {
        return $request->validate([
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')
                    ->where(fn ($query) => $query
                        ->where('is_active', true)
                        ->whereNull('deleted_at')),
            ],

            'effective_from' => [
                'required',
                'date',
            ],

            'effective_to' => [
                'nullable',
                'date',
                'after_or_equal:effective_from',
            ],

            'monthly_salary' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.salary_component_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('salary_components', 'id')
                    ->where(fn ($query) => $query
                        ->where('is_active', true)),
            ],

            'items.*.calculation_type' => [
                'required',
                Rule::in(['fixed', 'percentage']),
            ],

            'items.*.amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'items.*.percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:1000',
            ],

            'items.*.percentage_of_component_id' => [
                'nullable',
                'integer',
                'exists:salary_components,id',
            ],

            'items.*.minimum_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'items.*.maximum_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],

            'items.*.is_active' => [
                'nullable',
                'boolean',
            ],

            'items.*.sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'items.*.remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);
    }

    /**
     * Save salary structure component rows.
     */
    private function saveItems(
        EmployeeSalaryStructure $salaryStructure,
        array $items
    ): void {
        $components = SalaryComponent::query()
            ->whereIn(
                'id',
                collect($items)
                    ->pluck('salary_component_id')
                    ->filter()
                    ->all()
            )
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $component = $components->get(
                (int) $item['salary_component_id']
            );

            if (! $component) {
                continue;
            }

            $calculationType = $item['calculation_type'];

            $amount = null;
            $percentage = null;
            $percentageOfComponentId = null;

            if ($calculationType === 'fixed') {
                $amount = $item['amount'] ?? 0;
            }

            if ($calculationType === 'percentage') {
                $percentage = $item['percentage']
                    ?? $component->default_percentage
                    ?? 0;

                $percentageOfComponentId =
                    $item['percentage_of_component_id']
                    ?? $component->percentage_of_component_id;
            }

            $salaryStructure->items()->create([
                'salary_component_id' => $component->id,
                'calculation_type' => $calculationType,
                'amount' => $amount,
                'percentage' => $percentage,
                'percentage_of_component_id' => $percentageOfComponentId,
                'minimum_amount' => $item['minimum_amount'] ?? null,
                'maximum_amount' => $item['maximum_amount'] ?? null,
                'is_active' => (bool) ($item['is_active'] ?? true),
                'sort_order' => $item['sort_order']
                    ?? $component->sort_order
                    ?? 0,
                'remarks' => $item['remarks'] ?? null,
            ]);
        }
    }
}