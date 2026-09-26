<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\PharmacyStockBatch;
use App\Models\PharmacyStockLocation;
use App\Models\PharmacyStockLocationBalance;
use App\Models\PharmacyStockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockBatchController extends Controller
{
    /**
     * Pharmacy stock list with search, status and operational filters.
     */
    public function index(Request $request): View
    {
        $search = trim(
            (string) $request->get('search')
        );

        $status = $request->get('status');

        $filter = $request->get('filter');


        /*
         * Only allow known operational filters.
         */
        $allowedFilters = [
            'low_stock',
            'out_of_stock',
            'near_expiry',
            'expired',
        ];


        if (
            $filter !== null
            && ! in_array(
                $filter,
                $allowedFilters,
                true
            )
        ) {
            $filter = null;
        }


        $today = today();

        $nearExpiryDate =
            $today->copy()->addDays(90);


        $batches =
            PharmacyStockBatch::query()
                ->with('medicine')


                /*
                 * -----------------------------------------------------
                 * SEARCH
                 * -----------------------------------------------------
                 */
                ->when(
                    $search !== '',
                    function ($query) use ($search) {

                        $query->where(
                            function ($q) use ($search) {

                                $q->where(
                                    'batch_number',
                                    'ilike',
                                    '%'.$search.'%'
                                )
                                    ->orWhereHas(
                                        'medicine',
                                        function ($medicineQuery) use ($search) {

                                            $medicineQuery
                                                ->where(
                                                    'code',
                                                    'ilike',
                                                    '%'.$search.'%'
                                                )
                                                ->orWhere(
                                                    'generic_name',
                                                    'ilike',
                                                    '%'.$search.'%'
                                                )
                                                ->orWhere(
                                                    'brand_name',
                                                    'ilike',
                                                    '%'.$search.'%'
                                                );
                                        }
                                    );
                            }
                        );
                    }
                )


                /*
                 * -----------------------------------------------------
                 * ACTIVE / INACTIVE STATUS
                 * -----------------------------------------------------
                 */
                ->when(
                    $status === 'active',
                    fn ($query) =>
                        $query->where(
                            'is_active',
                            true
                        )
                )

                ->when(
                    $status === 'inactive',
                    fn ($query) =>
                        $query->where(
                            'is_active',
                            false
                        )
                )


                /*
                 * -----------------------------------------------------
                 * LOW STOCK
                 *
                 * Active batch
                 * quantity > 0
                 * quantity <= reorder level
                 * -----------------------------------------------------
                 */
                ->when(
                    $filter === 'low_stock',
                    function ($query) {

                        $query
                            ->where(
                                'is_active',
                                true
                            )
                            ->where(
                                'quantity_available',
                                '>',
                                0
                            )
                            ->whereColumn(
                                'quantity_available',
                                '<=',
                                'reorder_level'
                            );
                    }
                )


                /*
                 * -----------------------------------------------------
                 * OUT OF STOCK
                 * -----------------------------------------------------
                 */
                ->when(
                    $filter === 'out_of_stock',
                    function ($query) {

                        $query
                            ->where(
                                'is_active',
                                true
                            )
                            ->where(
                                'quantity_available',
                                '<=',
                                0
                            );
                    }
                )


                /*
                 * -----------------------------------------------------
                 * NEAR EXPIRY
                 *
                 * Active batch
                 * stock still available
                 * not expired
                 * expiry within next 90 days
                 * -----------------------------------------------------
                 */
                ->when(
                    $filter === 'near_expiry',
                    function ($query) use (
                        $today,
                        $nearExpiryDate
                    ) {

                        $query
                            ->where(
                                'is_active',
                                true
                            )
                            ->where(
                                'quantity_available',
                                '>',
                                0
                            )
                            ->whereNotNull(
                                'expiry_date'
                            )
                            ->whereDate(
                                'expiry_date',
                                '>=',
                                $today
                            )
                            ->whereDate(
                                'expiry_date',
                                '<=',
                                $nearExpiryDate
                            );
                    }
                )


                /*
                 * -----------------------------------------------------
                 * EXPIRED STOCK
                 *
                 * Only batches with remaining physical stock are
                 * considered an operational expired-stock alert.
                 * -----------------------------------------------------
                 */
                ->when(
                    $filter === 'expired',
                    function ($query) use ($today) {

                        $query
                            ->where(
                                'is_active',
                                true
                            )
                            ->where(
                                'quantity_available',
                                '>',
                                0
                            )
                            ->whereNotNull(
                                'expiry_date'
                            )
                            ->whereDate(
                                'expiry_date',
                                '<',
                                $today
                            );
                    }
                )


                /*
                 * NULL expiry dates are shown last.
                 */
                ->orderByRaw(
                    'CASE
                        WHEN expiry_date IS NULL THEN 1
                        ELSE 0
                    END'
                )

                ->orderBy(
                    'expiry_date'
                )

                ->orderBy(
                    'batch_number'
                )

                ->paginate(25)

                ->withQueryString();


        /*
         * ---------------------------------------------------------
         * COUNTS FOR FILTER TABS
         * ---------------------------------------------------------
         */


        $lowStockCount =
            PharmacyStockBatch::query()
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'quantity_available',
                    '>',
                    0
                )
                ->whereColumn(
                    'quantity_available',
                    '<=',
                    'reorder_level'
                )
                ->count();


        $outOfStockCount =
            PharmacyStockBatch::query()
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'quantity_available',
                    '<=',
                    0
                )
                ->count();


        $nearExpiryCount =
            PharmacyStockBatch::query()
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'quantity_available',
                    '>',
                    0
                )
                ->whereNotNull(
                    'expiry_date'
                )
                ->whereDate(
                    'expiry_date',
                    '>=',
                    $today
                )
                ->whereDate(
                    'expiry_date',
                    '<=',
                    $nearExpiryDate
                )
                ->count();


        $expiredCount =
            PharmacyStockBatch::query()
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'quantity_available',
                    '>',
                    0
                )
                ->whereNotNull(
                    'expiry_date'
                )
                ->whereDate(
                    'expiry_date',
                    '<',
                    $today
                )
                ->count();


        return view(
            'pharmacy.stock-batches.index',
            compact(
                'batches',
                'search',
                'status',
                'filter',
                'lowStockCount',
                'outOfStockCount',
                'nearExpiryCount',
                'expiredCount'
            )
        );
    }



    /**
     * Show new stock batch form.
     */
    public function create(): View
    {
        $medicines =
            Medicine::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'generic_name'
                )
                ->orderBy(
                    'brand_name'
                )
                ->get();


        return view(
            'pharmacy.stock-batches.create',
            compact(
                'medicines'
            )
        );
    }



    /**
     * Save a new stock batch and opening ledger movement.
     */
    public function store(
        Request $request
    ): RedirectResponse {

        $validated =
            $request->validate([

                'medicine_id' => [
                    'required',
                    'integer',
                    'exists:medicines,id',
                ],

                'batch_number' => [
                    'required',
                    'string',
                    'max:100',

                    Rule::unique(
                        'pharmacy_stock_batches',
                        'batch_number'
                    )->where(
                        fn ($query) =>
                            $query->where(
                                'medicine_id',
                                $request->integer(
                                    'medicine_id'
                                )
                            )
                    ),
                ],

                'expiry_date' => [
                    'nullable',
                    'date',
                ],

                'purchase_price' => [
                    'required',
                    'numeric',
                    'min:0',
                ],

                'selling_price' => [
                    'required',
                    'numeric',
                    'min:0',
                ],

                'quantity_received' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'reorder_level' => [
                    'required',
                    'integer',
                    'min:0',
                ],

                'received_date' => [
                    'required',
                    'date',
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

            return back()
                ->withInput()
                ->withErrors([
                    'quantity_received' =>
                        'Central Store stock location is not configured.',
                ]);
        }

                DB::transaction(
            function () use (
                $validated,
                $request,
                $centralStore
            ) {

                $stockBatch =
                    PharmacyStockBatch::create([

                        'medicine_id' =>
                            $validated[
                                'medicine_id'
                            ],

                        'batch_number' =>
                            strtoupper(
                                trim(
                                    $validated[
                                        'batch_number'
                                    ]
                                )
                            ),

                        'expiry_date' =>
                            $validated[
                                'expiry_date'
                            ]
                            ?? null,

                        'purchase_price' =>
                            $validated[
                                'purchase_price'
                            ],

                        'selling_price' =>
                            $validated[
                                'selling_price'
                            ],

                        'quantity_received' =>
                            $validated[
                                'quantity_received'
                            ],

                        'quantity_available' =>
                            $validated[
                                'quantity_received'
                            ],

                        'reorder_level' =>
                            $validated[
                                'reorder_level'
                            ],

                        'received_date' =>
                            $validated[
                                'received_date'
                            ],

                        'is_active' =>
                            true,
                    ]);

                                              /*
                |--------------------------------------------------------------------------
                | Central Store Opening Balance
                |--------------------------------------------------------------------------
                |
                | Opening stock entered directly through Stock Batch must also
                | belong to a physical stock location. New opening stock is
                | initially assigned to Central Store.
                |
                */

                PharmacyStockLocationBalance::create([

                    'pharmacy_stock_location_id' =>
                        $centralStore->id,

                    'pharmacy_stock_batch_id' =>
                        $stockBatch->id,

                    'quantity_available' =>
                        $validated[
                            'quantity_received'
                        ],

                    'reorder_level' =>
                        $validated[
                            'reorder_level'
                        ],
                ]);

                PharmacyStockMovement::create([

                    'pharmacy_stock_batch_id' =>
                        $stockBatch->id,

                    'movement_type' =>
                        'stock_in',

                    'quantity' =>
                        $validated[
                            'quantity_received'
                        ],

                    'balance_after' =>
                        $validated[
                            'quantity_received'
                        ],

                    'reference_type' =>
                        'initial_stock',

                    'reference_id' =>
                        $stockBatch->id,

                    'remarks' =>
                        'Opening stock recorded when batch was created.',

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
                'pharmacy.stock-batches.index'
            )
            ->with(
                'success',
                'Stock batch added successfully.'
            );
    }



    /**
     * Show stock batch edit form.
     */
    public function edit(
        PharmacyStockBatch $stockBatch
    ): View {

        $stockBatch->load(
            'medicine'
        );


        $medicines =
            Medicine::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'generic_name'
                )
                ->orderBy(
                    'brand_name'
                )
                ->get();


        return view(
            'pharmacy.stock-batches.edit',
            compact(
                'stockBatch',
                'medicines'
            )
        );
    }



    /**
     * Update batch metadata.
     *
     * Quantity_available is intentionally not directly editable here.
     */
    public function update(
        Request $request,
        PharmacyStockBatch $stockBatch
    ): RedirectResponse {

        $validated =
            $request->validate([

                'medicine_id' => [
                    'required',
                    'integer',
                    'exists:medicines,id',
                ],

                'batch_number' => [
                    'required',
                    'string',
                    'max:100',

                    Rule::unique(
                        'pharmacy_stock_batches',
                        'batch_number'
                    )
                        ->where(
                            fn ($query) =>
                                $query->where(
                                    'medicine_id',
                                    $request->integer(
                                        'medicine_id'
                                    )
                                )
                        )
                        ->ignore(
                            $stockBatch->id
                        ),
                ],

                'expiry_date' => [
                    'nullable',
                    'date',
                ],

                'purchase_price' => [
                    'required',
                    'numeric',
                    'min:0',
                ],

                'selling_price' => [
                    'required',
                    'numeric',
                    'min:0',
                ],

                'reorder_level' => [
                    'required',
                    'integer',
                    'min:0',
                ],

                'received_date' => [
                    'required',
                    'date',
                ],
            ]);


        $stockBatch->update([

            'medicine_id' =>
                $validated[
                    'medicine_id'
                ],

            'batch_number' =>
                strtoupper(
                    trim(
                        $validated[
                            'batch_number'
                        ]
                    )
                ),

            'expiry_date' =>
                $validated[
                    'expiry_date'
                ]
                ?? null,

            'purchase_price' =>
                $validated[
                    'purchase_price'
                ],

            'selling_price' =>
                $validated[
                    'selling_price'
                ],

            'reorder_level' =>
                $validated[
                    'reorder_level'
                ],

            'received_date' =>
                $validated[
                    'received_date'
                ],
        ]);


        return redirect()
            ->route(
                'pharmacy.stock-batches.index'
            )
            ->with(
                'success',
                'Stock batch updated successfully.'
            );
    }



    /**
     * Activate or deactivate stock batch.
     */
    public function toggleStatus(
        PharmacyStockBatch $stockBatch
    ): RedirectResponse {

        $stockBatch->update([
            'is_active' =>
                ! $stockBatch->is_active,
        ]);


        return redirect()
            ->route(
                'pharmacy.stock-batches.index'
            )
            ->with(
                'success',
                $stockBatch->is_active
                    ? 'Stock batch activated successfully.'
                    : 'Stock batch deactivated successfully.'
            );
    }
}