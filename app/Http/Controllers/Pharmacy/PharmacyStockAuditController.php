<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\PharmacyStockAudit;
use App\Models\PharmacyStockAuditItem;
use App\Models\PharmacyStockBatch;
use App\Models\PharmacyStockLocation;
use App\Models\PharmacyStockLocationBalance;
use App\Models\PharmacyStockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PharmacyStockAuditController extends Controller
{
    /**
     * Stock Audit Register.
     */
    public function index(
        Request $request
    ): View {

        $query =
            PharmacyStockAudit::query()
                ->with([
                    'location',
                    'createdBy',
                    'approvedBy',
                    'postedBy',
                ])
                ->withCount('items')
                ->orderByDesc('audit_date')
                ->orderByDesc('id');


        if ($request->filled('search')) {

            $search =
                trim(
                    $request->search
                );


            $query->where(
                function ($q) use ($search) {

                    $q->where(
                        'audit_no',
                        'ilike',
                        '%' . $search . '%'
                    )
                        ->orWhereHas(
                            'location',
                            function ($locationQuery) use ($search) {

                                $locationQuery
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


        if ($request->filled('pharmacy_stock_location_id')) {

            $query->where(
                'pharmacy_stock_location_id',
                $request->pharmacy_stock_location_id
            );
        }


        if ($request->filled('from_date')) {

            $query->whereDate(
                'audit_date',
                '>=',
                $request->from_date
            );
        }


        if ($request->filled('to_date')) {

            $query->whereDate(
                'audit_date',
                '<=',
                $request->to_date
            );
        }


        $audits =
            $query
                ->paginate(20)
                ->withQueryString();


        $locations =
            PharmacyStockLocation::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->get();


        return view(
            'pharmacy.stock-audits.index',
            compact(
                'audits',
                'locations'
            )
        );
    }



    /**
     * Start Stock Audit form.
     */
    public function create(): View
    {
        $activeBatchCount =
            PharmacyStockBatch::query()
                ->where(
                    'is_active',
                    true
                )
                ->count();


        $locations =
            PharmacyStockLocation::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Existing Open Audits
        |--------------------------------------------------------------------------
        */

        $openAudits =
            PharmacyStockAudit::query()
                ->with('location')
                ->whereIn(
                    'status',
                    [
                        'draft',
                        'counting',
                        'review',
                        'approved',
                    ]
                )
                ->orderByDesc('id')
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Keep old variable for current Blade compatibility
        |--------------------------------------------------------------------------
        */

        $openAudit =
            $openAudits->first();


        /*
        |--------------------------------------------------------------------------
        | Number of batches represented at each location
        |--------------------------------------------------------------------------
        */

        $locationBatchCounts =
            PharmacyStockLocationBalance::query()
                ->select(
                    'pharmacy_stock_location_id',
                    DB::raw('COUNT(*) as batch_count')
                )
                ->whereHas(
                    'stockBatch',
                    function ($query) {

                        $query->where(
                            'is_active',
                            true
                        );
                    }
                )
                ->groupBy(
                    'pharmacy_stock_location_id'
                )
                ->pluck(
                    'batch_count',
                    'pharmacy_stock_location_id'
                );


        return view(
            'pharmacy.stock-audits.create',
            compact(
                'activeBatchCount',
                'locations',
                'openAudits',
                'openAudit',
                'locationBatchCounts'
            )
        );
    }



    /**
     * Create location-specific audit and snapshot stock.
     */
    public function store(
        Request $request
    ): RedirectResponse {

        $validated =
            $request->validate([

                'pharmacy_stock_location_id' => [
                    'required',
                    'integer',
                    'exists:pharmacy_stock_locations,id',
                ],

                'audit_date' => [
                    'required',
                    'date',
                ],

                'audit_type' => [
                    'required',
                    'string',
                    'in:full',
                ],

                'remarks' => [
                    'nullable',
                    'string',
                    'max:3000',
                ],
            ]);


        $audit =
            DB::transaction(
                function () use (
                    $validated,
                    $request
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Location
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
                    | Prevent concurrent audit creation at same location
                    |--------------------------------------------------------------------------
                    */

                    DB::statement(
                        "SELECT pg_advisory_xact_lock(hashtext(?))",
                        [
                            'pharmacy_stock_audit_location_'
                            . $location->id,
                        ]
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Only one open audit per physical location
                    |--------------------------------------------------------------------------
                    */

                    $existingOpenAudit =
                        PharmacyStockAudit::query()
                            ->where(
                                'pharmacy_stock_location_id',
                                $location->id
                            )
                            ->whereIn(
                                'status',
                                [
                                    'draft',
                                    'counting',
                                    'review',
                                    'approved',
                                ]
                            )
                            ->lockForUpdate()
                            ->first();


                    if ($existingOpenAudit) {

                        throw ValidationException::withMessages([
                            'pharmacy_stock_location_id' =>
                                'An open stock audit already exists for '
                                . $location->name
                                . ': '
                                . $existingOpenAudit->audit_no
                                . '. Complete or cancel it before starting another audit for this location.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Audit Number
                    |--------------------------------------------------------------------------
                    */

                    DB::statement(
                        "SELECT pg_advisory_xact_lock(hashtext('pharmacy_stock_audit_number'))"
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | All Active Batches
                    |--------------------------------------------------------------------------
                    |
                    | We deliberately include batches with zero stock at this
                    | location. This allows unexpected physical stock to be
                    | recorded as an excess during the audit.
                    |
                    */

                    $batches =
                        PharmacyStockBatch::query()
                            ->with('medicine')
                            ->where(
                                'is_active',
                                true
                            )
                            ->orderBy('medicine_id')
                            ->orderBy('expiry_date')
                            ->orderBy('batch_number')
                            ->get();


                    if ($batches->isEmpty()) {

                        throw ValidationException::withMessages([
                            'audit_date' =>
                                'There are no active pharmacy stock batches to audit.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Current Location Balances
                    |--------------------------------------------------------------------------
                    */

                    $locationBalances =
                        PharmacyStockLocationBalance::query()
                            ->where(
                                'pharmacy_stock_location_id',
                                $location->id
                            )
                            ->whereIn(
                                'pharmacy_stock_batch_id',
                                $batches->pluck('id')
                            )
                            ->get()
                            ->keyBy(
                                'pharmacy_stock_batch_id'
                            );


                    /*
                    |--------------------------------------------------------------------------
                    | Audit Header
                    |--------------------------------------------------------------------------
                    */

                    $audit =
                        PharmacyStockAudit::create([

                            'pharmacy_stock_location_id' =>
                                $location->id,

                            'audit_no' =>
                                $this->generateAuditNumber(),

                            'audit_date' =>
                                $validated[
                                    'audit_date'
                                ],

                            'status' =>
                                'counting',

                            'audit_type' =>
                                $validated[
                                    'audit_type'
                                ],

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


                    /*
                    |--------------------------------------------------------------------------
                    | Snapshot Location Stock
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $batches as $batch
                    ) {

                        $medicine =
                            $batch->medicine;


                        $medicineName =
                            $medicine?->generic_name
                            ?? $medicine?->generic
                            ?? 'Unknown Medicine';


                        $brandName =
                            $medicine?->brand_name
                            ?? $medicine?->brand
                            ?? null;


                        $locationBalance =
                            $locationBalances->get(
                                $batch->id
                            );


                        $systemQuantity =
                            $locationBalance
                                ? (int)
                                    $locationBalance
                                        ->quantity_available
                                : 0;


                        PharmacyStockAuditItem::create([

                            'pharmacy_stock_audit_id' =>
                                $audit->id,

                            'pharmacy_stock_batch_id' =>
                                $batch->id,

                            'medicine_id' =>
                                $batch->medicine_id,

                            'medicine_code' =>
                                $medicine?->code,

                            'medicine_name' =>
                                $medicineName,

                            'brand_name' =>
                                $brandName,

                            'strength' =>
                                $medicine?->strength,

                            'unit' =>
                                $medicine?->unit,

                            'batch_number' =>
                                $batch->batch_number,

                            'expiry_date' =>
                                $batch->expiry_date,

                            'system_quantity' =>
                                $systemQuantity,

                            'counted_quantity' =>
                                null,

                            'variance_quantity' =>
                                null,

                            'purchase_price' =>
                                (float)
                                $batch->purchase_price,

                            'variance_value' =>
                                null,

                            'variance_reason' =>
                                null,

                            'remarks' =>
                                null,

                            'is_posted' =>
                                false,

                            'pharmacy_stock_movement_id' =>
                                null,
                        ]);
                    }


                    return $audit;
                }
            );


        return redirect()
            ->route(
                'pharmacy.stock-audits.show',
                $audit
            )
            ->with(
                'success',
                'Stock audit started successfully. Enter the physical counts for the selected location.'
            );
    }



    /**
     * Show audit / counting / review screen.
     */
    public function show(
        PharmacyStockAudit $pharmacyStockAudit
    ): View {

        $pharmacyStockAudit->load([
            'location',
            'items.stockBatch',
            'createdBy',
            'approvedBy',
            'postedBy',
        ]);


        $totalSystemQty =
            $pharmacyStockAudit
                ->items
                ->sum(
                    'system_quantity'
                );


        $totalCountedQty =
            $pharmacyStockAudit
                ->items
                ->whereNotNull(
                    'counted_quantity'
                )
                ->sum(
                    'counted_quantity'
                );


        $countedItems =
            $pharmacyStockAudit
                ->items
                ->whereNotNull(
                    'counted_quantity'
                )
                ->count();


        $varianceItems =
            $pharmacyStockAudit
                ->items
                ->filter(
                    fn ($item) =>
                        $item->variance_quantity !== null
                        &&
                        (int)
                        $item->variance_quantity !== 0
                )
                ->count();


        $netVarianceQty =
            $pharmacyStockAudit
                ->items
                ->whereNotNull(
                    'variance_quantity'
                )
                ->sum(
                    'variance_quantity'
                );


        $netVarianceValue =
            $pharmacyStockAudit
                ->items
                ->whereNotNull(
                    'variance_value'
                )
                ->sum(
                    fn ($item) =>
                        (float)
                        $item->variance_value
                );


        return view(
            'pharmacy.stock-audits.show',
            compact(
                'pharmacyStockAudit',
                'totalSystemQty',
                'totalCountedQty',
                'countedItems',
                'varianceItems',
                'netVarianceQty',
                'netVarianceValue'
            )
        );
    }



    /**
     * Printable Stock Audit Report.
     */
    public function print(
        PharmacyStockAudit $pharmacyStockAudit
    ): View {

        $pharmacyStockAudit->load([
            'location',
            'items.stockBatch',
            'createdBy',
            'approvedBy',
            'postedBy',
        ]);


        $totalSystemQty =
            $pharmacyStockAudit
                ->items
                ->sum(
                    'system_quantity'
                );


        $totalCountedQty =
            $pharmacyStockAudit
                ->items
                ->whereNotNull(
                    'counted_quantity'
                )
                ->sum(
                    'counted_quantity'
                );


        $varianceItems =
            $pharmacyStockAudit
                ->items
                ->filter(
                    fn ($item) =>
                        $item->variance_quantity !== null
                        &&
                        (int)
                        $item->variance_quantity !== 0
                )
                ->count();


        $netVarianceQty =
            $pharmacyStockAudit
                ->items
                ->whereNotNull(
                    'variance_quantity'
                )
                ->sum(
                    'variance_quantity'
                );


        $netVarianceValue =
            $pharmacyStockAudit
                ->items
                ->whereNotNull(
                    'variance_value'
                )
                ->sum(
                    fn ($item) =>
                        (float)
                        $item->variance_value
                );


        return view(
            'pharmacy.stock-audits.print',
            compact(
                'pharmacyStockAudit',
                'totalSystemQty',
                'totalCountedQty',
                'varianceItems',
                'netVarianceQty',
                'netVarianceValue'
            )
        );
    }



    /**
     * Save physical counts.
     *
     * This DOES NOT alter live stock.
     */
    public function updateCounts(
        Request $request,
        PharmacyStockAudit $pharmacyStockAudit
    ): RedirectResponse {

        $validated =
            $request->validate([

                'items' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'items.*.audit_item_id' => [
                    'required',
                    'integer',
                    'exists:pharmacy_stock_audit_items,id',
                ],

                'items.*.counted_quantity' => [
                    'nullable',
                    'integer',
                    'min:0',
                ],

                'items.*.variance_reason' => [
                    'nullable',
                    'string',
                    'in:shortage,excess,counting_error,breakage,expired,missing,documentation_error,other',
                ],

                'items.*.remarks' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ]);


        DB::transaction(
            function () use (
                $validated,
                $pharmacyStockAudit
            ) {

                $audit =
                    PharmacyStockAudit::query()
                        ->whereKey(
                            $pharmacyStockAudit->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                if (
                    ! in_array(
                        $audit->status,
                        [
                            'draft',
                            'counting',
                        ],
                        true
                    )
                ) {

                    throw ValidationException::withMessages([
                        'items' =>
                            'Physical counts can only be edited while the audit is in counting status.',
                    ]);
                }


                foreach (
                    $validated['items']
                    as $row
                ) {

                    $item =
                        PharmacyStockAuditItem::query()
                            ->whereKey(
                                $row[
                                    'audit_item_id'
                                ]
                            )
                            ->where(
                                'pharmacy_stock_audit_id',
                                $audit->id
                            )
                            ->lockForUpdate()
                            ->firstOrFail();


                    $countedQuantity =
                        $row[
                            'counted_quantity'
                        ]
                        === null
                        ||
                        $row[
                            'counted_quantity'
                        ]
                        === ''
                            ? null
                            : (int)
                                $row[
                                    'counted_quantity'
                                ];


                    if (
                        $countedQuantity === null
                    ) {

                        $item->update([

                            'counted_quantity' =>
                                null,

                            'variance_quantity' =>
                                null,

                            'variance_value' =>
                                null,

                            'variance_reason' =>
                                null,

                            'remarks' =>
                                $row[
                                    'remarks'
                                ]
                                ?? null,
                        ]);

                        continue;
                    }


                    $varianceQuantity =
                        $countedQuantity
                        -
                        (int)
                        $item
                            ->system_quantity;


                    $varianceValue =
                        round(
                            $varianceQuantity
                            *
                            (float)
                            $item
                                ->purchase_price,
                            2
                        );


                    $item->update([

                        'counted_quantity' =>
                            $countedQuantity,

                        'variance_quantity' =>
                            $varianceQuantity,

                        'variance_value' =>
                            $varianceValue,

                        'variance_reason' =>
                            $row[
                                'variance_reason'
                            ]
                            ?? null,

                        'remarks' =>
                            $row[
                                'remarks'
                            ]
                            ?? null,
                    ]);
                }


                $audit->update([
                    'status' =>
                        'counting',
                ]);
            }
        );


        return redirect()
            ->route(
                'pharmacy.stock-audits.show',
                $pharmacyStockAudit
            )
            ->with(
                'success',
                'Physical stock counts saved successfully.'
            );
    }



    /**
     * Submit completed count for review.
     */
    public function submitForReview(
        PharmacyStockAudit $pharmacyStockAudit
    ): RedirectResponse {

        DB::transaction(
            function () use (
                $pharmacyStockAudit
            ) {

                $audit =
                    PharmacyStockAudit::query()
                        ->whereKey(
                            $pharmacyStockAudit->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                if (
                    ! in_array(
                        $audit->status,
                        [
                            'draft',
                            'counting',
                        ],
                        true
                    )
                ) {

                    throw ValidationException::withMessages([
                        'audit' =>
                            'Only an audit in counting status can be submitted for review.',
                    ]);
                }


                $items =
                    PharmacyStockAuditItem::query()
                        ->where(
                            'pharmacy_stock_audit_id',
                            $audit->id
                        )
                        ->get();


                if ($items->isEmpty()) {

                    throw ValidationException::withMessages([
                        'audit' =>
                            'This audit contains no stock items.',
                    ]);
                }


                $uncounted =
                    $items->filter(
                        fn ($item) =>
                            $item->counted_quantity === null
                    );


                if ($uncounted->isNotEmpty()) {

                    throw ValidationException::withMessages([
                        'audit' =>
                            $uncounted->count()
                            . ' stock batch(es) have not yet been counted.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Require reason for every variance
                |--------------------------------------------------------------------------
                */

                $missingReasons =
                    $items->filter(
                        fn ($item) =>
                            (int)
                            $item->variance_quantity !== 0
                            &&
                            empty(
                                $item->variance_reason
                            )
                    );


                if ($missingReasons->isNotEmpty()) {

                    throw ValidationException::withMessages([
                        'audit' =>
                            'A variance reason is required for every batch with a stock difference.',
                    ]);
                }


                $audit->update([
                    'status' =>
                        'review',
                ]);
            }
        );


        return redirect()
            ->route(
                'pharmacy.stock-audits.show',
                $pharmacyStockAudit
            )
            ->with(
                'success',
                'Stock audit submitted for review.'
            );
    }



    /**
     * Approve reviewed audit.
     *
     * Creator cannot approve own audit.
     */
    public function approve(
        Request $request,
        PharmacyStockAudit $pharmacyStockAudit
    ): RedirectResponse {

        DB::transaction(
            function () use (
                $request,
                $pharmacyStockAudit
            ) {

                $audit =
                    PharmacyStockAudit::query()
                        ->whereKey(
                            $pharmacyStockAudit->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                if (
                    $audit->status
                    !== 'review'
                ) {

                    throw ValidationException::withMessages([
                        'audit' =>
                            'Only an audit under review can be approved.',
                    ]);
                }


                if (
                    (int)
                    $audit->created_by
                    ===
                    (int)
                    $request
                        ->user()
                        ->id
                ) {

                    throw ValidationException::withMessages([
                        'audit' =>
                            'You cannot approve a stock audit that you created. '
                            . 'Approval must be completed by another authorised user.',
                    ]);
                }


                $audit->update([

                    'status' =>
                        'approved',

                    'approved_by' =>
                        $request
                            ->user()
                            ->id,

                    'approved_at' =>
                        now(),
                ]);
            }
        );


        return redirect()
            ->route(
                'pharmacy.stock-audits.show',
                $pharmacyStockAudit
            )
            ->with(
                'success',
                'Stock audit approved. No stock has been changed yet.'
            );
    }



    /**
     * Post approved location audit variance.
     *
     * Poster must differ from creator and approver.
     */
    public function post(
        Request $request,
        PharmacyStockAudit $pharmacyStockAudit
    ): RedirectResponse {

        DB::transaction(
            function () use (
                $request,
                $pharmacyStockAudit
            ) {

                /*
                |--------------------------------------------------------------------------
                | Lock Audit
                |--------------------------------------------------------------------------
                */

                $audit =
                    PharmacyStockAudit::query()
                        ->with('location')
                        ->whereKey(
                            $pharmacyStockAudit->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                if (
                    $audit->status
                    !== 'approved'
                ) {

                    throw ValidationException::withMessages([
                        'audit' =>
                            'Only an approved stock audit can be posted.',
                    ]);
                }


                if ($audit->posted_at) {

                    throw ValidationException::withMessages([
                        'audit' =>
                            'This stock audit has already been posted.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Legacy Audit Safety
                |--------------------------------------------------------------------------
                */

                if (
                    $audit
                        ->pharmacy_stock_location_id
                    === null
                ) {

                    throw ValidationException::withMessages([
                        'audit' =>
                            'This is a legacy hospital-wide stock audit and cannot be posted after multi-location inventory was enabled. Cancel it and create a new location-specific audit.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Segregation of Duties
                |--------------------------------------------------------------------------
                */

                if (
                    (int)
                    $audit->created_by
                    ===
                    (int)
                    $request
                        ->user()
                        ->id
                ) {

                    throw ValidationException::withMessages([
                        'audit' =>
                            'You cannot post a stock audit that you created. '
                            . 'Final posting must be completed by another authorised user.',
                    ]);
                }


                if (
                    $audit->approved_by !== null
                    &&
                    (int)
                    $audit->approved_by
                    ===
                    (int)
                    $request
                        ->user()
                        ->id
                ) {

                    throw ValidationException::withMessages([
                        'audit' =>
                            'You cannot post a stock audit that you approved. '
                            . 'Final posting must be completed by another authorised user.',
                    ]);
                }


                $location =
                    $audit->location;


                if (! $location) {

                    throw ValidationException::withMessages([
                        'audit' =>
                            'The stock location linked to this audit could not be found.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Lock Audit Items
                |--------------------------------------------------------------------------
                */

                $items =
                    PharmacyStockAuditItem::query()
                        ->where(
                            'pharmacy_stock_audit_id',
                            $audit->id
                        )
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->get();


                if ($items->isEmpty()) {

                    throw ValidationException::withMessages([
                        'audit' =>
                            'This stock audit contains no items.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Ensure all items have physical counts
                |--------------------------------------------------------------------------
                */

                if (
                    $items
                        ->whereNull(
                            'counted_quantity'
                        )
                        ->isNotEmpty()
                ) {

                    throw ValidationException::withMessages([
                        'audit' =>
                            'All audit items must have a physical count before posting.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Detect stock introduced after snapshot for batches not audited
                |--------------------------------------------------------------------------
                |
                | Normally every active batch is snapshotted. This protects against
                | a new batch receiving stock at this location after the audit began.
                |
                */

                $snapshotBatchIds =
                    $items
                        ->pluck(
                            'pharmacy_stock_batch_id'
                        );


                $newLocationStock =
                    PharmacyStockLocationBalance::query()
                        ->with('stockBatch.medicine')
                        ->where(
                            'pharmacy_stock_location_id',
                            $location->id
                        )
                        ->where(
                            'quantity_available',
                            '!=',
                            0
                        )
                        ->whereNotIn(
                            'pharmacy_stock_batch_id',
                            $snapshotBatchIds
                        )
                        ->first();


                if ($newLocationStock) {

                    throw ValidationException::withMessages([
                        'audit' =>
                            'New stock appeared at '
                            . $location->name
                            . ' after this audit was started. '
                            . 'Cancel this audit and begin a fresh audit before posting.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | FIRST PASS
                |--------------------------------------------------------------------------
                |
                | Verify that the audited LOCATION balance has not changed since
                | snapshot.
                |
                | Stock movements at OTHER locations do not invalidate this audit.
                |
                */

                foreach (
                    $items as $item
                ) {

                    if ($item->is_posted) {

                        throw ValidationException::withMessages([
                            'audit' =>
                                'One or more audit items have already been posted.',
                        ]);
                    }


                    $batch =
                        PharmacyStockBatch::query()
                            ->whereKey(
                                $item
                                    ->pharmacy_stock_batch_id
                            )
                            ->lockForUpdate()
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


                    $currentLocationBalance =
                        $locationBalance
                            ? (int)
                                $locationBalance
                                    ->quantity_available
                            : 0;


                    $snapshotBalance =
                        (int)
                        $item
                            ->system_quantity;


                    if (
                        $currentLocationBalance
                        !== $snapshotBalance
                    ) {

                        throw ValidationException::withMessages([
                            'audit' =>
                                'Stock changed at '
                                . $location->name
                                . ' after the audit snapshot for '
                                . $item->medicine_name
                                . ' batch '
                                . $item->batch_number
                                . '. Snapshot quantity: '
                                . $snapshotBalance
                                . ', current location quantity: '
                                . $currentLocationBalance
                                . '. Posting has been blocked to protect stock integrity.',
                        ]);
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | SECOND PASS
                |--------------------------------------------------------------------------
                |
                | Apply approved variance to:
                |
                | 1. audited physical location
                | 2. hospital-wide batch balance
                |
                */

                foreach (
                    $items as $item
                ) {

                    $batch =
                        PharmacyStockBatch::query()
                            ->whereKey(
                                $item
                                    ->pharmacy_stock_batch_id
                            )
                            ->lockForUpdate()
                            ->firstOrFail();


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


                    $countedQuantity =
                        (int)
                        $item
                            ->counted_quantity;


                    $variance =
                        (int)
                        $item
                            ->variance_quantity;


                    /*
                    |--------------------------------------------------------------------------
                    | No Variance
                    |--------------------------------------------------------------------------
                    */

                    if ($variance === 0) {

                        $item->update([

                            'is_posted' =>
                                true,

                            'pharmacy_stock_movement_id' =>
                                null,
                        ]);

                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Positive Variance
                    |--------------------------------------------------------------------------
                    */

                    if ($variance > 0) {

                        $movementType =
                            'correction_plus';


                        $movementQuantity =
                            abs(
                                $variance
                            );


                        $newLocationBalance =
                            $oldLocationBalance
                            +
                            $movementQuantity;


                        $newGlobalBalance =
                            $oldGlobalBalance
                            +
                            $movementQuantity;


                    /*
                    |--------------------------------------------------------------------------
                    | Negative Variance
                    |--------------------------------------------------------------------------
                    */

                    } else {

                        $movementType =
                            'correction_minus';


                        $movementQuantity =
                            abs(
                                $variance
                            );


                        if (
                            $movementQuantity
                            > $oldLocationBalance
                        ) {

                            throw ValidationException::withMessages([
                                'audit' =>
                                    'Audit shortage exceeds the current stock at '
                                    . $location->name
                                    . ' for '
                                    . $item->medicine_name
                                    . ' batch '
                                    . $item->batch_number
                                    . '.',
                            ]);
                        }


                        if (
                            $movementQuantity
                            > $oldGlobalBalance
                        ) {

                            throw ValidationException::withMessages([
                                'audit' =>
                                    'Hospital-wide stock is inconsistent for '
                                    . $item->medicine_name
                                    . ' batch '
                                    . $item->batch_number
                                    . '. Posting has been blocked.',
                            ]);
                        }


                        $newLocationBalance =
                            $oldLocationBalance
                            -
                            $movementQuantity;


                        $newGlobalBalance =
                            $oldGlobalBalance
                            -
                            $movementQuantity;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Location Must End at Physical Count
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $newLocationBalance
                        !== $countedQuantity
                    ) {

                        throw ValidationException::withMessages([
                            'audit' =>
                                'Calculated location balance does not match the physical count for '
                                . $item->medicine_name
                                . ' batch '
                                . $item->batch_number
                                . '.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Update / Create Location Balance
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


                    /*
                    |--------------------------------------------------------------------------
                    | Update Hospital-wide Balance
                    |--------------------------------------------------------------------------
                    */

                    $batch->update([

                        'quantity_available' =>
                            $newGlobalBalance,
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Permanent Stock Ledger
                    |--------------------------------------------------------------------------
                    */

                    $movement =
                        PharmacyStockMovement::create([

                            'pharmacy_stock_batch_id' =>
                                $batch->id,

                            'movement_type' =>
                                $movementType,

                            'quantity' =>
                                $movementQuantity,

                            'balance_after' =>
                                $newGlobalBalance,

                            'reference_type' =>
                                'pharmacy_stock_audit',

                            'reference_id' =>
                                $audit->id,

                            'remarks' =>
                                'Posted from stock audit '
                                . $audit->audit_no
                                . ' | Location: '
                                . $location->name
                                . ' | System quantity: '
                                . $item->system_quantity
                                . ' | Physical quantity: '
                                . $item->counted_quantity
                                . ' | Variance: '
                                . (
                                    $variance > 0
                                        ? '+'
                                        : ''
                                )
                                . $variance
                                . ' | '
                                . $location->name
                                . ': '
                                . $oldLocationBalance
                                . ' → '
                                . $newLocationBalance
                                . ' | Hospital: '
                                . $oldGlobalBalance
                                . ' → '
                                . $newGlobalBalance
                                . (
                                    $item->variance_reason
                                        ? ' | Reason: '
                                            . ucwords(
                                                str_replace(
                                                    '_',
                                                    ' ',
                                                    $item
                                                        ->variance_reason
                                                )
                                            )
                                        : ''
                                ),

                            'created_by' =>
                                $request
                                    ->user()
                                    ->id,

                            'movement_at' =>
                                now(),
                        ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Mark Item Posted
                    |--------------------------------------------------------------------------
                    */

                    $item->update([

                        'is_posted' =>
                            true,

                        'pharmacy_stock_movement_id' =>
                            $movement->id,
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Finalize Audit
                |--------------------------------------------------------------------------
                */

                $audit->update([

                    'status' =>
                        'posted',

                    'posted_by' =>
                        $request
                            ->user()
                            ->id,

                    'posted_at' =>
                        now(),
                ]);
            }
        );


        return redirect()
            ->route(
                'pharmacy.stock-audits.show',
                $pharmacyStockAudit
            )
            ->with(
                'success',
                'Stock audit posted successfully. Approved variances have been applied to the audited location and hospital-wide stock.'
            );
    }



    /**
     * Cancel an unposted audit.
     */
    public function cancel(
        PharmacyStockAudit $pharmacyStockAudit
    ): RedirectResponse {

        DB::transaction(
            function () use (
                $pharmacyStockAudit
            ) {

                $audit =
                    PharmacyStockAudit::query()
                        ->whereKey(
                            $pharmacyStockAudit->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                if (
                    $audit->status
                    === 'posted'
                ) {

                    throw ValidationException::withMessages([
                        'audit' =>
                            'A posted stock audit cannot be cancelled.',
                    ]);
                }


                if (
                    $audit->status
                    === 'cancelled'
                ) {

                    throw ValidationException::withMessages([
                        'audit' =>
                            'This stock audit is already cancelled.',
                    ]);
                }


                $postedItems =
                    PharmacyStockAuditItem::query()
                        ->where(
                            'pharmacy_stock_audit_id',
                            $audit->id
                        )
                        ->where(
                            'is_posted',
                            true
                        )
                        ->exists();


                if ($postedItems) {

                    throw ValidationException::withMessages([
                        'audit' =>
                            'This audit contains posted stock movements and cannot be cancelled.',
                    ]);
                }


                $audit->update([
                    'status' =>
                        'cancelled',
                ]);
            }
        );


        return redirect()
            ->route(
                'pharmacy.stock-audits.show',
                $pharmacyStockAudit
            )
            ->with(
                'success',
                'Stock audit cancelled.'
            );
    }



    /**
     * Generate concurrency-safe audit number.
     *
     * Example:
     * AUD-20260914-000001
     */
    private function generateAuditNumber(): string
    {
        $prefix =
            'AUD-'
            . now()->format(
                'Ymd'
            )
            . '-';


        $last =
            PharmacyStockAudit::query()
                ->where(
                    'audit_no',
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
                    $last->audit_no,
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