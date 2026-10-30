<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\FinanceHead;
use App\Models\GeneralLedgerAccount;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        */

        if ($scope === 'charges') {
            $query->whereNotIn('category', [
                'laboratory',
                'radiology',
            ]);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('code', 'ilike', "%{$search}%")
                    ->orWhere('name', 'ilike', "%{$search}%")
                    ->orWhere('category', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $services = $query
            ->orderBy('category')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view(
            'services.index',
            compact('services', 'scope')
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

        $financeHeads = FinanceHead::query()
            ->where('head_type', 'income')
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $revenueGlAccounts = GeneralLedgerAccount::query()
            ->where('account_type', 'income')
            ->where('is_active', true)
            ->where('allow_posting', true)
            ->whereNotNull('finance_head_id')
            ->orderBy('account_code')
            ->get();

        $categories = ServiceCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view(
            'services.create',
            compact(
                'departments',
                'financeHeads',
                'revenueGlAccounts',
                'categories'
            )
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
                'string',
                'exists:service_categories,code',
                function ($attribute, $value, $fail) {
                    $active = ServiceCategory::query()
                        ->where('code', $value)
                        ->where('is_active', true)
                        ->exists();

                    if (! $active) {
                        $fail(
                            'Please select an active service category.'
                        );
                    }
                },
            ],

            'department_id' => [
                'nullable',
                'exists:departments,id',
            ],

            'finance_head_id' => [
                'nullable',
                Rule::exists('finance_heads', 'id')
                    ->where(
                        fn ($query) => $query
                            ->where('head_type', 'income')
                            ->where('is_active', true)
                    ),
            ],

            'revenue_gl_account_id' => [
                'nullable',
                'integer',
                Rule::exists('general_ledger_accounts', 'id')
                    ->where(fn ($query) => $query
                        ->where('account_type', 'income')
                        ->where('is_active', true)
                        ->where('allow_posting', true)
                        ->where('finance_head_id', request('finance_head_id'))
                    ),
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

            'default_specimen_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'sample_container' => [
                'nullable',
                'string',
                'max:150',
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

        $validated['code'] = strtoupper(
            trim($validated['code'])
        );

        $validated['name'] = trim(
            $validated['name']
        );

        $validated['requires_sample'] =
            $request->boolean('requires_sample');

        $validated['default_specimen_type'] =
            $validated['requires_sample']
                ? ($validated['default_specimen_type'] ?? null)
                : null;

        $validated['sample_container'] =
            $validated['requires_sample']
                ? ($validated['sample_container'] ?? null)
                : null;

        $validated['requires_report'] =
            $request->boolean('requires_report');

        $validated['is_active'] =
            $request->boolean('is_active');

        Service::create($validated);

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

        $financeHeads = FinanceHead::query()
            ->where('head_type', 'income')
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $revenueGlAccounts = GeneralLedgerAccount::query()
            ->where('account_type', 'income')
            ->where('is_active', true)
            ->where('allow_posting', true)
            ->whereNotNull('finance_head_id')
            ->orderBy('account_code')
            ->get();

        $categories = ServiceCategory::query()
            ->where(function ($query) use ($service) {
                $query->where('is_active', true)
                    ->orWhere('code', $service->category);
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view(
            'services.edit',
            compact(
                'service',
                'departments',
                'financeHeads',
                'revenueGlAccounts',
                'categories'
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
                'unique:services,code,'.$service->id,
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'category' => [
                'required',
                'string',
                'exists:service_categories,code',
                function ($attribute, $value, $fail) use ($service) {
                    if ($value === $service->category) {
                        return;
                    }

                    $active = ServiceCategory::query()
                        ->where('code', $value)
                        ->where('is_active', true)
                        ->exists();

                    if (! $active) {
                        $fail(
                            'Please select an active service category.'
                        );
                    }
                },
            ],

            'department_id' => [
                'nullable',
                'exists:departments,id',
            ],

            'finance_head_id' => [
                'nullable',
                Rule::exists('finance_heads', 'id')
                    ->where(
                        fn ($query) => $query
                            ->where('head_type', 'income')
                            ->where('is_active', true)
                    ),
            ],

            'revenue_gl_account_id' => [
                'nullable',
                'integer',
                Rule::exists('general_ledger_accounts', 'id')
                    ->where(fn ($query) => $query
                        ->where('account_type', 'income')
                        ->where('is_active', true)
                        ->where('allow_posting', true)
                        ->where('finance_head_id', request('finance_head_id'))
                    ),
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

            'default_specimen_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'sample_container' => [
                'nullable',
                'string',
                'max:150',
            ],
        ]);

        $validated['code'] = strtoupper(
            trim($validated['code'])
        );

        $validated['name'] = trim(
            $validated['name']
        );

        $validated['requires_sample'] =
            $request->boolean('requires_sample');

        $validated['default_specimen_type'] =
            $validated['requires_sample']
                ? ($validated['default_specimen_type'] ?? null)
                : null;

        $validated['sample_container'] =
            $validated['requires_sample']
                ? ($validated['sample_container'] ?? null)
                : null;

        $validated['requires_report'] =
            $request->boolean('requires_report');

        $validated['is_active'] =
            $request->boolean('is_active');

        $service->update($validated);

        return redirect()
            ->route('services.index')
            ->with(
                'success',
                'Service updated successfully.'
            );
    }
}
