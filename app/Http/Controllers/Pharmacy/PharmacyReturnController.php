<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\IpBillingAccount;
use App\Models\IpBillingCharge;
use App\Models\PharmacyReturn;
use App\Models\PharmacyReturnItem;
use App\Models\PharmacySale;
use App\Models\PharmacySaleItem;
use App\Models\PharmacyStockLocation;
use App\Models\PharmacyStockLocationBalance;
use App\Models\PharmacyStockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PharmacyReturnController extends Controller
{
    /**
     * Show patient-return form.
     */
    public function create(
        PharmacySale $sale
    ) {
        $sale->load([
            'patient',
            'encounter.department',
            'encounter.doctor',
            'items.medicine',
            'items.stockBatch',
            'returns.items',
        ]);


        if (
            ! in_array(
                $sale->status,
                [
                    'completed',
                    'credit',
                ],
                true
            )
        ) {
            abort(
                422,
                'Only completed or credit pharmacy sales can be returned.'
            );
        }


        $returnableItems =
            $sale->items
                ->map(
                    function (
                        PharmacySaleItem $item
                    ) {

                        $alreadyReturned =
                            PharmacyReturnItem::query()
                                ->where(
                                    'pharmacy_sale_item_id',
                                    $item->id
                                )
                                ->whereHas(
                                    'pharmacyReturn',
                                    function ($query) {

                                        $query->where(
                                            'status',
                                            'completed'
                                        );
                                    }
                                )
                                ->sum(
                                    'quantity'
                                );


                        $returnableQuantity =
                            max(
                                0,
                                (int)
                                $item->quantity
                                -
                                (int)
                                $alreadyReturned
                            );


                        $item->already_returned =
                            (int)
                            $alreadyReturned;


                        $item->returnable_quantity =
                            (int)
                            $returnableQuantity;


                        return $item;
                    }
                );


        return view(
            'pharmacy.returns.create',
            compact(
                'sale',
                'returnableItems'
            )
        );
    }



    /**
     * Process patient return.
     */
    public function store(
        Request $request,
        PharmacySale $sale
    ) {
        $validated =
            $request->validate([

                'reason' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'remarks' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],

                'refund_mode' => [
                    'nullable',
                    'in:cash,upi,card,credit,mhis,none',
                ],

                'transaction_reference' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'items' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'items.*.sale_item_id' => [
                    'required',
                    'integer',
                ],

                'items.*.quantity' => [
                    'required',
                    'integer',
                    'min:0',
                ],
            ]);


        /*
        |--------------------------------------------------------------------------
        | Pharmacy Location
        |--------------------------------------------------------------------------
        */

        $pharmacyLocation =
            PharmacyStockLocation::query()
                ->where(
                    'code',
                    'PHARMACY'
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();


        if (! $pharmacyLocation) {

            throw ValidationException::withMessages([
                'items' =>
                    'Pharmacy stock location is not configured.',
            ]);
        }


        $pharmacyReturn =
            DB::transaction(
                function () use (
                    $validated,
                    $sale,
                    $request,
                    $pharmacyLocation
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Lock Original Sale
                    |--------------------------------------------------------------------------
                    */

                    $lockedSale =
                        PharmacySale::query()
                            ->whereKey(
                                $sale->id
                            )
                            ->lockForUpdate()
                            ->firstOrFail();


                    $lockedSale->load([
                        'items.medicine',
                        'items.stockBatch',
                    ]);


                    if (
                        ! in_array(
                            $lockedSale->status,
                            [
                                'completed',
                                'credit',
                            ],
                            true
                        )
                    ) {

                        throw ValidationException::withMessages([
                            'sale' =>
                                'This pharmacy sale cannot be returned.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Inpatient Billing Account
                    |--------------------------------------------------------------------------
                    |
                    | For an inpatient pharmacy sale, the return must reverse the
                    | corresponding running-bill charge. The original charge is
                    | never edited or deleted; a separate negative ledger entry is
                    | created for the return.
                    |
                    */

                    $ipBillingAccount =
                        null;


                    if (
                        $lockedSale->admission_id
                    ) {

                        $ipBillingAccount =
                            IpBillingAccount::query()
                                ->where(
                                    'admission_id',
                                    $lockedSale->admission_id
                                )
                                ->lockForUpdate()
                                ->first();


                        if (! $ipBillingAccount) {

                            throw ValidationException::withMessages([
                                'sale' =>
                                    'No inpatient billing account was found for this pharmacy sale.',
                            ]);
                        }


                        if (
                            $ipBillingAccount->status
                            !== 'open'
                        ) {

                            throw ValidationException::withMessages([
                                'sale' =>
                                    'Medicines cannot be returned against a finalized inpatient bill.',
                            ]);
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Requested Return Quantities
                    |--------------------------------------------------------------------------
                    */

                    $requestedItems =
                        collect(
                            $validated['items']
                        )
                            ->keyBy(
                                'sale_item_id'
                            );


                    $returnLines =
                        [];


                    foreach (
                        $lockedSale->items
                        as $saleItem
                    ) {

                        $requested =
                            $requestedItems->get(
                                $saleItem->id
                            );


                        if (! $requested) {
                            continue;
                        }


                        $returnQty =
                            (int)
                            (
                                $requested['quantity']
                                ?? 0
                            );


                        if (
                            $returnQty <= 0
                        ) {
                            continue;
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Prevent Excess Return
                        |--------------------------------------------------------------------------
                        */

                        $alreadyReturned =
                            PharmacyReturnItem::query()
                                ->where(
                                    'pharmacy_sale_item_id',
                                    $saleItem->id
                                )
                                ->whereHas(
                                    'pharmacyReturn',
                                    function ($query) {

                                        $query->where(
                                            'status',
                                            'completed'
                                        );
                                    }
                                )
                                ->sum(
                                    'quantity'
                                );


                        $remainingReturnable =
                            max(
                                0,
                                (int)
                                $saleItem->quantity
                                -
                                (int)
                                $alreadyReturned
                            );


                        if (
                            $returnQty
                            > $remainingReturnable
                        ) {

                            throw ValidationException::withMessages([
                                'items' =>
                                    'Return quantity exceeds the remaining returnable quantity for '
                                    . $saleItem->medicine_name
                                    . '.',
                            ]);
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Lock Hospital-wide Stock Batch
                        |--------------------------------------------------------------------------
                        */

                        $stockBatch =
                            $saleItem
                                ->stockBatch()
                                ->lockForUpdate()
                                ->firstOrFail();


                        /*
                        |--------------------------------------------------------------------------
                        | Lock Pharmacy Location/Batch Pair
                        |--------------------------------------------------------------------------
                        |
                        | This protects creation/update of the location balance
                        | when two returns involving the same batch occur
                        | concurrently.
                        |
                        */

                        DB::statement(
                            "SELECT pg_advisory_xact_lock(hashtext(?))",
                            [
                                'pharmacy_location_balance_'
                                . $pharmacyLocation->id
                                . '_'
                                . $stockBatch->id,
                            ]
                        );


                        $pharmacyBalance =
                            PharmacyStockLocationBalance::query()
                                ->where(
                                    'pharmacy_stock_location_id',
                                    $pharmacyLocation->id
                                )
                                ->where(
                                    'pharmacy_stock_batch_id',
                                    $stockBatch->id
                                )
                                ->lockForUpdate()
                                ->first();


                        /*
                        |--------------------------------------------------------------------------
                        | Calculate Refund Proportionally
                        |--------------------------------------------------------------------------
                        |
                        | Original sale remains immutable.
                        |
                        */

                        $soldQty =
                            max(
                                1,
                                (int)
                                $saleItem->quantity
                            );


                        $ratio =
                            $returnQty
                            / $soldQty;


                        $grossAmount =
                            round(
                                (float)
                                $saleItem->amount
                                * $ratio,
                                2
                            );


                        $taxableAmount =
                            round(
                                (float)
                                $saleItem->taxable_amount
                                * $ratio,
                                2
                            );


                        $cgstAmount =
                            round(
                                (float)
                                $saleItem->cgst_amount
                                * $ratio,
                                2
                            );


                        $sgstAmount =
                            round(
                                (float)
                                $saleItem->sgst_amount
                                * $ratio,
                                2
                            );


                        $igstAmount =
                            round(
                                (float)
                                $saleItem->igst_amount
                                * $ratio,
                                2
                            );


                        $refundAmount =
                            round(
                                $taxableAmount
                                +
                                $cgstAmount
                                +
                                $sgstAmount
                                +
                                $igstAmount,
                                2
                            );


                        /*
                        |--------------------------------------------------------------------------
                        | Fallback for Older Pre-GST Sales
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $refundAmount <= 0
                            &&
                            $grossAmount > 0
                        ) {

                            $refundAmount =
                                $grossAmount;
                        }


                        $returnLines[] = [

                            'sale_item' =>
                                $saleItem,

                            'stock_batch' =>
                                $stockBatch,

                            'pharmacy_balance' =>
                                $pharmacyBalance,

                            'quantity' =>
                                $returnQty,

                            'gross_amount' =>
                                $grossAmount,

                            'taxable_amount' =>
                                $taxableAmount,

                            'cgst_amount' =>
                                $cgstAmount,

                            'sgst_amount' =>
                                $sgstAmount,

                            'igst_amount' =>
                                $igstAmount,

                            'refund_amount' =>
                                $refundAmount,
                        ];
                    }


                    if (
                        empty(
                            $returnLines
                        )
                    ) {

                        throw ValidationException::withMessages([
                            'items' =>
                                'Please enter a return quantity for at least one medicine.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Return Totals
                    |--------------------------------------------------------------------------
                    */

                    $grossTotal =
                        round(
                            collect(
                                $returnLines
                            )
                                ->sum(
                                    'gross_amount'
                                ),
                            2
                        );


                    $taxableTotal =
                        round(
                            collect(
                                $returnLines
                            )
                                ->sum(
                                    'taxable_amount'
                                ),
                            2
                        );


                    $cgstTotal =
                        round(
                            collect(
                                $returnLines
                            )
                                ->sum(
                                    'cgst_amount'
                                ),
                            2
                        );


                    $sgstTotal =
                        round(
                            collect(
                                $returnLines
                            )
                                ->sum(
                                    'sgst_amount'
                                ),
                            2
                        );


                    $igstTotal =
                        round(
                            collect(
                                $returnLines
                            )
                                ->sum(
                                    'igst_amount'
                                ),
                            2
                        );


                    $refundTotal =
                        round(
                            collect(
                                $returnLines
                            )
                                ->sum(
                                    'refund_amount'
                                ),
                            2
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Concurrency-safe Return Number
                    |--------------------------------------------------------------------------
                    */

                    DB::statement(
                        "SELECT pg_advisory_xact_lock(hashtext('pharmacy_return_number'))"
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Create Return Header
                    |--------------------------------------------------------------------------
                    */

                    $return =
                        PharmacyReturn::create([

                            'return_no' =>
                                $this->generateReturnNumber(),

                            'pharmacy_sale_id' =>
                                $lockedSale->id,

                            'patient_id' =>
                                $lockedSale->patient_id,

                            'encounter_id' =>
                                $lockedSale->encounter_id,

                            'returned_at' =>
                                now(),

                            'gross_amount' =>
                                $grossTotal,

                            'taxable_amount' =>
                                $taxableTotal,

                            'cgst_amount' =>
                                $cgstTotal,

                            'sgst_amount' =>
                                $sgstTotal,

                            'igst_amount' =>
                                $igstTotal,

                            'refund_amount' =>
                                $refundTotal,

                            'refund_mode' =>
                                $lockedSale->admission_id
                                    ? 'none'
                                    : (
                                        $validated[
                                            'refund_mode'
                                        ]
                                        ?? 'none'
                                    ),

                            'transaction_reference' =>
                                $lockedSale->admission_id
                                    ? null
                                    : (
                                        $validated[
                                            'transaction_reference'
                                        ]
                                        ?? null
                                    ),

                            'reason' =>
                                $validated[
                                    'reason'
                                ],

                            'remarks' =>
                                $validated[
                                    'remarks'
                                ]
                                ?? null,

                            'status' =>
                                'completed',

                            'created_by' =>
                                $request
                                    ->user()
                                    ->id,
                        ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Restore Returned Stock
                    |--------------------------------------------------------------------------
                    |
                    | Patient return physically enters Pharmacy.
                    |
                    | Therefore:
                    |
                    | 1. Pharmacy location balance increases
                    | 2. Hospital-wide batch balance increases
                    |
                    */

                    foreach (
                        $returnLines
                        as $line
                    ) {

                        /** @var PharmacySaleItem $saleItem */
                        $saleItem =
                            $line[
                                'sale_item'
                            ];


                        $stockBatch =
                            $line[
                                'stock_batch'
                            ];


                        $pharmacyBalance =
                            $line[
                                'pharmacy_balance'
                            ];


                        $returnQty =
                            (int)
                            $line[
                                'quantity'
                            ];


                        /*
                        |--------------------------------------------------------------------------
                        | Existing Pharmacy Location Balance
                        |--------------------------------------------------------------------------
                        */

                        if ($pharmacyBalance) {

                            $currentPharmacyBalance =
                                (int)
                                $pharmacyBalance
                                    ->quantity_available;


                            $newPharmacyBalance =
                                $currentPharmacyBalance
                                + $returnQty;


                            $pharmacyBalance->update([

                                'quantity_available' =>
                                    $newPharmacyBalance,
                            ]);

                        } else {

                            /*
                            |--------------------------------------------------------------------------
                            | First Stock for this Batch at Pharmacy Location
                            |--------------------------------------------------------------------------
                            */

                            $currentPharmacyBalance =
                                0;


                            $newPharmacyBalance =
                                $returnQty;


                            $pharmacyBalance =
                                PharmacyStockLocationBalance::create([

                                    'pharmacy_stock_location_id' =>
                                        $pharmacyLocation->id,

                                    'pharmacy_stock_batch_id' =>
                                        $stockBatch->id,

                                    'quantity_available' =>
                                        $newPharmacyBalance,

                                    'reorder_level' =>
                                        0,
                                ]);
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Restore Hospital-wide Stock
                        |--------------------------------------------------------------------------
                        */

                        $currentGlobalBalance =
                            (int)
                            $stockBatch
                                ->quantity_available;


                        $newGlobalBalance =
                            $currentGlobalBalance
                            + $returnQty;


                        $stockBatch->update([

                            'quantity_available' =>
                                $newGlobalBalance,
                        ]);


                        /*
                        |--------------------------------------------------------------------------
                        | Return Item
                        |--------------------------------------------------------------------------
                        */

                        $returnItem =
                            PharmacyReturnItem::create([

                            'pharmacy_return_id' =>
                                $return->id,

                            'pharmacy_sale_item_id' =>
                                $saleItem->id,

                            'medicine_id' =>
                                $saleItem->medicine_id,

                            'pharmacy_stock_batch_id' =>
                                $saleItem
                                    ->pharmacy_stock_batch_id,

                            'medicine_code' =>
                                $saleItem->medicine_code,

                            'medicine_name' =>
                                $saleItem->medicine_name,

                            'brand_name' =>
                                $saleItem->brand_name,

                            'strength' =>
                                $saleItem->strength,

                            'unit' =>
                                $saleItem->unit,

                            /*
                             * Current medicine master uses `hsn`.
                             */
                            'hsn_code' =>
                                $saleItem
                                    ->medicine
                                    ?->hsn,

                            'batch_number' =>
                                $saleItem->batch_number,

                            'quantity' =>
                                $returnQty,

                            'unit_price' =>
                                $saleItem->unit_price,

                            'gst_percent' =>
                                $saleItem->gst_percent,

                            'gross_amount' =>
                                $line[
                                    'gross_amount'
                                ],

                            'taxable_amount' =>
                                $line[
                                    'taxable_amount'
                                ],

                            'cgst_amount' =>
                                $line[
                                    'cgst_amount'
                                ],

                            'sgst_amount' =>
                                $line[
                                    'sgst_amount'
                                ],

                            'igst_amount' =>
                                $line[
                                    'igst_amount'
                                ],

                            'refund_amount' =>
                                $line[
                                    'refund_amount'
                                ],
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
                                'return',

                            'quantity' =>
                                $returnQty,

                            'balance_after' =>
                                $newGlobalBalance,

                            'reference_type' =>
                                'pharmacy_return',

                            'reference_id' =>
                                $return->id,

                            'remarks' =>
                                'Patient return to Pharmacy against sale '
                                . $lockedSale->sale_no
                                . '. Return '
                                . $return->return_no
                                . ' | Pharmacy balance: '
                                . $currentPharmacyBalance
                                . ' → '
                                . $newPharmacyBalance
                                . ' | Hospital balance: '
                                . $currentGlobalBalance
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
                        | Reverse Inpatient Pharmacy Charge
                        |--------------------------------------------------------------------------
                        |
                        | Keep the original inpatient charge immutable. A negative
                        | charge linked to this PharmacyReturnItem provides the
                        | audit trail and supports partial/multiple returns.
                        |
                        */

                        if ($ipBillingAccount) {

                            $originalCharge =
                                IpBillingCharge::query()
                                    ->where(
                                        'admission_id',
                                        $lockedSale->admission_id
                                    )
                                    ->where(
                                        'source_type',
                                        'pharmacy_sale_item'
                                    )
                                    ->where(
                                        'source_id',
                                        $saleItem->id
                                    )
                                    ->lockForUpdate()
                                    ->first();


                            if (! $originalCharge) {

                                throw ValidationException::withMessages([
                                    'items' =>
                                        'The inpatient billing charge for '
                                        . $saleItem->medicine_name
                                        . ' could not be found. The return was not posted.',
                                ]);
                            }


                            $duplicateReversal =
                                IpBillingCharge::query()
                                    ->where(
                                        'source_type',
                                        'pharmacy_return_item'
                                    )
                                    ->where(
                                        'source_id',
                                        $returnItem->id
                                    )
                                    ->exists();


                            if ($duplicateReversal) {

                                throw ValidationException::withMessages([
                                    'items' =>
                                        'Duplicate inpatient pharmacy-return reversal detected.',
                                ]);
                            }


                            $soldQty =
                                max(
                                    1,
                                    (int)
                                    $saleItem->quantity
                                );


                            $returnRatio =
                                $returnQty
                                / $soldQty;


                            $reversalDiscount =
                                round(
                                    (float)
                                    $originalCharge->discount
                                    * $returnRatio,
                                    2
                                );


                            $reversalAmount =
                                round(
                                    (float)
                                    $originalCharge->amount
                                    * $returnRatio,
                                    2
                                );


                            IpBillingCharge::create([

                                'ip_billing_account_id' =>
                                    $ipBillingAccount->id,

                                'admission_id' =>
                                    $lockedSale->admission_id,

                                'charge_date' =>
                                    now(),

                                'charge_type' =>
                                    'pharmacy_return',

                                'service_id' =>
                                    null,

                                'service_order_item_id' =>
                                    null,

                                'code' =>
                                    $return->return_no
                                    . '-'
                                    . $returnItem->id,

                                'description' =>
                                    'Medicine return: '
                                    . $originalCharge->description,

                                /*
                                 * Negative quantity makes the gross reversal:
                                 *
                                 * quantity × unit_price = negative gross
                                 */
                                'quantity' =>
                                    -1 * $returnQty,

                                'unit_price' =>
                                    $originalCharge->unit_price,

                                /*
                                 * Original discount is also reversed.
                                 *
                                 * net = gross - discount
                                 *
                                 * Example:
                                 * -100 - (-10) = -90
                                 */
                                'discount' =>
                                    -1 * $reversalDiscount,

                                'amount' =>
                                    -1 * $reversalAmount,

                                'source_type' =>
                                    'pharmacy_return_item',

                                'source_id' =>
                                    $returnItem->id,

                                'status' =>
                                    'active',

                                'remarks' =>
                                    'Reversal for IP pharmacy sale '
                                    . $lockedSale->sale_no
                                    . ', return '
                                    . $return->return_no
                                    . '. Reason: '
                                    . $validated['reason'],

                                'created_by' =>
                                    $request
                                        ->user()
                                        ->id,
                            ]);
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Recalculate Inpatient Running Bill
                    |--------------------------------------------------------------------------
                    */

                    if ($ipBillingAccount) {

                        $this->recalculateIpBillingAccount(
                            $ipBillingAccount
                        );
                    }


                    return $return;
                }
            );


        return redirect()
            ->route(
                'pharmacy.returns.receipt',
                $pharmacyReturn
            )
            ->with(
                'success',
                'Pharmacy return completed successfully.'
            );
    }



    /**
     * Printable return receipt.
     */
    public function receipt(
        PharmacyReturn $pharmacyReturn
    ) {
        $pharmacyReturn->load([
            'sale',
            'patient',
            'encounter.department',
            'encounter.doctor',
            'items',
            'createdBy',
        ]);


        return view(
            'pharmacy.returns.receipt',
            compact(
                'pharmacyReturn'
            )
        );
    }



    /**
     * Recalculate inpatient billing totals after a pharmacy return.
     */
    private function recalculateIpBillingAccount(
        IpBillingAccount $account
    ): void {

        $account->load([
            'charges',
            'advances',
            'mhisClaims',
        ]);


        $activeCharges =
            $account->charges
                ->where(
                    'status',
                    'active'
                );


        $subtotal =
            round(
                (float)
                $activeCharges->sum(
                    fn ($charge) =>
                        round(
                            (float) $charge->quantity
                            * (float) $charge->unit_price,
                            2
                        )
                ),
                2
            );


        $discountAmount =
            round(
                (float)
                $activeCharges->sum(
                    fn ($charge) =>
                        (float) $charge->discount
                ),
                2
            );


        $netAmount =
            round(
                (float)
                $activeCharges->sum(
                    fn ($charge) =>
                        (float) $charge->amount
                ),
                2
            );


        $advanceAmount =
            round(
                (float)
                $account->advances
                    ->where(
                        'status',
                        'active'
                    )
                    ->sum(
                        fn ($advance) =>
                            (float) $advance->amount
                    ),
                2
            );


        $paidAmount =
            round(
                (float)
                $account->paid_amount,
                2
            );


        $mhisApprovedAmount =
            round(
                (float)
                $account->mhisClaims
                    ->whereIn(
                        'status',
                        [
                            'approved',
                            'submitted',
                            'settled',
                        ]
                    )
                    ->sum(
                        fn ($claim) =>
                            (float) $claim->approved_amount
                    ),
                2
            );


        $balanceAmount =
            round(
                max(
                    $netAmount
                    - $advanceAmount
                    - $paidAmount
                    - $mhisApprovedAmount,
                    0
                ),
                2
            );


        $account->update([
            'subtotal' =>
                $subtotal,

            'discount_amount' =>
                $discountAmount,

            'net_amount' =>
                $netAmount,

            'advance_amount' =>
                $advanceAmount,

            'balance_amount' =>
                $balanceAmount,
        ]);
    }



    /**
     * Generate return number.
     *
     * Example:
     * PHR-20260914-000001
     *
     * Caller holds pharmacy_return_number
     * advisory transaction lock.
     */
    private function generateReturnNumber(): string
    {
        $prefix =
            'PHR-'
            . now()->format(
                'Ymd'
            )
            . '-';


        $last =
            PharmacyReturn::query()
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

            $lastNumber =
                (int)
                substr(
                    $last->return_no,
                    -6
                );


            $next =
                $lastNumber
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