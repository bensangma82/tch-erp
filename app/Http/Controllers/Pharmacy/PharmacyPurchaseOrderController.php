<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\PharmacyPurchaseOrder;
use App\Models\PharmacyPurchaseOrderItem;
use App\Models\PharmacySupplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PharmacyPurchaseOrderController extends Controller
{
    /**
     * Purchase Order Register.
     */
    public function index(Request $request): View
    {
        $query =
            PharmacyPurchaseOrder::query()
                ->with([
                    'supplier',
                    'createdBy',
                    'approvedBy',
                ])
                ->withCount('items')
                ->orderByDesc('po_date')
                ->orderByDesc('id');


        if ($request->filled('search')) {

            $search =
                trim($request->search);


            $query->where(
                function ($q) use ($search) {

                    $q->where(
                        'po_no',
                        'ilike',
                        '%' . $search . '%'
                    )
                        ->orWhereHas(
                            'supplier',
                            function ($supplierQuery) use ($search) {

                                $supplierQuery
                                    ->where(
                                        'name',
                                        'ilike',
                                        '%' . $search . '%'
                                    )
                                    ->orWhere(
                                        'code',
                                        'ilike',
                                        '%' . $search . '%'
                                    );
                            }
                        );
                }
            );
        }


        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );
        }


        if ($request->filled('from_date')) {

            $query->whereDate(
                'po_date',
                '>=',
                $request->from_date
            );
        }


        if ($request->filled('to_date')) {

            $query->whereDate(
                'po_date',
                '<=',
                $request->to_date
            );
        }


        $purchaseOrders =
            $query
                ->paginate(20)
                ->withQueryString();


        return view(
            'pharmacy.purchase-orders.index',
            compact('purchaseOrders')
        );
    }



    /**
     * Create Purchase Order form.
     */
    public function create(): View
    {
        $suppliers =
            PharmacySupplier::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->get();


        $medicines =
            Medicine::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('generic_name')
                ->get();


        return view(
            'pharmacy.purchase-orders.create',
            compact(
                'suppliers',
                'medicines'
            )
        );
    }



    /**
     * Save Purchase Order as Draft.
     */
    public function store(
        Request $request
    ): RedirectResponse {

        $validated =
            $request->validate([

                'pharmacy_supplier_id' => [
                    'required',
                    'integer',
                    'exists:pharmacy_suppliers,id',
                ],

                'po_date' => [
                    'required',
                    'date',
                ],

                'expected_delivery_date' => [
                    'nullable',
                    'date',
                    'after_or_equal:po_date',
                ],

                'remarks' => [
                    'nullable',
                    'string',
                    'max:3000',
                ],

                'items' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'items.*.medicine_id' => [
                    'required',
                    'integer',
                    'exists:medicines,id',
                ],

                'items.*.quantity_ordered' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'items.*.unit_cost' => [
                    'required',
                    'numeric',
                    'min:0',
                ],

                'items.*.discount_percent' => [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:100',
                ],

                'items.*.gst_percent' => [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:100',
                ],
            ]);


        $purchaseOrder =
            DB::transaction(
                function () use (
                    $validated,
                    $request
                ) {

                    $supplier =
                        PharmacySupplier::query()
                            ->whereKey(
                                $validated['pharmacy_supplier_id']
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->first();


                    if (! $supplier) {

                        throw ValidationException::withMessages([
                            'pharmacy_supplier_id' =>
                                'The selected supplier is inactive or unavailable.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Determine GST Type
                    |--------------------------------------------------------------------------
                    |
                    | For now:
                    | Supplier in Meghalaya = CGST + SGST
                    | Supplier outside Meghalaya = IGST
                    |
                    | This should later be moved to hospital tax settings.
                    |
                    */

                    $supplierState =
                        strtolower(
                            trim(
                                $supplier->state
                                ?? ''
                            )
                        );


                    $isInterState =
                        $supplierState !== ''
                        &&
                        $supplierState !== 'meghalaya';


                    /*
                    |--------------------------------------------------------------------------
                    | Lock PO Number Generation
                    |--------------------------------------------------------------------------
                    |
                    | PostgreSQL transaction advisory lock prevents two concurrent
                    | requests from generating the same PO number, including when
                    | no PO exists yet for the day.
                    |
                    */

                    DB::statement(
                        "SELECT pg_advisory_xact_lock(hashtext('pharmacy_purchase_order_number'))"
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Create PO Header
                    |--------------------------------------------------------------------------
                    */

                    $purchaseOrder =
                        PharmacyPurchaseOrder::create([

                            'po_no' =>
                                $this->generatePurchaseOrderNumber(),

                            'pharmacy_supplier_id' =>
                                $supplier->id,

                            'po_date' =>
                                $validated['po_date'],

                            'expected_delivery_date' =>
                                $validated['expected_delivery_date']
                                ?? null,

                            'status' =>
                                'draft',

                            'subtotal' =>
                                0,

                            'discount_amount' =>
                                0,

                            'taxable_amount' =>
                                0,

                            'cgst_amount' =>
                                0,

                            'sgst_amount' =>
                                0,

                            'igst_amount' =>
                                0,

                            'total_amount' =>
                                0,

                            'remarks' =>
                                $validated['remarks']
                                ?? null,

                            'created_by' =>
                                $request->user()->id,

                            'approved_by' =>
                                null,

                            'approved_at' =>
                                null,
                        ]);


                    $subtotal =
                        0.00;


                    $discountTotal =
                        0.00;


                    $taxableTotal =
                        0.00;


                    $cgstTotal =
                        0.00;


                    $sgstTotal =
                        0.00;


                    $igstTotal =
                        0.00;


                    $grandTotal =
                        0.00;


                    /*
                    |--------------------------------------------------------------------------
                    | Prevent Duplicate Medicine Lines
                    |--------------------------------------------------------------------------
                    */

                    $medicineIds =
                        collect(
                            $validated['items']
                        )
                            ->pluck(
                                'medicine_id'
                            )
                            ->map(
                                fn ($id) =>
                                    (int) $id
                            );


                    if (
                        $medicineIds->count()
                        !==
                        $medicineIds->unique()->count()
                    ) {

                        throw ValidationException::withMessages([
                            'items' =>
                                'The same medicine cannot appear more than once in a purchase order.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Create PO Items
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $validated['items']
                        as $row
                    ) {

                        $medicine =
                            Medicine::query()
                                ->whereKey(
                                    $row['medicine_id']
                                )
                                ->where(
                                    'is_active',
                                    true
                                )
                                ->first();


                        if (! $medicine) {

                            throw ValidationException::withMessages([
                                'items' =>
                                    'One or more selected medicines are inactive or unavailable.',
                            ]);
                        }


                        $quantity =
                            (int)
                            $row['quantity_ordered'];


                        $unitCost =
                            round(
                                (float)
                                $row['unit_cost'],
                                2
                            );


                        $discountPercent =
                            round(
                                (float)
                                (
                                    $row['discount_percent']
                                    ?? 0
                                ),
                                2
                            );


                        $gstPercent =
                            round(
                                (float)
                                (
                                    $row['gst_percent']
                                    ?? 0
                                ),
                                2
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | Line Calculation
                        |--------------------------------------------------------------------------
                        */

                        $gross =
                            round(
                                $quantity
                                * $unitCost,
                                2
                            );


                        $discountAmount =
                            round(
                                $gross
                                * $discountPercent
                                / 100,
                                2
                            );


                        $taxableAmount =
                            round(
                                $gross
                                - $discountAmount,
                                2
                            );


                        $gstAmount =
                            round(
                                $taxableAmount
                                * $gstPercent
                                / 100,
                                2
                            );


                        if ($isInterState) {

                            $cgstAmount =
                                0.00;


                            $sgstAmount =
                                0.00;


                            $igstAmount =
                                $gstAmount;

                        } else {

                            $cgstAmount =
                                round(
                                    $gstAmount
                                    / 2,
                                    2
                                );


                            $sgstAmount =
                                round(
                                    $gstAmount
                                    - $cgstAmount,
                                    2
                                );


                            $igstAmount =
                                0.00;
                        }


                        $lineTotal =
                            round(
                                $taxableAmount
                                + $cgstAmount
                                + $sgstAmount
                                + $igstAmount,
                                2
                            );


                        PharmacyPurchaseOrderItem::create([

                            'pharmacy_purchase_order_id' =>
                                $purchaseOrder->id,

                            'medicine_id' =>
                                $medicine->id,

                            'medicine_code' =>
                                $medicine->code,

                            'medicine_name' =>
                                $medicine->generic_name,

                            'brand_name' =>
                                $medicine->brand_name,

                            'strength' =>
                                $medicine->strength,

                            'unit' =>
                                $medicine->unit,

                            'quantity_ordered' =>
                                $quantity,

                            'unit_cost' =>
                                $unitCost,

                            'discount_percent' =>
                                $discountPercent,

                            'discount_amount' =>
                                $discountAmount,

                            'gst_percent' =>
                                $gstPercent,

                            'taxable_amount' =>
                                $taxableAmount,

                            'cgst_amount' =>
                                $cgstAmount,

                            'sgst_amount' =>
                                $sgstAmount,

                            'igst_amount' =>
                                $igstAmount,

                            'line_total' =>
                                $lineTotal,

                            'quantity_received' =>
                                0,
                        ]);


                        $subtotal +=
                            $gross;


                        $discountTotal +=
                            $discountAmount;


                        $taxableTotal +=
                            $taxableAmount;


                        $cgstTotal +=
                            $cgstAmount;


                        $sgstTotal +=
                            $sgstAmount;


                        $igstTotal +=
                            $igstAmount;


                        $grandTotal +=
                            $lineTotal;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Update Header Totals
                    |--------------------------------------------------------------------------
                    */

                    $purchaseOrder->update([

                        'subtotal' =>
                            round(
                                $subtotal,
                                2
                            ),

                        'discount_amount' =>
                            round(
                                $discountTotal,
                                2
                            ),

                        'taxable_amount' =>
                            round(
                                $taxableTotal,
                                2
                            ),

                        'cgst_amount' =>
                            round(
                                $cgstTotal,
                                2
                            ),

                        'sgst_amount' =>
                            round(
                                $sgstTotal,
                                2
                            ),

                        'igst_amount' =>
                            round(
                                $igstTotal,
                                2
                            ),

                        'total_amount' =>
                            round(
                                $grandTotal,
                                2
                            ),
                    ]);


                    return $purchaseOrder;
                }
            );


        return redirect()
            ->route(
                'pharmacy.purchase-orders.show',
                $purchaseOrder
            )
            ->with(
                'success',
                'Purchase order created successfully as draft.'
            );
    }



    /**
     * View Purchase Order.
     */
    public function show(
        PharmacyPurchaseOrder $pharmacyPurchaseOrder
    ): View {

        $pharmacyPurchaseOrder->load([
            'supplier',
            'items.medicine',
            'createdBy',
            'approvedBy',
        ]);


        return view(
            'pharmacy.purchase-orders.show',
            compact(
                'pharmacyPurchaseOrder'
            )
        );
    }



    /**
     * Approve Purchase Order.
     */
    public function approve(
        Request $request,
        PharmacyPurchaseOrder $pharmacyPurchaseOrder
    ): RedirectResponse {

        if (
            $pharmacyPurchaseOrder->status
            !== 'draft'
        ) {

            return back()->with(
                'error',
                'Only draft purchase orders can be approved.'
            );
        }


        if (
            $pharmacyPurchaseOrder
                ->items()
                ->count()
            === 0
        ) {

            return back()->with(
                'error',
                'Purchase order has no items.'
            );
        }


        $pharmacyPurchaseOrder->update([

            'status' =>
                'approved',

            'approved_by' =>
                $request->user()->id,

            'approved_at' =>
                now(),
        ]);


        return back()->with(
            'success',
            'Purchase order approved successfully.'
        );
    }



    /**
     * Cancel Purchase Order.
     */
    public function cancel(
        PharmacyPurchaseOrder $pharmacyPurchaseOrder
    ): RedirectResponse {

        if (
            in_array(
                $pharmacyPurchaseOrder->status,
                [
                    'partially_received',
                    'received',
                ],
                true
            )
        ) {

            return back()->with(
                'error',
                'A purchase order that has already been received cannot be cancelled.'
            );
        }


        $pharmacyPurchaseOrder->update([

            'status' =>
                'cancelled',
        ]);


        return back()->with(
            'success',
            'Purchase order cancelled.'
        );
    }



    /**
     * Generate concurrency-safe PO number.
     *
     * Example:
     * PO-20260914-000001
     *
     * IMPORTANT:
     * This method must be called while the current database transaction holds
     * the PostgreSQL advisory lock:
     *
     * pharmacy_purchase_order_number
     */
    private function generatePurchaseOrderNumber(): string
    {
        $prefix =
            'PO-'
            . now()->format('Ymd')
            . '-';


        $last =
            PharmacyPurchaseOrder::query()
                ->where(
                    'po_no',
                    'like',
                    $prefix . '%'
                )
                ->orderByDesc('id')
                ->first();


        $next =
            1;


        if ($last) {

            $lastSequence =
                (int)
                substr(
                    $last->po_no,
                    -6
                );


            $next =
                $lastSequence
                + 1;
        }


        return
            $prefix
            . str_pad(
                (string) $next,
                6,
                '0',
                STR_PAD_LEFT
            );
    }
}