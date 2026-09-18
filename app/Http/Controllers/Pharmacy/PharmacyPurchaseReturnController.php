<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\PharmacyGrn;
use App\Models\PharmacyGrnItem;
use App\Models\PharmacyPurchaseReturn;
use App\Models\PharmacyPurchaseReturnItem;
use App\Models\PharmacyStockBatch;
use App\Models\PharmacyStockLocation;
use App\Models\PharmacyStockLocationBalance;
use App\Models\PharmacyStockMovement;
use App\Models\PharmacySupplierPayable;
use App\Services\Pharmacy\PharmacySupplierPayableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PharmacyPurchaseReturnController extends Controller
{
    public function __construct(
        private readonly PharmacySupplierPayableService $payableService
    ) {
    }



    /**
     * Purchase Return Register.
     */
    public function index(
        Request $request
    ): View {

        $query =
            PharmacyPurchaseReturn::query()
                ->with([
                    'supplier',
                    'createdBy',
                ])
                ->withCount('items')
                ->orderByDesc(
                    'return_date'
                )
                ->orderByDesc(
                    'id'
                );


        if (
            $request->filled(
                'search'
            )
        ) {

            $search =
                trim(
                    $request->search
                );


            $query->where(
                function ($q) use ($search) {

                    $q->where(
                        'return_no',
                        'ilike',
                        '%' . $search . '%'
                    )
                        ->orWhere(
                            'supplier_credit_note_no',
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


        if (
            $request->filled(
                'from_date'
            )
        ) {

            $query->whereDate(
                'return_date',
                '>=',
                $request->from_date
            );
        }


        if (
            $request->filled(
                'to_date'
            )
        ) {

            $query->whereDate(
                'return_date',
                '<=',
                $request->to_date
            );
        }


        $returns =
            $query
                ->paginate(20)
                ->withQueryString();


        return view(
            'pharmacy.purchase-returns.index',
            compact(
                'returns'
            )
        );
    }



    /**
     * Create Purchase Return against completed GRN.
     */
    public function create(
        PharmacyGrn $pharmacyGrn
    ): View {

        $pharmacyGrn->load([
            'supplier',
            'purchaseOrder',
            'items.stockBatch',
            'items.medicine',
        ]);


        if (
            $pharmacyGrn->status
            !== 'completed'
        ) {

            abort(
                422,
                'Purchase return can only be created against a completed GRN.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Already Returned Quantities
        |--------------------------------------------------------------------------
        */

        $alreadyReturned =
            PharmacyPurchaseReturnItem::query()
                ->whereIn(
                    'pharmacy_grn_item_id',
                    $pharmacyGrn
                        ->items
                        ->pluck('id')
                )
                ->select(
                    'pharmacy_grn_item_id',
                    DB::raw(
                        'SUM(quantity_returned) as total_returned'
                    )
                )
                ->groupBy(
                    'pharmacy_grn_item_id'
                )
                ->pluck(
                    'total_returned',
                    'pharmacy_grn_item_id'
                );


        return view(
            'pharmacy.purchase-returns.create',
            compact(
                'pharmacyGrn',
                'alreadyReturned'
            )
        );
    }



    /**
     * Store Purchase Return.
     *
     * Supplier returns leave CENTRAL STORE.
     */
    public function store(
        Request $request,
        PharmacyGrn $pharmacyGrn
    ): RedirectResponse {

        $validated =
            $request->validate([

                'return_date' => [
                    'required',
                    'date',
                ],

                'supplier_credit_note_no' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'supplier_credit_note_date' => [
                    'nullable',
                    'date',
                ],

                'reason' => [
                    'required',
                    'string',
                    'in:damaged,wrong_item,excess_supply,quality_issue,recall,near_expiry,other',
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

                'items.*.grn_item_id' => [
                    'required',
                    'integer',
                    'exists:pharmacy_grn_items,id',
                ],

                'items.*.quantity_returned' => [
                    'required',
                    'integer',
                    'min:0',
                ],

                'items.*.reason' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'items.*.remarks' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ]);


        /*
        |--------------------------------------------------------------------------
        | Central Store Location
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


        $purchaseReturn =
            DB::transaction(
                function () use (
                    $validated,
                    $request,
                    $pharmacyGrn,
                    $centralStore
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Lock GRN
                    |--------------------------------------------------------------------------
                    */

                    $grn =
                        PharmacyGrn::query()
                            ->with([
                                'supplier',
                                'purchaseOrder',
                                'items.stockBatch',
                            ])
                            ->whereKey(
                                $pharmacyGrn->id
                            )
                            ->lockForUpdate()
                            ->firstOrFail();


                    if (
                        $grn->status
                        !== 'completed'
                    ) {

                        throw ValidationException::withMessages([
                            'items' =>
                                'This GRN is no longer available for purchase return.',
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
                                $grn
                                    ->supplier
                                    ?->state
                                ?? ''
                            )
                        );


                    $isInterState =
                        $supplierState !== ''
                        &&
                        $supplierState !== 'meghalaya';


                    /*
                    |--------------------------------------------------------------------------
                    | Validate All Return Lines
                    |--------------------------------------------------------------------------
                    */

                    $hasReturn =
                        false;


                    foreach (
                        $validated['items']
                        as $row
                    ) {

                        $quantity =
                            (int)
                            $row[
                                'quantity_returned'
                            ];


                        if ($quantity <= 0) {
                            continue;
                        }


                        $hasReturn =
                            true;


                        $grnItem =
                            $grn
                                ->items
                                ->firstWhere(
                                    'id',
                                    (int)
                                    $row[
                                        'grn_item_id'
                                    ]
                                );


                        if (! $grnItem) {

                            throw ValidationException::withMessages([
                                'items' =>
                                    'One or more items do not belong to this GRN.',
                            ]);
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Remaining Returnable Against GRN
                        |--------------------------------------------------------------------------
                        */

                        $previouslyReturned =
                            (int)
                            PharmacyPurchaseReturnItem::query()
                                ->where(
                                    'pharmacy_grn_item_id',
                                    $grnItem->id
                                )
                                ->sum(
                                    'quantity_returned'
                                );


                        $grnReturnable =
                            (int)
                            $grnItem
                                ->quantity_received
                            -
                            $previouslyReturned;


                        if (
                            $quantity
                            > $grnReturnable
                        ) {

                            throw ValidationException::withMessages([
                                'items' =>
                                    'Return quantity for '
                                    . $grnItem->medicine_name
                                    . ' exceeds the remaining returnable GRN quantity of '
                                    . max(
                                        0,
                                        $grnReturnable
                                    )
                                    . '.',
                            ]);
                        }
                    }


                    if (! $hasReturn) {

                        throw ValidationException::withMessages([
                            'items' =>
                                'Enter a return quantity for at least one medicine.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Document Number Lock
                    |--------------------------------------------------------------------------
                    */

                    DB::statement(
                        "SELECT pg_advisory_xact_lock(hashtext('pharmacy_purchase_return_number'))"
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Create Return Header
                    |--------------------------------------------------------------------------
                    */

                    $purchaseReturn =
                        PharmacyPurchaseReturn::create([

                            'return_no' =>
                                $this->generateReturnNumber(),

                            'pharmacy_supplier_id' =>
                                $grn
                                    ->pharmacy_supplier_id,

                            'return_date' =>
                                $validated[
                                    'return_date'
                                ],

                            'supplier_credit_note_no' =>
                                $validated[
                                    'supplier_credit_note_no'
                                ]
                                ?? null,

                            'supplier_credit_note_date' =>
                                $validated[
                                    'supplier_credit_note_date'
                                ]
                                ?? null,

                            'reason' =>
                                $validated[
                                    'reason'
                                ],

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
                    | Process Return Lines
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $validated['items']
                        as $row
                    ) {

                        $quantity =
                            (int)
                            $row[
                                'quantity_returned'
                            ];


                        if ($quantity <= 0) {
                            continue;
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Lock Exact GRN Item
                        |--------------------------------------------------------------------------
                        */

                        $grnItem =
                            PharmacyGrnItem::query()
                                ->whereKey(
                                    $row[
                                        'grn_item_id'
                                    ]
                                )
                                ->where(
                                    'pharmacy_grn_id',
                                    $grn->id
                                )
                                ->lockForUpdate()
                                ->firstOrFail();


                        /*
                        |--------------------------------------------------------------------------
                        | Re-check Previous Supplier Returns
                        |--------------------------------------------------------------------------
                        */

                        $previouslyReturned =
                            (int)
                            PharmacyPurchaseReturnItem::query()
                                ->where(
                                    'pharmacy_grn_item_id',
                                    $grnItem->id
                                )
                                ->sum(
                                    'quantity_returned'
                                );


                        $grnReturnable =
                            (int)
                            $grnItem
                                ->quantity_received
                            -
                            $previouslyReturned;


                        if (
                            $quantity
                            > $grnReturnable
                        ) {

                            throw ValidationException::withMessages([
                                'items' =>
                                    'Return quantity for '
                                    . $grnItem->medicine_name
                                    . ' exceeds the remaining GRN quantity.',
                            ]);
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Lock Hospital-wide Batch
                        |--------------------------------------------------------------------------
                        */

                        $stockBatch =
                            PharmacyStockBatch::query()
                                ->whereKey(
                                    $grnItem
                                        ->pharmacy_stock_batch_id
                                )
                                ->lockForUpdate()
                                ->firstOrFail();


                        /*
                        |--------------------------------------------------------------------------
                        | Lock Central Store Location/Batch Pair
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


                        /*
                        |--------------------------------------------------------------------------
                        | Supplier Return Must Be Physically in Central Store
                        |--------------------------------------------------------------------------
                        */

                        if (! $centralBalance) {

                            throw ValidationException::withMessages([
                                'items' =>
                                    'No Central Store stock exists for '
                                    . $grnItem->medicine_name
                                    . ', batch '
                                    . $stockBatch->batch_number
                                    . '. Transfer stock back to Central Store before returning it to the supplier.',
                            ]);
                        }


                        $oldCentralBalance =
                            (int)
                            $centralBalance
                                ->quantity_available;


                        if (
                            $quantity
                            > $oldCentralBalance
                        ) {

                            throw ValidationException::withMessages([
                                'items' =>
                                    'Only '
                                    . $oldCentralBalance
                                    . ' units of '
                                    . $grnItem->medicine_name
                                    . ' are available in Central Store for batch '
                                    . $stockBatch->batch_number
                                    . '. Transfer stock back to Central Store before returning a larger quantity.',
                            ]);
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Check Hospital-wide Balance
                        |--------------------------------------------------------------------------
                        */

                        $oldGlobalBalance =
                            (int)
                            $stockBatch
                                ->quantity_available;


                        if (
                            $quantity
                            > $oldGlobalBalance
                        ) {

                            throw ValidationException::withMessages([
                                'items' =>
                                    'Hospital-wide stock is inconsistent for batch '
                                    . $stockBatch->batch_number
                                    . '. Please review inventory before processing this return.',
                            ]);
                        }


                        $newCentralBalance =
                            $oldCentralBalance
                            - $quantity;


                        $newGlobalBalance =
                            $oldGlobalBalance
                            - $quantity;


                        /*
                        |--------------------------------------------------------------------------
                        | Financial Calculation
                        |--------------------------------------------------------------------------
                        |
                        | Preserve original GRN purchase price,
                        | discount percentage and GST percentage.
                        |
                        */

                        $purchasePrice =
                            round(
                                (float)
                                $grnItem
                                    ->purchase_price,
                                2
                            );


                        $discountPercent =
                            round(
                                (float)
                                $grnItem
                                    ->discount_percent,
                                2
                            );


                        $gstPercent =
                            round(
                                (float)
                                $grnItem
                                    ->gst_percent,
                                2
                            );


                        $gross =
                            round(
                                $quantity
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
                        | Create Purchase Return Item
                        |--------------------------------------------------------------------------
                        */

                        PharmacyPurchaseReturnItem::create([

                            'pharmacy_purchase_return_id' =>
                                $purchaseReturn->id,

                            'pharmacy_grn_item_id' =>
                                $grnItem->id,

                            'medicine_id' =>
                                $grnItem
                                    ->medicine_id,

                            'pharmacy_stock_batch_id' =>
                                $stockBatch->id,

                            'medicine_code' =>
                                $grnItem
                                    ->medicine_code,

                            'medicine_name' =>
                                $grnItem
                                    ->medicine_name,

                            'brand_name' =>
                                $grnItem
                                    ->brand_name,

                            'strength' =>
                                $grnItem
                                    ->strength,

                            'unit' =>
                                $grnItem
                                    ->unit,

                            'batch_number' =>
                                $grnItem
                                    ->batch_number,

                            'expiry_date' =>
                                $grnItem
                                    ->expiry_date,

                            'quantity_returned' =>
                                $quantity,

                            'purchase_price' =>
                                $purchasePrice,

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

                            'reason' =>
                                $row[
                                    'reason'
                                ]
                                ?? $validated[
                                    'reason'
                                ],

                            'remarks' =>
                                $row[
                                    'remarks'
                                ]
                                ?? null,
                        ]);


                        /*
                        |--------------------------------------------------------------------------
                        | Reduce Central Store Balance
                        |--------------------------------------------------------------------------
                        */

                        $centralBalance->update([

                            'quantity_available' =>
                                $newCentralBalance,
                        ]);


                        /*
                        |--------------------------------------------------------------------------
                        | Reduce Hospital-wide Balance
                        |--------------------------------------------------------------------------
                        */

                        $stockBatch->update([

                            'quantity_available' =>
                                $newGlobalBalance,
                        ]);


                        /*
                        |--------------------------------------------------------------------------
                        | Stock Ledger
                        |--------------------------------------------------------------------------
                        */

                        PharmacyStockMovement::create([

                            'pharmacy_stock_batch_id' =>
                                $stockBatch->id,

                            'movement_type' =>
                                'purchase_return',

                            'quantity' =>
                                $quantity,

                            'balance_after' =>
                                $newGlobalBalance,

                            'reference_type' =>
                                'pharmacy_purchase_return',

                            'reference_id' =>
                                $purchaseReturn->id,

                            'remarks' =>
                                'Returned to supplier from Central Store through '
                                . $purchaseReturn->return_no
                                . ' | GRN: '
                                . $grn->grn_no
                                . ' | Batch: '
                                . $stockBatch->batch_number
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
                        | Header Totals
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
                    | Update Return Header
                    |--------------------------------------------------------------------------
                    */

                    $purchaseReturn->update([

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
                    | Synchronise Existing Supplier Payable
                    |--------------------------------------------------------------------------
                    */

                    $payable =
                        PharmacySupplierPayable::query()
                            ->where(
                                'pharmacy_grn_id',
                                $grn->id
                            )
                            ->lockForUpdate()
                            ->first();


                    if ($payable) {

                        $this
                            ->payableService
                            ->recalculate(
                                $payable,
                                $request
                                    ->user()
                                    ->id
                            );
                    }


                    return $purchaseReturn;
                }
            );


        return redirect()
            ->route(
                'pharmacy.purchase-returns.show',
                $purchaseReturn
            )
            ->with(
                'success',
                'Purchase return completed successfully. Stock removed from Central Store.'
            );
    }



    /**
     * View / Print Purchase Return.
     */
    public function show(
        PharmacyPurchaseReturn $pharmacyPurchaseReturn
    ): View {

        $pharmacyPurchaseReturn->load([
            'supplier',
            'items.grnItem.grn',
            'items.stockBatch',
            'createdBy',
        ]);


        return view(
            'pharmacy.purchase-returns.show',
            compact(
                'pharmacyPurchaseReturn'
            )
        );
    }



    /**
     * Generate purchase return number.
     *
     * Example:
     * PR-20260914-000001
     *
     * Caller must hold:
     * pharmacy_purchase_return_number
     */
    private function generateReturnNumber(): string
    {
        $prefix =
            'PR-'
            . now()->format(
                'Ymd'
            )
            . '-';


        $last =
            PharmacyPurchaseReturn::query()
                ->where(
                    'return_no',
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
                    $last->return_no,
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