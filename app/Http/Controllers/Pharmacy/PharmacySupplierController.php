<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\PharmacySupplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PharmacySupplierController extends Controller
{
    public function index(Request $request): View
    {
        $query =
            PharmacySupplier::query()
                ->orderBy('name');

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('code', 'ilike', '%' . $search . '%')
                    ->orWhere('name', 'ilike', '%' . $search . '%')
                    ->orWhere('contact_person', 'ilike', '%' . $search . '%')
                    ->orWhere('phone', 'ilike', '%' . $search . '%')
                    ->orWhere('gstin', 'ilike', '%' . $search . '%')
                    ->orWhere('drug_license_no', 'ilike', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            }

            if ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $suppliers =
            $query
                ->paginate(20)
                ->withQueryString();

        return view(
            'pharmacy.suppliers.index',
            compact('suppliers')
        );
    }


    public function create(): View
    {
        return view(
            'pharmacy.suppliers.create'
        );
    }


    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:pharmacy_suppliers,code',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'contact_person' => [
                'nullable',
                'string',
                'max:255',
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
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'city' => [
                'nullable',
                'string',
                'max:255',
            ],

            'district' => [
                'nullable',
                'string',
                'max:255',
            ],

            'state' => [
                'nullable',
                'string',
                'max:255',
            ],

            'pin_code' => [
                'nullable',
                'string',
                'max:10',
            ],

            'gstin' => [
                'nullable',
                'string',
                'max:20',
            ],

            'drug_license_no' => [
                'nullable',
                'string',
                'max:255',
            ],

            'pan_no' => [
                'nullable',
                'string',
                'max:20',
            ],

            'credit_days' => [
                'nullable',
                'integer',
                'min:0',
                'max:3650',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $validated['code'] =
            strtoupper(
                trim($validated['code'])
            );

        $validated['gstin'] =
            isset($validated['gstin'])
                ? strtoupper(trim($validated['gstin']))
                : null;

        $validated['pan_no'] =
            isset($validated['pan_no'])
                ? strtoupper(trim($validated['pan_no']))
                : null;

        $validated['credit_days'] =
            $validated['credit_days'] ?? 0;

        $validated['is_active'] =
            true;

        $supplier =
            PharmacySupplier::create(
                $validated
            );

        return redirect()
            ->route(
                'pharmacy.suppliers.edit',
                $supplier
            )
            ->with(
                'success',
                'Supplier created successfully.'
            );
    }


    public function edit(
        PharmacySupplier $pharmacySupplier
    ): View {
        return view(
            'pharmacy.suppliers.edit',
            compact('pharmacySupplier')
        );
    }


    public function update(
        Request $request,
        PharmacySupplier $pharmacySupplier
    ): RedirectResponse {

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique(
                    'pharmacy_suppliers',
                    'code'
                )->ignore(
                    $pharmacySupplier->id
                ),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'contact_person' => [
                'nullable',
                'string',
                'max:255',
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
                'max:255',
            ],

            'address' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'city' => [
                'nullable',
                'string',
                'max:255',
            ],

            'district' => [
                'nullable',
                'string',
                'max:255',
            ],

            'state' => [
                'nullable',
                'string',
                'max:255',
            ],

            'pin_code' => [
                'nullable',
                'string',
                'max:10',
            ],

            'gstin' => [
                'nullable',
                'string',
                'max:20',
            ],

            'drug_license_no' => [
                'nullable',
                'string',
                'max:255',
            ],

            'pan_no' => [
                'nullable',
                'string',
                'max:20',
            ],

            'credit_days' => [
                'nullable',
                'integer',
                'min:0',
                'max:3650',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $validated['code'] =
            strtoupper(
                trim($validated['code'])
            );

        $validated['gstin'] =
            isset($validated['gstin'])
                ? strtoupper(trim($validated['gstin']))
                : null;

        $validated['pan_no'] =
            isset($validated['pan_no'])
                ? strtoupper(trim($validated['pan_no']))
                : null;

        $validated['credit_days'] =
            $validated['credit_days'] ?? 0;

        $pharmacySupplier->update(
            $validated
        );

        return redirect()
            ->route(
                'pharmacy.suppliers.edit',
                $pharmacySupplier
            )
            ->with(
                'success',
                'Supplier updated successfully.'
            );
    }


    public function toggleStatus(
        PharmacySupplier $pharmacySupplier
    ): RedirectResponse {

        $pharmacySupplier->update([
            'is_active' =>
                ! $pharmacySupplier->is_active,
        ]);

        return back()->with(
            'success',
            $pharmacySupplier->is_active
                ? 'Supplier activated successfully.'
                : 'Supplier deactivated successfully.'
        );
    }
}