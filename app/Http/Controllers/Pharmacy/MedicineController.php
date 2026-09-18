<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MedicineController extends Controller
{
    /**
     * Display medicine master.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->get('search'));

        $medicines = Medicine::query()
            ->when(
                $search !== '',
                function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('code', 'ilike', '%'.$search.'%')
                            ->orWhere('generic_name', 'ilike', '%'.$search.'%')
                            ->orWhere('brand_name', 'ilike', '%'.$search.'%')
                            ->orWhere('strength', 'ilike', '%'.$search.'%')
                            ->orWhere('manufacturer', 'ilike', '%'.$search.'%');
                    });
                }
            )
            ->orderBy('generic_name')
            ->orderBy('brand_name')
            ->paginate(20)
            ->withQueryString();

        return view(
            'pharmacy.medicines.index',
            compact(
                'medicines',
                'search'
            )
        );
    }


    /**
     * Show create form.
     */
    public function create(): View
    {
        return view(
            'pharmacy.medicines.create'
        );
    }


    /**
     * Store medicine.
     */
    public function store(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:medicines,code',
            ],

            'generic_name' => [
                'required',
                'string',
                'max:255',
            ],

            'brand_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'strength' => [
                'nullable',
                'string',
                'max:100',
            ],

            'dosage_form' => [
                'nullable',
                'string',
                'max:100',
            ],

            'manufacturer' => [
                'nullable',
                'string',
                'max:255',
            ],

            'unit' => [
                'required',
                'string',
                'max:50',
            ],

            'default_selling_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'hsn_code' => [
    'nullable',
    'string',
    'max:20',
],

'gst_percent' => [
    'required',
    'numeric',
    'min:0',
    'max:100',
],
        ]);


        Medicine::create([
            'code' => strtoupper(
                trim($validated['code'])
            ),

            'generic_name' => trim(
                $validated['generic_name']
            ),

            'brand_name' => filled($validated['brand_name'] ?? null)
                ? trim($validated['brand_name'])
                : null,

            'strength' => filled($validated['strength'] ?? null)
                ? trim($validated['strength'])
                : null,

            'dosage_form' => filled($validated['dosage_form'] ?? null)
                ? trim($validated['dosage_form'])
                : null,

            'manufacturer' => filled($validated['manufacturer'] ?? null)
                ? trim($validated['manufacturer'])
                : null,

            'unit' => trim(
                $validated['unit']
            ),

            'default_selling_price' =>
                $validated['default_selling_price'],

            'description' => filled($validated['description'] ?? null)
                ? trim($validated['description'])
                : null,

            'is_active' => true,
            'hsn_code' => filled($validated['hsn_code'] ?? null)
    ? trim($validated['hsn_code'])
    : null,

'gst_percent' =>
    $validated['gst_percent'],
        ]);


        return redirect()
            ->route('pharmacy.medicines.index')
            ->with(
                'success',
                'Medicine created successfully.'
            );
    }


    /**
     * Show edit form.
     */
    public function edit(
        Medicine $medicine
    ): View {
        return view(
            'pharmacy.medicines.edit',
            compact('medicine')
        );
    }


    /**
     * Update medicine.
     */
    public function update(
        Request $request,
        Medicine $medicine
    ): RedirectResponse {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('medicines', 'code')
                    ->ignore($medicine->id),
            ],

            'generic_name' => [
                'required',
                'string',
                'max:255',
            ],

            'brand_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'strength' => [
                'nullable',
                'string',
                'max:100',
            ],

            'dosage_form' => [
                'nullable',
                'string',
                'max:100',
            ],

            'manufacturer' => [
                'nullable',
                'string',
                'max:255',
            ],

            'unit' => [
                'required',
                'string',
                'max:50',
            ],

            'default_selling_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);


        $medicine->update([
            'code' => strtoupper(
                trim($validated['code'])
            ),

            'generic_name' => trim(
                $validated['generic_name']
            ),

            'brand_name' => filled($validated['brand_name'] ?? null)
                ? trim($validated['brand_name'])
                : null,

            'strength' => filled($validated['strength'] ?? null)
                ? trim($validated['strength'])
                : null,

            'dosage_form' => filled($validated['dosage_form'] ?? null)
                ? trim($validated['dosage_form'])
                : null,

            'manufacturer' => filled($validated['manufacturer'] ?? null)
                ? trim($validated['manufacturer'])
                : null,

            'unit' => trim(
                $validated['unit']
            ),

            'default_selling_price' =>
                $validated['default_selling_price'],

            'description' => filled($validated['description'] ?? null)
                ? trim($validated['description'])
                : null,

                'hsn_code' => filled($validated['hsn_code'] ?? null)
    ? trim($validated['hsn_code'])
    : null,

'gst_percent' =>
    $validated['gst_percent'],
        ]);


        return redirect()
            ->route('pharmacy.medicines.index')
            ->with(
                'success',
                'Medicine updated successfully.'
            );
    }


    /**
     * Activate or deactivate medicine.
     */
    public function toggleStatus(
        Medicine $medicine
    ): RedirectResponse {
        $medicine->update([
            'is_active' => ! $medicine->is_active,
        ]);


        return redirect()
            ->route('pharmacy.medicines.index')
            ->with(
                'success',
                $medicine->is_active
                    ? 'Medicine activated successfully.'
                    : 'Medicine deactivated successfully.'
            );
    }
}