<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\PharmacyStockBatch;
use App\Models\PharmacyStockLocation;
use App\Models\PharmacyStockLocationBalance;
use App\Models\PharmacyStockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    /**
     * Show manual stock adjustment form.
     */
    public function create(
        PharmacyStockBatch $stockBatch
    ): View {

        $stockBatch->load(
            'medicine'
        );


        /*
        |--------------------------------------------------------------------------
        | Active Stock Locations
        |--------------------------------------------------------------------------
        */

        $locations =
            PharmacyStockLocation::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'name'
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Current Location Balances for this Batch
        |--------------------------------------------------------------------------
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
                ->get()
                ->keyBy(
                    'pharmacy_stock_location_id'
                );


        return view(
            'pharmacy.stock-adjustments.create',
            compact(
                'stockBatch',
                'locations',
                'locationBalances'
            )
        );
    }



    /**
     * Process manual stock adjustment.
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

                'adjustment_type' => [
                    'required',
                    'string',
                    'in:stock_in,correction_plus,correction_minus,damaged,expired_writeoff,lost,other_plus,other_minus',
                ],

                'quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],

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
            ]);


        DB::transaction(
            function () use (
                $validated,
                $request,
                $stockBatch
            ) {

                /*
                |--------------------------------------------------------------------------
                | Lock Hospital-wide Batch
                |--------------------------------------------------------------------------
                */

                $batch =
                    PharmacyStockBatch::query()
                        ->whereKey(
                            $stockBatch->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                /*
                |--------------------------------------------------------------------------
                | Validate Selected Location
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
                        ->first();


                if (! $location) {

                    throw ValidationException::withMessages([
                        'pharmacy_stock_location_id' =>
                            'The selected stock location is not active.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Protect Location / Batch Pair
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


                $oldLocationBalance =
                    $locationBalance
                        ? (int)
                            $locationBalance
                                ->quantity_available
                        : 0;


                $oldGlobalBalance =
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
                | Adjustment Direction
                |--------------------------------------------------------------------------
                */

                $increaseTypes = [
                    'stock_in',
                    'correction_plus',
                    'other_plus',
                ];


                $decreaseTypes = [
                    'correction_minus',
                    'damaged',
                    'expired_writeoff',
                    'lost',
                    'other_minus',
                ];


                /*
                |--------------------------------------------------------------------------
                | Increase
                |--------------------------------------------------------------------------
                */

                if (
                    in_array(
                        $validated[
                            'adjustment_type'
                        ],
                        $increaseTypes,
                        true
                    )
                ) {

                    $newLocationBalance =
                        $oldLocationBalance
                        + $quantity;


                    $newGlobalBalance =
                        $oldGlobalBalance
                        + $quantity;


                    /*
                    |--------------------------------------------------------------------------
                    | Create location balance if this batch has never been here
                    |--------------------------------------------------------------------------
                    */

                    if ($locationBalance) {

                        $locationBalance->update([

                            'quantity_available' =>
                                $newLocationBalance,
                        ]);

                    } else {

                        $locationBalance =
                            PharmacyStockLocationBalance::create([

                                'pharmacy_stock_location_id' =>
                                    $location->id,

                                'pharmacy_stock_batch_id' =>
                                    $batch->id,

                                'quantity_available' =>
                                    $newLocationBalance,

                                'reorder_level' =>
                                    0,
                            ]);
                    }


                    $batch->update([

                        'quantity_available' =>
                            $newGlobalBalance,
                    ]);


                /*
                |--------------------------------------------------------------------------
                | Decrease
                |--------------------------------------------------------------------------
                */

                } elseif (
                    in_array(
                        $validated[
                            'adjustment_type'
                        ],
                        $decreaseTypes,
                        true
                    )
                ) {

                    if (! $locationBalance) {

                        throw ValidationException::withMessages([
                            'quantity' =>
                                'There is no stock for this batch at '
                                . $location->name
                                . '.',
                        ]);
                    }


                    if (
                        $quantity
                        > $oldLocationBalance
                    ) {

                        throw ValidationException::withMessages([
                            'quantity' =>
                                'Adjustment quantity cannot exceed the stock available at '
                                . $location->name
                                . '. Available: '
                                . $oldLocationBalance
                                . '.',
                        ]);
                    }


                    if (
                        $quantity
                        > $oldGlobalBalance
                    ) {

                        throw ValidationException::withMessages([
                            'quantity' =>
                                'Hospital-wide stock is inconsistent for batch '
                                . $batch->batch_number
                                . '. Please review inventory before making this adjustment.',
                        ]);
                    }


                    $newLocationBalance =
                        $oldLocationBalance
                        - $quantity;


                    $newGlobalBalance =
                        $oldGlobalBalance
                        - $quantity;


                    $locationBalance->update([

                        'quantity_available' =>
                            $newLocationBalance,
                    ]);


                    $batch->update([

                        'quantity_available' =>
                            $newGlobalBalance,
                    ]);


                } else {

                    throw ValidationException::withMessages([
                        'adjustment_type' =>
                            'Invalid stock adjustment type.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Permanent Audit Ledger Entry
                |--------------------------------------------------------------------------
                |
                | Quantity remains positive.
                | movement_type determines whether this is an increase or decrease.
                |
                */

                PharmacyStockMovement::create([

                    'pharmacy_stock_batch_id' =>
                        $batch->id,

                    'movement_type' =>
                        $validated[
                            'adjustment_type'
                        ],

                    'quantity' =>
                        $quantity,

                    'balance_after' =>
                        $newGlobalBalance,

                    'reference_type' =>
                        'manual_adjustment',

                    'reference_id' =>
                        $batch->id,

                    'remarks' =>
                        $this->buildRemarks(
                            $validated[
                                'reason'
                            ],
                            $validated[
                                'remarks'
                            ]
                            ?? null,
                            $location->name,
                            $oldLocationBalance,
                            $newLocationBalance,
                            $oldGlobalBalance,
                            $newGlobalBalance
                        ),

                    'created_by' =>
                        $request
                            ->user()
                            ?->id,

                    'movement_at' =>
                        now(),
                ]);
            }
        );


        return redirect()
            ->route(
                'pharmacy.stock-batches.movements',
                $stockBatch
            )
            ->with(
                'success',
                'Stock adjustment completed successfully.'
            );
    }



    /**
     * Build permanent audit remarks.
     */
    private function buildRemarks(
        string $reason,
        ?string $remarks,
        string $locationName,
        int $oldLocationBalance,
        int $newLocationBalance,
        int $oldGlobalBalance,
        int $newGlobalBalance
    ): string {

        $parts = [

            'Reason: '
            . $reason,

            'Location: '
            . $locationName,

            $locationName
            . ' balance: '
            . $oldLocationBalance
            . ' → '
            . $newLocationBalance,

            'Hospital balance: '
            . $oldGlobalBalance
            . ' → '
            . $newGlobalBalance,
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
}