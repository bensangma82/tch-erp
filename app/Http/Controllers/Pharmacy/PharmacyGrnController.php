<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\PharmacyGrn;
use App\Models\PharmacyGrnItem;
use App\Models\PharmacyPurchaseOrder;
use App\Models\PharmacyPurchaseOrderItem;
use App\Models\PharmacyStockBatch;
use App\Models\PharmacyStockLocation;
use App\Models\PharmacyStockLocationBalance;
use App\Models\PharmacyStockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PharmacyGrnController extends Controller
{
    /**
     * GRN Register.
     */
    public function index(
        Request $request
    ): View {

        $query =
            PharmacyGrn::query()
                ->with([
                    'purchaseOrder',
                    'supplier',
                    'createdBy',
                ])
                ->withCount('items')
                ->orderByDesc('grn_date')
                ->orderByDesc('id');


        if ($request->filled('search')) {

            $search =
                trim(
                    $request->search
                );


            $query->where(
                function ($q) use ($search) {

                    $q->where(
                        'grn_no',
                        'ilike',
                        '%' . $search . '%'
                    )
                        ->orWhere(
                            'supplier_invoice_no',
                            'ilike',
                            '%' . $search . '%'
                        )
                        ->orWhereHas(
                            'purchaseOrder',
                            function ($poQuery) use ($search) {

                                $poQuery->where(
                                    'po_no',
                                    'ilike',
                                    '%' . $search . '%'
                                );
                            }
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


        if ($request->filled('from_date')) {

            $query->whereDate(
                'grn_date',
                '>=',
                $request->from_date
            );
        }


        if ($request->filled('to_date')) {

            $query->whereDate(
                'grn_date',
                '<=',
                $request->to_date
            );
        }


        $grns =
            $query
                ->paginate(20)
                ->withQueryString();


        return view(
            'pharmacy.grns.index',
            compact(
                'grns'
            )
        );
    }



    /**
     * Show GRN form.
     */
    public function create(
        PharmacyPurchaseOrder $pharmacyPurchaseOrder
    ): View {

        $pharmacyPurchaseOrder->load([
            'supplier',
            'items.medicine',
        ]);


        if (
            ! in_array(
                $pharmacyPurchaseOrder->status,
                [
                    'approved',
                    'partially_received',
                ],
                true
            )
        ) {

            abort(
                422,
                'GRN can only be created for approved or partially received purchase orders.'
            );
        }


        return view(
            'pharmacy.grns.create',
            compact(
                'pharmacyPurchaseOrder'
            )
        );
    }



    /**
     * Store GRN.
     *
     * Supplier stock always enters CENTRAL STORE.
     */
    public function store(
        Request $request,
        PharmacyPurchaseOrder $pharmacyPurchaseOrder
    ): RedirectResponse {

        $validated =
            $request->validate([

                'grn_date' => [
                    'required',
                    'date',
                ],

                'supplier_invoice_no' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'supplier_invoice_date' => [
                    'nullable',
                    'date',
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

                'items.*.purchase_order_item_id' => [
                    'required',
                    'integer',
                    'exists:pharmacy_purchase_order_items,id',
                ],

                'items.*.purchase_qty' => [
                    'required',
                    'integer',
                    'min:0',
                ],

                'items.*.bonus_qty' => [
                    'nullable',
                    'integer',
                    'min:0',
                ],

                'items.*.units_per_pack' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'items.*.batch_number' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'items.*.expiry_date' => [
                    'nullable',
                    'date',
                ],

                'items.*.purchase_price' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'items.*.mrp_per_pack' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                // Kept for compatibility with the Blade payload.
                // The server does not trust this value; it recalculates
                // selling price per base unit from MRP / Pack.
                'items.*.selling_price' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],
            ]);


        /*
        |--------------------------------------------------------------------------
        | Central Store
        |--------------------------------------------------------------------------
        */

        $centralStore =
            PharmacyStockLocation::query()
                ->where(
                    'code',
                    'CENTRAL_STORE'
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();


        if (! $centralStore) {

            throw ValidationException::withMessages([
                'items' =>
                    'Central Store stock location is not configured.',
            ]);
        }


        $grn =
            DB::transaction(
                function () use (
                    $validated,
                    $request,
                    $pharmacyPurchaseOrder,
                    $centralStore
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Lock Purchase Order
                    |--------------------------------------------------------------------------
                    */

                    $po =
                        PharmacyPurchaseOrder::query()
                            ->with([
                                'supplier',
                                'items.medicine',
                            ])
                            ->whereKey(
                                $pharmacyPurchaseOrder->id
                            )
                            ->lockForUpdate()
                            ->firstOrFail();


                    if (
                        ! in_array(
                            $po->status,
                            [
                                'approved',
                                'partially_received',
                            ],
                            true
                        )
                    ) {

                        throw ValidationException::withMessages([
                            'items' =>
                                'This purchase order is no longer available for receiving.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | GST Mode
                    |--------------------------------------------------------------------------
                    */

                    $supplierState =
                        strtolower(
                            trim(
                                $po->supplier?->state
                                ?? ''
                            )
                        );


                    $isInterState =
                        $supplierState !== ''
                        &&
                        $supplierState !== 'meghalaya';


                    /*
                    |--------------------------------------------------------------------------
                    | Validate Receiving Lines
                    |--------------------------------------------------------------------------
                    */

                    $receivedAny =
                        false;


                    foreach (
                        $validated['items']
                        as $row
                    ) {

                        $purchaseQty =
                            (int)
                            $row['purchase_qty'];

                        $bonusQty =
                            (int)
                            ($row['bonus_qty'] ?? 0);

                        $unitsPerPack =
                            (int)
                            $row['units_per_pack'];

                        $receivedUnits =
                            ($purchaseQty + $bonusQty)
                            * $unitsPerPack;


                        if ($purchaseQty <= 0) {
                            continue;
                        }


                        $receivedAny =
                            true;


                        $poItem =
                            $po->items
                                ->firstWhere(
                                    'id',
                                    (int)
                                    $row[
                                        'purchase_order_item_id'
                                    ]
                                );


                        if (! $poItem) {

                            throw ValidationException::withMessages([
                                'items' =>
                                    'One or more GRN items do not belong to this purchase order.',
                            ]);
                        }


                        $remaining =
                            (int)
                            $poItem->quantity_ordered
                            -
                            (int)
                            $poItem->quantity_received;


                        if (
                            $purchaseQty
                            > $remaining
                        ) {

                            throw ValidationException::withMessages([
                                'items' =>
                                    'Received quantity for '
                                    . $poItem->medicine_name
                                    . ' cannot exceed the remaining PO quantity of '
                                    . $remaining
                                    . '.',
                            ]);
                        }


                        if (
                            empty(
                                trim(
                                    $row[
                                        'batch_number'
                                    ]
                                    ?? ''
                                )
                            )
                        ) {

                            throw ValidationException::withMessages([
                                'items' =>
                                    'Batch number is required for every received medicine.',
                            ]);
                        }


                        if (
                            empty(
                                $row[
                                    'expiry_date'
                                ]
                                ?? null
                            )
                        ) {

                            throw ValidationException::withMessages([
                                'items' =>
                                    'Expiry date is required for every received medicine.',
                            ]);
                        }
                    }


                    if (! $receivedAny) {

                        throw ValidationException::withMessages([
                            'items' =>
                                'Enter a purchased pack quantity for at least one medicine.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | GRN Number Lock
                    |--------------------------------------------------------------------------
                    */

                    DB::statement(
                        "SELECT pg_advisory_xact_lock(hashtext('pharmacy_grn_number'))"
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Create GRN Header
                    |--------------------------------------------------------------------------
                    */

                    $grn =
                        PharmacyGrn::create([

                            'grn_no' =>
                                $this->generateGrnNumber(),

                            'pharmacy_purchase_order_id' =>
                                $po->id,

                            'pharmacy_supplier_id' =>
                                $po->pharmacy_supplier_id,

                            'grn_date' =>
                                $validated[
                                    'grn_date'
                                ],

                            'supplier_invoice_no' =>
                                $validated[
                                    'supplier_invoice_no'
                                ]
                                ?? null,

                            'supplier_invoice_date' =>
                                $validated[
                                    'supplier_invoice_date'
                                ]
                                ?? null,

                            'status' =>
                                'completed',

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
                                $validated[
                                    'remarks'
                                ]
                                ?? null,

                            'created_by' =>
                                $request
                                    ->user()
                                    ->id,
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
                    | Receive GRN Items
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $validated['items']
                        as $row
                    ) {

                        $purchaseQty =
                            (int)
                            $row['purchase_qty'];

                        $bonusQty =
                            (int)
                            ($row['bonus_qty'] ?? 0);

                        $unitsPerPack =
                            (int)
                            $row['units_per_pack'];

                        $receivedUnits =
                            ($purchaseQty + $bonusQty)
                            * $unitsPerPack;


                        if ($purchaseQty <= 0) {
                            continue;
                        }


                        $poItem =
                            PharmacyPurchaseOrderItem::query()
                                ->whereKey(
                                    $row[
                                        'purchase_order_item_id'
                                    ]
                                )
                                ->where(
                                    'pharmacy_purchase_order_id',
                                    $po->id
                                )
                                ->lockForUpdate()
                                ->firstOrFail();


                        $remaining =
                            (int)
                            $poItem->quantity_ordered
                            -
                            (int)
                            $poItem->quantity_received;


                        if (
                            $purchaseQty
                            > $remaining
                        ) {

                            throw ValidationException::withMessages([
                                'items' =>
                                    'Received quantity for '
                                    . $poItem->medicine_name
                                    . ' exceeds the remaining PO quantity.',
                            ]);
                        }


                        $batchNumber =
                            trim(
                                $row[
                                    'batch_number'
                                ]
                            );


                        $expiryDate =
                            $row[
                                'expiry_date'
                            ];


                        $purchasePrice =
                            round(
                                (float)
                                (
                                    $row[
                                        'purchase_price'
                                    ]
                                    ??
                                    $poItem->unit_cost
                                ),
                                2
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | MRP / Selling Price
                        |--------------------------------------------------------------------------
                        |
                        | MRP is entered per purchase pack. Stock and dispensing
                        | operate in base units, so selling_price must always be
                        | stored as the price of ONE base unit.
                        |
                        | Never trust the hidden browser selling_price value.
                        |
                        */

                        $mrpPerPack =
                            round(
                                (float)
                                (
                                    $row[
                                        'mrp_per_pack'
                                    ]
                                    ?? 0
                                ),
                                2
                            );


                        $sellingPrice =
                            round(
                                $mrpPerPack
                                / $unitsPerPack,
                                2
                            );


                        $discountPercent =
                            round(
                                (float)
                                $poItem
                                    ->discount_percent,
                                2
                            );


                        $gstPercent =
                            round(
                                (float)
                                $poItem
                                    ->gst_percent,
                                2
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | Financial Calculation
                        |--------------------------------------------------------------------------
                        */

                        $gross =
                            round(
                                $purchaseQty
                                * $purchasePrice,
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
                                    $gstAmount / 2,
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


                        /*
                        |--------------------------------------------------------------------------
                        | Lock Medicine + Batch Identity
                        |--------------------------------------------------------------------------
                        |
                        | This also protects concurrent first-time creation
                        | of the same medicine/batch combination.
                        |
                        */

                        DB::statement(
                            "SELECT pg_advisory_xact_lock(hashtext(?))",
                            [
                                'pharmacy_stock_batch_'
                                . $poItem->medicine_id
                                . '_'
                                . $batchNumber,
                            ]
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | Hospital-wide Stock Batch
                        |--------------------------------------------------------------------------
                        */

                        $stockBatch =
                            PharmacyStockBatch::query()
                                ->where(
                                    'medicine_id',
                                    $poItem->medicine_id
                                )
                                ->where(
                                    'batch_number',
                                    $batchNumber
                                )
                                ->lockForUpdate()
                                ->first();


                        if ($stockBatch) {

                            /*
                            |--------------------------------------------------------------------------
                            | Expiry Safety Check
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $stockBatch->expiry_date
                                &&
                                $stockBatch
                                    ->expiry_date
                                    ->format('Y-m-d')
                                !==
                                $expiryDate
                            ) {

                                throw ValidationException::withMessages([
                                    'items' =>
                                        'Existing batch '
                                        . $batchNumber
                                        . ' has a different expiry date.',
                                ]);
                            }


                            $oldGlobalBalance =
                                (int)
                                $stockBatch
                                    ->quantity_available;


                            $newGlobalBalance =
                                $oldGlobalBalance
                                + $receivedUnits;


                            $stockBatch->update([

                                'quantity_received' =>
                                    (int)
                                    $stockBatch
                                        ->quantity_received
                                    + $receivedUnits,

                                'quantity_available' =>
                                    $newGlobalBalance,

                                'purchase_price' =>
                                    $purchasePrice,

                                'selling_price' =>
                                    $sellingPrice,

                                'expiry_date' =>
                                    $expiryDate,

                                'received_date' =>
                                    $validated[
                                        'grn_date'
                                    ],

                                'is_active' =>
                                    true,
                            ]);

                        } else {

                            $oldGlobalBalance =
                                0;


                            $newGlobalBalance =
                                $receivedUnits;


                            $stockBatch =
                                PharmacyStockBatch::create([

                                    'medicine_id' =>
                                        $poItem
                                            ->medicine_id,

                                    'batch_number' =>
                                        $batchNumber,

                                    'expiry_date' =>
                                        $expiryDate,

                                    'purchase_price' =>
                                        $purchasePrice,

                                    'selling_price' =>
                                        $sellingPrice,

                                    'quantity_received' =>
                                        $receivedUnits,

                                    'quantity_available' =>
                                        $receivedUnits,

                                    'reorder_level' =>
                                        0,

                                    'received_date' =>
                                        $validated[
                                            'grn_date'
                                        ],

                                    'is_active' =>
                                        true,
                                ]);
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Central Store Balance Lock
                        |--------------------------------------------------------------------------
                        */

                        DB::statement(
                            "SELECT pg_advisory_xact_lock(hashtext(?))",
                            [
                                'pharmacy_location_balance_'
                                . $centralStore->id
                                . '_'
                                . $stockBatch->id,
                            ]
                        );


                        $centralBalance =
                            PharmacyStockLocationBalance::query()
                                ->where(
                                    'pharmacy_stock_location_id',
                                    $centralStore->id
                                )
                                ->where(
                                    'pharmacy_stock_batch_id',
                                    $stockBatch->id
                                )
                                ->lockForUpdate()
                                ->first();


                        if ($centralBalance) {

                            $oldCentralBalance =
                                (int)
                                $centralBalance
                                    ->quantity_available;


                            $newCentralBalance =
                                $oldCentralBalance
                                + $receivedUnits;


                            $centralBalance->update([

                                'quantity_available' =>
                                    $newCentralBalance,
                            ]);

                        } else {

                            $oldCentralBalance =
                                0;


                            $newCentralBalance =
                                $receivedUnits;


                            $centralBalance =
                                PharmacyStockLocationBalance::create([

                                    'pharmacy_stock_location_id' =>
                                        $centralStore->id,

                                    'pharmacy_stock_batch_id' =>
                                        $stockBatch->id,

                                    'quantity_available' =>
                                        $receivedUnits,

                                    'reorder_level' =>
                                        $stockBatch
                                            ->reorder_level
                                        ?? 0,
                                ]);
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Integrity Check
                        |--------------------------------------------------------------------------
                        |
                        | Central Store may not equal global stock because some
                        | quantities may already be held by Pharmacy or wards.
                        |
                        | Therefore we do NOT force equality here.
                        |
                        */


                        /*
                        |--------------------------------------------------------------------------
                        | GRN Item
                        |--------------------------------------------------------------------------
                        */

                        PharmacyGrnItem::create([

                            'pharmacy_grn_id' =>
                                $grn->id,

                            'pharmacy_purchase_order_item_id' =>
                                $poItem->id,

                            'medicine_id' =>
                                $poItem->medicine_id,

                            'pharmacy_stock_batch_id' =>
                                $stockBatch->id,

                            'medicine_code' =>
                                $poItem->medicine_code,

                            'medicine_name' =>
                                $poItem->medicine_name,

                            'brand_name' =>
                                $poItem->brand_name,

                            'strength' =>
                                $poItem->strength,

                            'unit' =>
                                $poItem->unit,

                            'batch_number' =>
                                $batchNumber,

                            'expiry_date' =>
                                $expiryDate,

                            'purchase_qty' =>
                                $purchaseQty,

                            'bonus_qty' =>
                                $bonusQty,

                            'units_per_pack' =>
                                $unitsPerPack,

                            'received_units' =>
                                $receivedUnits,

                            // Legacy field retained as base-unit quantity.
                            'quantity_received' =>
                                $receivedUnits,

                            'purchase_price' =>
                                $purchasePrice,

                            'selling_price' =>
                                $sellingPrice,

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
                        ]);


                        /*
                        |--------------------------------------------------------------------------
                        | Hospital-wide Stock Ledger
                        |--------------------------------------------------------------------------
                        */

                        PharmacyStockMovement::create([

                            'pharmacy_stock_batch_id' =>
                                $stockBatch->id,

                            'movement_type' =>
                                'stock_in',

                            'quantity' =>
                                $receivedUnits,

                            'balance_after' =>
                                $newGlobalBalance,

                            'reference_type' =>
                                'pharmacy_grn',

                            'reference_id' =>
                                $grn->id,

                            'remarks' =>
                                'Received into Central Store through '
                                . $grn->grn_no
                                . ' against '
                                . $po->po_no
                                . ' | Batch: '
                                . $batchNumber
                                . ' | Packs: '
                                . $purchaseQty
                                . ' + Bonus: '
                                . $bonusQty
                                . ' | Units/Pack: '
                                . $unitsPerPack
                                . ' | Stock Units: '
                                . $receivedUnits
                                . ' | Central Store: '
                                . $oldCentralBalance
                                . ' → '
                                . $newCentralBalance
                                . ' | Hospital: '
                                . $oldGlobalBalance
                                . ' → '
                                . $newGlobalBalance,

                            'created_by' =>
                                $request
                                    ->user()
                                    ->id,

                            'movement_at' =>
                                now(),
                        ]);


                        /*
                        |--------------------------------------------------------------------------
                        | Update PO Item Received Quantity
                        |--------------------------------------------------------------------------
                        */

                        $poItem->update([

                            'quantity_received' =>
                                (int)
                                $poItem
                                    ->quantity_received
                                + $purchaseQty,
                        ]);


                        /*
                        |--------------------------------------------------------------------------
                        | Accumulate GRN Totals
                        |--------------------------------------------------------------------------
                        */

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
                    | Update GRN Totals
                    |--------------------------------------------------------------------------
                    */

                    $grn->update([

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


                    /*
                    |--------------------------------------------------------------------------
                    | Update Purchase Order Status
                    |--------------------------------------------------------------------------
                    */

                    $po->refresh();


                    $po->load(
                        'items'
                    );


                    $allReceived =
                        $po->items
                            ->every(
                                function ($item) {

                                    return
                                        (int)
                                        $item
                                            ->quantity_received
                                        >=
                                        (int)
                                        $item
                                            ->quantity_ordered;
                                }
                            );


                    $anyReceived =
                        $po->items
                            ->contains(
                                function ($item) {

                                    return
                                        (int)
                                        $item
                                            ->quantity_received
                                        > 0;
                                }
                            );


                    if ($allReceived) {

                        $po->update([
                            'status' =>
                                'received',
                        ]);

                    } elseif ($anyReceived) {

                        $po->update([
                            'status' =>
                                'partially_received',
                        ]);
                    }


                    return $grn;
                }
            );


        return redirect()
            ->route(
                'pharmacy.grns.show',
                $grn
            )
            ->with(
                'success',
                'Goods Receipt Note completed successfully. Stock received into Central Store.'
            );
    }



    /**
     * View / Print GRN.
     */
    public function show(
        PharmacyGrn $pharmacyGrn
    ): View {

        $pharmacyGrn->load([
            'purchaseOrder',
            'supplier',
            'items',
            'createdBy',
        ]);


        return view(
            'pharmacy.grns.show',
            compact(
                'pharmacyGrn'
            )
        );
    }



    /**
     * Generate concurrency-safe GRN number.
     *
     * Caller must hold:
     * pharmacy_grn_number
     */
    private function generateGrnNumber(): string
    {
        $prefix =
            'GRN-'
            . now()->format(
                'Ymd'
            )
            . '-';


        $last =
            PharmacyGrn::query()
                ->where(
                    'grn_no',
                    'like',
                    $prefix . '%'
                )
                ->orderByDesc(
                    'id'
                )
                ->first();


        $next =
            1;


        if ($last) {

            $lastSequence =
                (int)
                substr(
                    $last->grn_no,
                    -6
                );


            $next =
                $lastSequence
                + 1;
        }


        return
            $prefix
            . str_pad(
                (string)
                $next,
                6,
                '0',
                STR_PAD_LEFT
            );
    }
}