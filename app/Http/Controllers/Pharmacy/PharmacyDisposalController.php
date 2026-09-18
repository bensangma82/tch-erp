<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\PharmacyDisposal;
use App\Models\PharmacyDisposalItem;
use App\Models\PharmacyStockBatch;
use App\Models\PharmacyStockLocation;
use App\Models\PharmacyStockLocationBalance;
use App\Models\PharmacyStockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PharmacyDisposalController extends Controller
{
    /**
     * Disposal Register.
     */
    public function index(
        Request $request
    ): View {

        $query =
            PharmacyDisposal::query()
                ->with([
                    'items',
                    'createdBy',
                ])
                ->orderByDesc(
                    'disposed_at'
                );


        /*
        |--------------------------------------------------------------------------
        | Date From
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'from_date'
            )
        ) {

            $query->whereDate(
                'disposed_at',
                '>=',
                $request->from_date
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Date To
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'to_date'
            )
        ) {

            $query->whereDate(
                'disposed_at',
                '<=',
                $request->to_date
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Disposal Reason
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'reason'
            )
        ) {

            $query->where(
                'reason',
                $request->reason
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

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
                        'disposal_no',
                        'ilike',
                        '%' . $search . '%'
                    )
                        ->orWhereHas(
                            'items',
                            function ($itemQuery) use ($search) {

                                $itemQuery
                                    ->where(
                                        'medicine_name',
                                        'ilike',
                                        '%' . $search . '%'
                                    )
                                    ->orWhere(
                                        'brand_name',
                                        'ilike',
                                        '%' . $search . '%'
                                    )
                                    ->orWhere(
                                        'batch_number',
                                        'ilike',
                                        '%' . $search . '%'
                                    );
                            }
                        );
                }
            );
        }


        $disposals =
            $query
                ->paginate(20)
                ->withQueryString();


        return view(
            'pharmacy.disposals.index',
            compact(
                'disposals'
            )
        );
    }



    /**
     * Show disposal form.
     */
    public function create(
        Request $request,
        PharmacyStockBatch $stockBatch
    ): View {

        $stockBatch->load(
            'medicine'
        );


        /*
        |--------------------------------------------------------------------------
        | Locations Holding this Batch
        |--------------------------------------------------------------------------
        |
        | Only locations with actual physical stock are offered.
        |
        */

        $locationBalances =
            PharmacyStockLocationBalance::query()
                ->with(
                    'location'
                )
                ->where(
                    'pharmacy_stock_batch_id',
                    $stockBatch->id
                )
                ->where(
                    'quantity_available',
                    '>',
                    0
                )
                ->whereHas(
                    'location',
                    function ($query) {

                        $query->where(
                            'is_active',
                            true
                        );
                    }
                )
                ->get()
                ->sortBy(
                    fn ($balance) =>
                        $balance->location?->name
                )
                ->values();


        /*
        |--------------------------------------------------------------------------
        | Optional Preselected Location
        |--------------------------------------------------------------------------
        */

        $selectedLocationId =
            $request->integer(
                'location_id'
            );


        return view(
            'pharmacy.disposals.create',
            compact(
                'stockBatch',
                'locationBalances',
                'selectedLocationId'
            )
        );
    }



    /**
     * Process stock disposal / write-off.
     */
    public function store(
        Request $request,
        PharmacyStockBatch $stockBatch
    ): RedirectResponse {

        $validated =
            $request->validate([

                'pharmacy_stock_location_id' => [
                    'required',
                    'integer',
                    'exists:pharmacy_stock_locations,id',
                ],

                'quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'reason' => [
                    'required',
                    'string',
                    'in:expired,damaged,contaminated,broken,recall,other',
                ],

                'remarks' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ]);


        $disposal =
            DB::transaction(
                function () use (
                    $validated,
                    $request,
                    $stockBatch
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Lock Hospital-wide Stock Batch
                    |--------------------------------------------------------------------------
                    */

                    $batch =
                        PharmacyStockBatch::query()
                            ->with(
                                'medicine'
                            )
                            ->whereKey(
                                $stockBatch->id
                            )
                            ->lockForUpdate()
                            ->firstOrFail();


                    /*
                    |--------------------------------------------------------------------------
                    | Validate Location
                    |--------------------------------------------------------------------------
                    */

                    $location =
                        PharmacyStockLocation::query()
                            ->whereKey(
                                $validated[
                                    'pharmacy_stock_location_id'
                                ]
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->firstOrFail();


                    /*
                    |--------------------------------------------------------------------------
                    | Lock Location / Batch Pair
                    |--------------------------------------------------------------------------
                    */

                    DB::statement(
                        "SELECT pg_advisory_xact_lock(hashtext(?))",
                        [
                            'pharmacy_location_balance_'
                            . $location->id
                            . '_'
                            . $batch->id,
                        ]
                    );


                    $locationBalance =
                        PharmacyStockLocationBalance::query()
                            ->where(
                                'pharmacy_stock_location_id',
                                $location->id
                            )
                            ->where(
                                'pharmacy_stock_batch_id',
                                $batch->id
                            )
                            ->lockForUpdate()
                            ->first();


                    if (! $locationBalance) {

                        throw ValidationException::withMessages([
                            'pharmacy_stock_location_id' =>
                                'This batch has no stock at '
                                . $location->name
                                . '.',
                        ]);
                    }


                    $availableAtLocation =
                        (int)
                        $locationBalance
                            ->quantity_available;


                    $hospitalAvailable =
                        (int)
                        $batch
                            ->quantity_available;


                    $quantity =
                        (int)
                        $validated[
                            'quantity'
                        ];


                    /*
                    |--------------------------------------------------------------------------
                    | Prevent Location Over-disposal
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $quantity
                        > $availableAtLocation
                    ) {

                        throw ValidationException::withMessages([
                            'quantity' =>
                                'Only '
                                . $availableAtLocation
                                . ' units are available at '
                                . $location->name
                                . '.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Global Integrity Check
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $quantity
                        > $hospitalAvailable
                    ) {

                        throw ValidationException::withMessages([
                            'quantity' =>
                                'Hospital-wide stock is inconsistent for batch '
                                . $batch->batch_number
                                . '. Please review stock before disposal.',
                        ]);
                    }


                    $newLocationBalance =
                        $availableAtLocation
                        - $quantity;


                    $newGlobalBalance =
                        $hospitalAvailable
                        - $quantity;


                    /*
                    |--------------------------------------------------------------------------
                    | Write-off Value
                    |--------------------------------------------------------------------------
                    |
                    | Disposal loss uses purchase cost.
                    |
                    */

                    $purchasePrice =
                        round(
                            (float)
                            $batch
                                ->purchase_price,
                            2
                        );


                    $stockValue =
                        round(
                            $purchasePrice
                            * $quantity,
                            2
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Lock Disposal Number Generation
                    |--------------------------------------------------------------------------
                    */

                    DB::statement(
                        "SELECT pg_advisory_xact_lock(hashtext('pharmacy_disposal_number'))"
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Disposal Header
                    |--------------------------------------------------------------------------
                    */

                    $headerRemarks =
                        $this->buildRemarks(
                            $validated[
                                'remarks'
                            ]
                            ?? null,
                            $location->name
                        );


                    $disposal =
                        PharmacyDisposal::create([

                            'disposal_no' =>
                                $this->generateDisposalNumber(),

                            'disposed_at' =>
                                now(),

                            'reason' =>
                                $validated[
                                    'reason'
                                ],

                            'remarks' =>
                                $headerRemarks,

                            'status' =>
                                'completed',

                            'created_by' =>
                                $request
                                    ->user()
                                    ?->id,
                        ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Disposal Item Snapshot
                    |--------------------------------------------------------------------------
                    */

                    PharmacyDisposalItem::create([

                        'pharmacy_disposal_id' =>
                            $disposal->id,

                        'medicine_id' =>
                            $batch
                                ->medicine_id,

                        'pharmacy_stock_batch_id' =>
                            $batch->id,

                        'medicine_code' =>
                            $batch
                                ->medicine
                                ?->code,

                        'medicine_name' =>
                            $batch
                                ->medicine
                                ?->generic_name
                            ?? 'Medicine',

                        'brand_name' =>
                            $batch
                                ->medicine
                                ?->brand_name,

                        'strength' =>
                            $batch
                                ->medicine
                                ?->strength,

                        'unit' =>
                            $batch
                                ->medicine
                                ?->unit,

                        'batch_number' =>
                            $batch
                                ->batch_number,

                        'expiry_date' =>
                            $batch
                                ->expiry_date,

                        'quantity' =>
                            $quantity,

                        'purchase_price' =>
                            $purchasePrice,

                        'stock_value' =>
                            $stockValue,

                        'reason' =>
                            $validated[
                                'reason'
                            ],

                        'remarks' =>
                            $this->buildRemarks(
                                $validated[
                                    'remarks'
                                ]
                                ?? null,
                                $location->name
                            ),
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Reduce Physical Location Stock
                    |--------------------------------------------------------------------------
                    */

                    $locationBalance->update([

                        'quantity_available' =>
                            $newLocationBalance,
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Reduce Hospital-wide Stock
                    |--------------------------------------------------------------------------
                    */

                    $batch->update([

                        'quantity_available' =>
                            $newGlobalBalance,
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Hospital-wide Stock Movement Ledger
                    |--------------------------------------------------------------------------
                    */

                    PharmacyStockMovement::create([

                        'pharmacy_stock_batch_id' =>
                            $batch->id,

                        'movement_type' =>
                            'disposal',

                        'quantity' =>
                            $quantity,

                        'balance_after' =>
                            $newGlobalBalance,

                        'reference_type' =>
                            'pharmacy_disposal',

                        'reference_id' =>
                            $disposal->id,

                        'remarks' =>
                            'Disposed from '
                            . $location->name
                            . ' under '
                            . $disposal->disposal_no
                            . ' | Reason: '
                            . $this->reasonLabel(
                                $validated[
                                    'reason'
                                ]
                            )
                            . ' | '
                            . $location->name
                            . ': '
                            . $availableAtLocation
                            . ' → '
                            . $newLocationBalance
                            . ' | Hospital: '
                            . $hospitalAvailable
                            . ' → '
                            . $newGlobalBalance
                            . ' | Write-off value: ₹'
                            . number_format(
                                $stockValue,
                                2,
                                '.',
                                ''
                            ),

                        'created_by' =>
                            $request
                                ->user()
                                ?->id,

                        'movement_at' =>
                            now(),
                    ]);


                    return $disposal;
                }
            );


        return redirect()
            ->route(
                'pharmacy.disposals.receipt',
                $disposal
            )
            ->with(
                'success',
                'Stock disposal completed successfully.'
            );
    }



    /**
     * Printable disposal record.
     */
    public function receipt(
        PharmacyDisposal $pharmacyDisposal
    ): View {

        $pharmacyDisposal->load([
            'items',
            'createdBy',
        ]);


        return view(
            'pharmacy.disposals.receipt',
            compact(
                'pharmacyDisposal'
            )
        );
    }



    /**
     * Generate concurrency-safe disposal number.
     *
     * Example:
     * PHD-20260914-000001
     *
     * Caller must hold:
     * pharmacy_disposal_number
     */
    private function generateDisposalNumber(): string
    {
        $prefix =
            'PHD-'
            . now()->format(
                'Ymd'
            )
            . '-';


        $last =
            PharmacyDisposal::query()
                ->where(
                    'disposal_no',
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
                    $last
                        ->disposal_no,
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



    /**
     * Add physical stock location to disposal remarks.
     */
    private function buildRemarks(
        ?string $remarks,
        string $locationName
    ): string {

        $parts =
            [
                'Location: '
                . $locationName,
            ];


        if (
            $remarks !== null
            &&
            trim(
                $remarks
            ) !== ''
        ) {

            $parts[] =
                trim(
                    $remarks
                );
        }


        return implode(
            ' | ',
            $parts
        );
    }



    /**
     * Human-readable disposal reason.
     */
    private function reasonLabel(
        string $reason
    ): string {

        return match (
            $reason
        ) {

            'expired' =>
                'Expired Stock',

            'damaged' =>
                'Damaged Stock',

            'contaminated' =>
                'Contaminated Stock',

            'broken' =>
                'Broken / Spillage',

            'recall' =>
                'Product Recall',

            'other' =>
                'Other',

            default =>
                ucwords(
                    str_replace(
                        '_',
                        ' ',
                        $reason
                    )
                ),
        };
    }
}