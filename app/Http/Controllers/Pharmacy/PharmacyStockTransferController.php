<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\PharmacyStockBatch;
use App\Models\PharmacyStockLocation;
use App\Models\PharmacyStockLocationBalance;
use App\Models\PharmacyStockTransfer;
use App\Models\PharmacyStockTransferItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PharmacyStockTransferController extends Controller
{
    /**
     * Transfer register.
     */
    public function index(Request $request): View
    {
        $query =
            PharmacyStockTransfer::query()
                ->with([
                    'fromLocation',
                    'toLocation',
                    'createdBy',
                    'issuedBy',
                    'receivedBy',
                ])
                ->withCount('items')
                ->orderByDesc('transfer_date')
                ->orderByDesc('id');


        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }


        if ($request->filled('search')) {

            $search =
                trim(
                    (string) $request->search
                );


            $query->where(
                function ($q) use ($search) {

                    $q->where(
                        'transfer_no',
                        'ilike',
                        '%' . $search . '%'
                    )
                        ->orWhereHas(
                            'fromLocation',
                            function ($locationQuery) use ($search) {

                                $locationQuery->where(
                                    'name',
                                    'ilike',
                                    '%' . $search . '%'
                                );
                            }
                        )
                        ->orWhereHas(
                            'toLocation',
                            function ($locationQuery) use ($search) {

                                $locationQuery->where(
                                    'name',
                                    'ilike',
                                    '%' . $search . '%'
                                );
                            }
                        );
                }
            );
        }


        $transfers =
            $query
                ->paginate(20)
                ->withQueryString();


        return view(
            'pharmacy.stock-transfers.index',
            compact(
                'transfers'
            )
        );
    }



    /**
     * Show transfer creation form.
     */
    public function create(): View
    {
        $locations =
            PharmacyStockLocation::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('name')
                ->get();


        $batches =
            PharmacyStockBatch::query()
                ->with('medicine')
                ->where(
                    'is_active',
                    true
                )
                ->where(
                    'quantity_available',
                    '>',
                    0
                )
                ->orderBy('expiry_date')
                ->get();


        return view(
            'pharmacy.stock-transfers.create',
            compact(
                'locations',
                'batches'
            )
        );
    }



    /**
     * Store transfer as draft.
     */
    public function store(
        Request $request
    ): RedirectResponse {

        $validated =
            $request->validate([

                'transfer_date' => [
                    'required',
                    'date',
                ],

                'from_location_id' => [
                    'required',
                    'integer',
                    'exists:pharmacy_stock_locations,id',
                    'different:to_location_id',
                ],

                'to_location_id' => [
                    'required',
                    'integer',
                    'exists:pharmacy_stock_locations,id',
                    'different:from_location_id',
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

                'items.*.pharmacy_stock_batch_id' => [
                    'required',
                    'integer',
                    'exists:pharmacy_stock_batches,id',
                ],

                'items.*.quantity_requested' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'items.*.remarks' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ]);


        $transfer =
            DB::transaction(
                function () use (
                    $validated,
                    $request
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Lock document numbering
                    |--------------------------------------------------------------------------
                    */

                    DB::statement(
                        "SELECT pg_advisory_xact_lock(hashtext('pharmacy_stock_transfer_number'))"
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Create header
                    |--------------------------------------------------------------------------
                    */

                    $transfer =
                        PharmacyStockTransfer::create([

                            'transfer_no' =>
                                $this->generateTransferNumber(),

                            'transfer_date' =>
                                $validated['transfer_date'],

                            'from_location_id' =>
                                $validated['from_location_id'],

                            'to_location_id' =>
                                $validated['to_location_id'],

                            'status' =>
                                'draft',

                            'remarks' =>
                                $validated['remarks']
                                ?? null,

                            'created_by' =>
                                $request->user()->id,
                        ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Create transfer lines
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $validated['items']
                        as $row
                    ) {

                        $batch =
                            PharmacyStockBatch::query()
                                ->with('medicine')
                                ->whereKey(
                                    $row['pharmacy_stock_batch_id']
                                )
                                ->firstOrFail();


                        PharmacyStockTransferItem::create([

                            'pharmacy_stock_transfer_id' =>
                                $transfer->id,

                            'pharmacy_stock_batch_id' =>
                                $batch->id,

                            'medicine_code' =>
                                $batch->medicine?->code,

                            'medicine_name' =>
                                $batch->medicine?->generic_name
                                ?? 'Medicine',

                            'brand_name' =>
                                $batch->medicine?->brand_name,

                            'strength' =>
                                $batch->medicine?->strength,

                            'unit' =>
                                $batch->medicine?->unit,

                            'batch_number' =>
                                $batch->batch_number,

                            'expiry_date' =>
                                $batch->expiry_date,

                            'quantity_requested' =>
                                (int)
                                $row['quantity_requested'],

                            'quantity_issued' =>
                                0,

                            'quantity_received' =>
                                0,

                            'remarks' =>
                                $row['remarks']
                                ?? null,
                        ]);
                    }


                    return $transfer;
                }
            );


        return redirect()
            ->route(
                'pharmacy.stock-transfers.show',
                $transfer
            )
            ->with(
                'success',
                'Stock transfer draft created successfully.'
            );
    }



    /**
     * Show transfer.
     */
    public function show(
        PharmacyStockTransfer $pharmacyStockTransfer
    ): View {

        $pharmacyStockTransfer->load([
            'fromLocation',
            'toLocation',
            'createdBy',
            'issuedBy',
            'receivedBy',
            'cancelledBy',
            'items.stockBatch.medicine',
        ]);


        return view(
            'pharmacy.stock-transfers.show',
            compact(
                'pharmacyStockTransfer'
            )
        );
    }



    /**
     * Issue stock from source location.
     */
    public function issue(
        Request $request,
        PharmacyStockTransfer $pharmacyStockTransfer
    ): RedirectResponse {

        DB::transaction(
            function () use (
                $request,
                $pharmacyStockTransfer
            ) {

                /*
                |--------------------------------------------------------------------------
                | Lock transfer
                |--------------------------------------------------------------------------
                */

                $transfer =
                    PharmacyStockTransfer::query()
                        ->with('items')
                        ->whereKey(
                            $pharmacyStockTransfer->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                if (
                    $transfer->status
                    !== 'draft'
                ) {

                    throw ValidationException::withMessages([
                        'transfer' =>
                            'Only a draft stock transfer can be issued.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Process each transfer line
                |--------------------------------------------------------------------------
                */

                foreach (
                    $transfer->items
                    as $item
                ) {

                    $quantity =
                        (int)
                        $item->quantity_requested;


                    if ($quantity <= 0) {

                        throw ValidationException::withMessages([
                            'transfer' =>
                                'Transfer contains an invalid quantity.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Lock source balance
                    |--------------------------------------------------------------------------
                    */

                    $sourceBalance =
                        PharmacyStockLocationBalance::query()
                            ->where(
                                'pharmacy_stock_location_id',
                                $transfer->from_location_id
                            )
                            ->where(
                                'pharmacy_stock_batch_id',
                                $item->pharmacy_stock_batch_id
                            )
                            ->lockForUpdate()
                            ->first();


                    if (! $sourceBalance) {

                        throw ValidationException::withMessages([
                            'transfer' =>
                                'No stock is available at the source location for '
                                . $item->medicine_name
                                . ', batch '
                                . $item->batch_number
                                . '.',
                        ]);
                    }


                    $available =
                        (int)
                        $sourceBalance->quantity_available;


                    if (
                        $quantity
                        > $available
                    ) {

                        throw ValidationException::withMessages([
                            'transfer' =>
                                'Only '
                                . $available
                                . ' units of '
                                . $item->medicine_name
                                . ' are available at the source location for batch '
                                . $item->batch_number
                                . '.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Reduce source-location balance
                    |--------------------------------------------------------------------------
                    */

                    $sourceBalance->update([

                        'quantity_available' =>
                            $available
                            - $quantity,
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Record issued quantity
                    |--------------------------------------------------------------------------
                    */

                    $item->update([

                        'quantity_issued' =>
                            $quantity,
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Mark transfer issued
                |--------------------------------------------------------------------------
                */

                $transfer->update([

                    'status' =>
                        'issued',

                    'issued_by' =>
                        $request->user()->id,

                    'issued_at' =>
                        now(),
                ]);
            }
        );


        return redirect()
            ->route(
                'pharmacy.stock-transfers.show',
                $pharmacyStockTransfer
            )
            ->with(
                'success',
                'Stock issued successfully. Transfer is now awaiting receipt.'
            );
    }



    /**
     * Receive stock at destination location.
     */
    public function receive(
        Request $request,
        PharmacyStockTransfer $pharmacyStockTransfer
    ): RedirectResponse {

        DB::transaction(
            function () use (
                $request,
                $pharmacyStockTransfer
            ) {

                /*
                |--------------------------------------------------------------------------
                | Lock transfer
                |--------------------------------------------------------------------------
                */

                $transfer =
                    PharmacyStockTransfer::query()
                        ->with('items')
                        ->whereKey(
                            $pharmacyStockTransfer->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                if (
                    $transfer->status
                    !== 'issued'
                ) {

                    throw ValidationException::withMessages([
                        'transfer' =>
                            'Only an issued stock transfer can be received.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Receive each issued line
                |--------------------------------------------------------------------------
                */

                foreach (
                    $transfer->items
                    as $item
                ) {

                    $quantity =
                        (int)
                        $item->quantity_issued;


                    if ($quantity <= 0) {

                        throw ValidationException::withMessages([
                            'transfer' =>
                                'Transfer contains an invalid issued quantity.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Lock destination balance if it exists
                    |--------------------------------------------------------------------------
                    */

                    $destinationBalance =
                        PharmacyStockLocationBalance::query()
                            ->where(
                                'pharmacy_stock_location_id',
                                $transfer->to_location_id
                            )
                            ->where(
                                'pharmacy_stock_batch_id',
                                $item->pharmacy_stock_batch_id
                            )
                            ->lockForUpdate()
                            ->first();


                    if ($destinationBalance) {

                        $destinationBalance->update([

                            'quantity_available' =>
                                (int)
                                $destinationBalance->quantity_available
                                + $quantity,
                        ]);

                    } else {

                        PharmacyStockLocationBalance::create([

                            'pharmacy_stock_location_id' =>
                                $transfer->to_location_id,

                            'pharmacy_stock_batch_id' =>
                                $item->pharmacy_stock_batch_id,

                            'quantity_available' =>
                                $quantity,

                            'reorder_level' =>
                                0,
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Record received quantity
                    |--------------------------------------------------------------------------
                    */

                    $item->update([

                        'quantity_received' =>
                            $quantity,
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | Mark transfer received
                |--------------------------------------------------------------------------
                */

                $transfer->update([

                    'status' =>
                        'received',

                    'received_by' =>
                        $request->user()->id,

                    'received_at' =>
                        now(),
                ]);
            }
        );


        return redirect()
            ->route(
                'pharmacy.stock-transfers.show',
                $pharmacyStockTransfer
            )
            ->with(
                'success',
                'Stock received successfully.'
            );
    }



    /**
     * Cancel a draft transfer.
     */
    public function cancel(
        Request $request,
        PharmacyStockTransfer $pharmacyStockTransfer
    ): RedirectResponse {

        $validated =
            $request->validate([

                'cancellation_reason' => [
                    'required',
                    'string',
                    'max:3000',
                ],
            ]);


        DB::transaction(
            function () use (
                $validated,
                $request,
                $pharmacyStockTransfer
            ) {

                $transfer =
                    PharmacyStockTransfer::query()
                        ->whereKey(
                            $pharmacyStockTransfer->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                if (
                    $transfer->status
                    !== 'draft'
                ) {

                    throw ValidationException::withMessages([
                        'transfer' =>
                            'Only a draft stock transfer can be cancelled.',
                    ]);
                }


                $transfer->update([

                    'status' =>
                        'cancelled',

                    'cancelled_by' =>
                        $request->user()->id,

                    'cancelled_at' =>
                        now(),

                    'cancellation_reason' =>
                        $validated['cancellation_reason'],
                ]);
            }
        );


        return redirect()
            ->route(
                'pharmacy.stock-transfers.show',
                $pharmacyStockTransfer
            )
            ->with(
                'success',
                'Stock transfer cancelled successfully.'
            );
    }



    /**
     * Generate transfer number.
     *
     * Example:
     * ST-20260914-000001
     */
    private function generateTransferNumber(): string
    {
        $prefix =
            'ST-'
            . now()->format('Ymd')
            . '-';


        $last =
            PharmacyStockTransfer::query()
                ->where(
                    'transfer_no',
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
                    $last->transfer_no,
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