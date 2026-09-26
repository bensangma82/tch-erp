<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Encounter;
use App\Models\IpBillingAccount;
use App\Models\IpBillingCharge;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\PharmacySale;
use App\Models\PharmacySaleItem;
use App\Models\PharmacyStockBatch;
use App\Models\PharmacyStockLocation;
use App\Models\PharmacyStockLocationBalance;
use App\Models\PharmacyStockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DispensingController extends Controller
{
    /**
     * Show recent pharmacy sales.
     */
    public function index(
        Request $request
    ): View {

        $search =
            trim(
                (string)
                $request->get('search')
            );


        $sales =
            PharmacySale::query()
                ->with([
                    'patient',
                    'encounter.department',
                    'createdBy',
                ])
                ->when(
                    $search !== '',
                    function ($query) use ($search) {

                        $query->where(
                            function ($q) use ($search) {

                                $q->where(
                                    'sale_no',
                                    'ilike',
                                    '%' . $search . '%'
                                )
                                    ->orWhereHas(
                                        'patient',
                                        function ($patientQuery) use ($search) {

                                            $patientQuery
                                                ->where(
                                                    'uhid',
                                                    'ilike',
                                                    '%' . $search . '%'
                                                )
                                                ->orWhere(
                                                    'mrd_number',
                                                    'ilike',
                                                    '%' . $search . '%'
                                                )
                                                ->orWhere(
                                                    'first_name',
                                                    'ilike',
                                                    '%' . $search . '%'
                                                )
                                                ->orWhere(
                                                    'middle_name',
                                                    'ilike',
                                                    '%' . $search . '%'
                                                )
                                                ->orWhere(
                                                    'last_name',
                                                    'ilike',
                                                    '%' . $search . '%'
                                                );
                                        }
                                    );
                            }
                        );
                    }
                )
                ->orderByDesc(
                    'sale_at'
                )
                ->paginate(25)
                ->withQueryString();


        return view(
            'pharmacy.dispensing.index',
            compact(
                'sales',
                'search'
            )
        );
    }



    /**
     * Show dispensing form.
     */
    public function create(
        Request $request
    ): View {

        $patientId =
            $request->integer(
                'patient_id'
            );


        $encounterId =
            $request->integer(
                'encounter_id'
            );


        $admissionId =
            $request->integer(
                'admission_id'
            );


        $patient =
            null;


        $encounter =
            null;


        $admission =
            null;


        if ($admissionId) {

            $admission =
                Admission::query()
                    ->with([
                        'patient',
                        'department',
                        'consultant',
                        'bed.ward',
                        'currentBedAllocation.bed.ward',
                    ])
                    ->findOrFail(
                        $admissionId
                    );


            $patient =
                $admission->patient;

        } elseif ($encounterId) {

            $encounter =
                Encounter::query()
                    ->with([
                        'patient',
                        'department',
                        'doctor',
                    ])
                    ->findOrFail(
                        $encounterId
                    );


            $patient =
                $encounter->patient;

        } elseif ($patientId) {

            $patient =
                Patient::findOrFail(
                    $patientId
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Pharmacy Stock Location
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

            abort(
                500,
                'Pharmacy stock location is not configured.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Medicines Available at Pharmacy Location
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | Only stock physically available in PHARMACY may be sold.
        |
        */

        $medicines =
            Medicine::query()
                ->where(
                    'is_active',
                    true
                )
                ->whereHas(
                    'stockBatches.locationBalances',
                    function ($query) use ($pharmacyLocation) {

                        $query
                            ->where(
                                'pharmacy_stock_location_id',
                                $pharmacyLocation->id
                            )
                            ->where(
                                'quantity_available',
                                '>',
                                0
                            );
                    }
                )
                ->whereHas(
                    'stockBatches',
                    function ($query) {

                        $query
                            ->where(
                                'is_active',
                                true
                            )
                            ->where(
                                function ($expiryQuery) {

                                    $expiryQuery
                                        ->whereNull(
                                            'expiry_date'
                                        )
                                        ->orWhereDate(
                                            'expiry_date',
                                            '>=',
                                            today()
                                        );
                                }
                            );
                    }
                )
                ->with([
                    'stockBatches' =>
                        function ($query) use ($pharmacyLocation) {

                            $query
                                ->where(
                                    'is_active',
                                    true
                                )
                                ->where(
                                    function ($expiryQuery) {

                                        $expiryQuery
                                            ->whereNull(
                                                'expiry_date'
                                            )
                                            ->orWhereDate(
                                                'expiry_date',
                                                '>=',
                                                today()
                                            );
                                    }
                                )
                                ->whereHas(
                                    'locationBalances',
                                    function ($balanceQuery) use ($pharmacyLocation) {

                                        $balanceQuery
                                            ->where(
                                                'pharmacy_stock_location_id',
                                                $pharmacyLocation->id
                                            )
                                            ->where(
                                                'quantity_available',
                                                '>',
                                                0
                                            );
                                    }
                                )
                                ->with([
                                    'locationBalances' =>
                                        function ($balanceQuery) use ($pharmacyLocation) {

                                            $balanceQuery->where(
                                                'pharmacy_stock_location_id',
                                                $pharmacyLocation->id
                                            );
                                        },
                                ]);
                        },
                ])
                ->orderBy(
                    'generic_name'
                )
                ->orderBy(
                    'brand_name'
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Add Pharmacy Available Stock
        |--------------------------------------------------------------------------
        */

        $medicines->each(
            function ($medicine) {

                $availableStock =
                    $medicine
                        ->stockBatches
                        ->sum(
                            function ($batch) {

                                return
                                    (int)
                                    optional(
                                        $batch
                                            ->locationBalances
                                            ->first()
                                    )
                                        ->quantity_available;
                            }
                        );


                $medicine->setAttribute(
                    'available_stock',
                    $availableStock
                );
            }
        );


        /*
|--------------------------------------------------------------------------
| Pharmacy Patient / Encounter Search Pool
|--------------------------------------------------------------------------
|
| Keep a broader encounter history available for chronic and repeat
| medication patients. The Blade page can then filter this list by
| patient name, UHID, MRD, encounter number, department or doctor.
|
*/

$recentEncounters =
    Encounter::query()
        ->with([
            'patient',
            'department',
            'doctor',
        ])
        ->whereHas(
            'patient',
            function ($query) {
                $query->where(
                    'is_active',
                    true
                );
            }
        )
        ->whereDate(
            'encounter_date',
            '>=',
            today()->subDays(180)
        )
        ->orderByDesc(
            'encounter_date'
        )
        ->orderByDesc(
            'encounter_time'
        )
        ->limit(1000)
        ->get();


        return view(
            'pharmacy.dispensing.create',
            compact(
                'patient',
                'encounter',
                'admission',
                'medicines',
                'recentEncounters'
            )
        );
    }



    /**
     * Process pharmacy sale and dispense stock.
     */
    public function store(
        Request $request
    ): RedirectResponse {

        $validated =
            $request->validate([

                'patient_id' => [
                    'required',
                    'integer',
                    'exists:patients,id',
                ],

                'encounter_id' => [
                    'nullable',
                    'integer',
                    'exists:encounters,id',
                ],

                'admission_id' => [
                    'nullable',
                    'integer',
                    'exists:admissions,id',
                ],

                'medicines' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'medicines.*.medicine_id' => [
                    'required',
                    'integer',
                    'exists:medicines,id',
                ],

                'medicines.*.quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],

                'discount' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'payment_mode' => [
                    'nullable',
                    'required_without:admission_id',
                    'string',
                    'in:cash,upi,card,credit,mhis',
                ],

                'cash_received' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'transaction_reference' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'remarks' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ]);


        $patient =
            Patient::findOrFail(
                $validated['patient_id']
            );


        $encounter =
            null;


        $admission =
            null;


        if (
            ! empty(
                $validated['admission_id']
            )
        ) {

            $admission =
                Admission::query()
                    ->where(
                        'patient_id',
                        $patient->id
                    )
                    ->findOrFail(
                        $validated['admission_id']
                    );
        }


        if (
            ! empty(
                $validated['encounter_id']
            )
        ) {

            if ($admission) {
                throw ValidationException::withMessages([
                    'encounter_id' =>
                        'A pharmacy sale cannot be linked to both an OPD encounter and an IP admission.',
                ]);
            }


            $encounter =
                Encounter::query()
                    ->where(
                        'patient_id',
                        $patient->id
                    )
                    ->findOrFail(
                        $validated['encounter_id']
                    );
        }


        /*
        |--------------------------------------------------------------------------
        | Combine Duplicate Medicine Rows
        |--------------------------------------------------------------------------
        */

        $requestedMedicines =
            collect(
                $validated['medicines']
            )
                ->groupBy(
                    'medicine_id'
                )
                ->map(
                    function (
                        $rows,
                        $medicineId
                    ) {

                        return [

                            'medicine_id' =>
                                (int)
                                $medicineId,

                            'quantity' =>
                                (int)
                                $rows->sum(
                                    fn ($row) =>
                                        (int)
                                        $row['quantity']
                                ),
                        ];
                    }
                )
                ->values();


        $discount =
            round(
                (float)
                (
                    $validated['discount']
                    ?? 0
                ),
                2
            );


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
                'medicines' =>
                    'Pharmacy stock location is not configured.',
            ]);
        }


        $sale =
            DB::transaction(
                function () use (
                    $request,
                    $validated,
                    $patient,
                    $encounter,
                    $admission,
                    $requestedMedicines,
                    $discount,
                    $pharmacyLocation
                ) {

                    $ipBillingAccount =
                        null;


                    if ($admission) {

                        $ipBillingAccount =
                            IpBillingAccount::query()
                                ->where(
                                    'admission_id',
                                    $admission->id
                                )
                                ->lockForUpdate()
                                ->first();


                        if (! $ipBillingAccount) {
                            throw ValidationException::withMessages([
                                'admission_id' =>
                                    'No inpatient billing account was found for this admission.',
                            ]);
                        }


                        if (
                            $ipBillingAccount->status
                            !== 'open'
                        ) {
                            throw ValidationException::withMessages([
                                'admission_id' =>
                                    'Medicines can only be dispensed while the inpatient billing account is open.',
                            ]);
                        }
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | 1. FEFO Allocation from PHARMACY Location
                    |--------------------------------------------------------------------------
                    */

                    $allocations =
                        [];


                    $subtotal =
                        0;


                    foreach (
                        $requestedMedicines
                        as $requested
                    ) {

                        $medicine =
                            Medicine::query()
                                ->where(
                                    'is_active',
                                    true
                                )
                                ->findOrFail(
                                    $requested[
                                        'medicine_id'
                                    ]
                                );


                        $requiredQuantity =
                            (int)
                            $requested[
                                'quantity'
                            ];


                        /*
                        |--------------------------------------------------------------------------
                        | Eligible FEFO Batches
                        |--------------------------------------------------------------------------
                        |
                        | FEFO applies only to batches physically available
                        | at PHARMACY.
                        |
                        */

                        $candidateBatches =
                            PharmacyStockBatch::query()
                                ->where(
                                    'medicine_id',
                                    $medicine->id
                                )
                                ->where(
                                    'is_active',
                                    true
                                )
                                ->where(
                                    function ($query) {

                                        $query
                                            ->whereNull(
                                                'expiry_date'
                                            )
                                            ->orWhereDate(
                                                'expiry_date',
                                                '>=',
                                                today()
                                            );
                                    }
                                )
                                ->whereHas(
                                    'locationBalances',
                                    function ($query) use ($pharmacyLocation) {

                                        $query
                                            ->where(
                                                'pharmacy_stock_location_id',
                                                $pharmacyLocation->id
                                            )
                                            ->where(
                                                'quantity_available',
                                                '>',
                                                0
                                            );
                                    }
                                )
                                ->orderByRaw(
                                    'CASE
                                        WHEN expiry_date IS NULL
                                        THEN 1
                                        ELSE 0
                                     END'
                                )
                                ->orderBy(
                                    'expiry_date'
                                )
                                ->orderBy(
                                    'received_date'
                                )
                                ->orderBy(
                                    'id'
                                )
                                ->get();


                        /*
                        |--------------------------------------------------------------------------
                        | Lock Batch + Pharmacy Location Balance
                        |--------------------------------------------------------------------------
                        */

                        $lockedBatches =
                            collect();


                        foreach (
                            $candidateBatches
                            as $candidateBatch
                        ) {

                            $batch =
                                PharmacyStockBatch::query()
                                    ->whereKey(
                                        $candidateBatch->id
                                    )
                                    ->lockForUpdate()
                                    ->firstOrFail();


                            $locationBalance =
                                PharmacyStockLocationBalance::query()
                                    ->where(
                                        'pharmacy_stock_location_id',
                                        $pharmacyLocation->id
                                    )
                                    ->where(
                                        'pharmacy_stock_batch_id',
                                        $batch->id
                                    )
                                    ->lockForUpdate()
                                    ->first();


                            if (
                                ! $locationBalance
                                ||
                                (int)
                                $locationBalance->quantity_available
                                <= 0
                            ) {

                                continue;
                            }


                            $lockedBatches->push([
                                'batch' =>
                                    $batch,

                                'location_balance' =>
                                    $locationBalance,
                            ]);
                        }


                        $totalAvailable =
                            (int)
                            $lockedBatches->sum(
                                fn ($row) =>
                                    (int)
                                    $row[
                                        'location_balance'
                                    ]
                                        ->quantity_available
                            );


                        if (
                            $totalAvailable
                            < $requiredQuantity
                        ) {

                            throw ValidationException::withMessages([
                                'medicines' =>
                                    'Insufficient Pharmacy stock for '
                                    . $medicine->generic_name
                                    . '. Requested: '
                                    . $requiredQuantity
                                    . ', available in Pharmacy: '
                                    . $totalAvailable
                                    . '.',
                            ]);
                        }


                        $remaining =
                            $requiredQuantity;


                        foreach (
                            $lockedBatches
                            as $row
                        ) {

                            if (
                                $remaining <= 0
                            ) {
                                break;
                            }


                            /** @var PharmacyStockBatch $batch */
                            $batch =
                                $row['batch'];


                            /** @var PharmacyStockLocationBalance $locationBalance */
                            $locationBalance =
                                $row[
                                    'location_balance'
                                ];


                            $dispenseQuantity =
                                min(
                                    $remaining,
                                    (int)
                                    $locationBalance
                                        ->quantity_available
                                );


                            if (
                                $dispenseQuantity
                                <= 0
                            ) {
                                continue;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | GST-Inclusive Selling Price
                            |--------------------------------------------------------------------------
                            */

                            $unitPrice =
                                round(
                                    (float)
                                    $batch->selling_price,
                                    2
                                );


                            $grossAmount =
                                round(
                                    $dispenseQuantity
                                    * $unitPrice,
                                    2
                                );


                            $allocations[] = [

                                'medicine' =>
                                    $medicine,

                                'batch' =>
                                    $batch,

                                'location_balance' =>
                                    $locationBalance,

                                'quantity' =>
                                    $dispenseQuantity,

                                'unit_price' =>
                                    $unitPrice,

                                'gross_amount' =>
                                    $grossAmount,

                                'gst_percent' =>
                                    round(
                                        (float)
                                        (
                                            $medicine
                                                ->gst_percent
                                            ?? 0
                                        ),
                                        2
                                    ),
                            ];


                            $subtotal +=
                                $grossAmount;


                            $remaining -=
                                $dispenseQuantity;
                        }
                    }


                    $subtotal =
                        round(
                            $subtotal,
                            2
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | 2. Validate Discount
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $discount
                        > $subtotal
                    ) {

                        throw ValidationException::withMessages([
                            'discount' =>
                                'Discount cannot be greater than the subtotal.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Amount Payable
                    |--------------------------------------------------------------------------
                    */

                    $totalAmount =
                        round(
                            $subtotal
                            - $discount,
                            2
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | 3. GST Calculation
                    |--------------------------------------------------------------------------
                    */

                    $saleTaxableAmount =
                        0;


                    $saleCgstAmount =
                        0;


                    $saleSgstAmount =
                        0;


                    $saleIgstAmount =
                        0;


                    $discountAllocated =
                        0;


                    $allocationCount =
                        count(
                            $allocations
                        );


                    foreach (
                        $allocations
                        as $index => &$allocation
                    ) {

                        $grossAmount =
                            round(
                                (float)
                                $allocation[
                                    'gross_amount'
                                ],
                                2
                            );


                        if (
                            $discount > 0
                            &&
                            $subtotal > 0
                        ) {

                            if (
                                $index
                                ===
                                $allocationCount - 1
                            ) {

                                $lineDiscount =
                                    round(
                                        $discount
                                        - $discountAllocated,
                                        2
                                    );

                            } else {

                                $lineDiscount =
                                    round(
                                        $discount
                                        *
                                        (
                                            $grossAmount
                                            / $subtotal
                                        ),
                                        2
                                    );


                                $discountAllocated +=
                                    $lineDiscount;
                            }

                        } else {

                            $lineDiscount =
                                0;
                        }


                        $discountedGross =
                            round(
                                max(
                                    0,
                                    $grossAmount
                                    - $lineDiscount
                                ),
                                2
                            );


                        $gstPercent =
                            round(
                                (float)
                                $allocation[
                                    'gst_percent'
                                ],
                                2
                            );


                        if (
                            $gstPercent > 0
                        ) {

                            $taxableAmount =
                                round(
                                    $discountedGross
                                    /
                                    (
                                        1
                                        +
                                        (
                                            $gstPercent
                                            / 100
                                        )
                                    ),
                                    2
                                );


                            $gstAmount =
                                round(
                                    $discountedGross
                                    - $taxableAmount,
                                    2
                                );


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
                                0;

                        } else {

                            $taxableAmount =
                                $discountedGross;


                            $gstAmount =
                                0;


                            $cgstAmount =
                                0;


                            $sgstAmount =
                                0;


                            $igstAmount =
                                0;
                        }


                        $allocation[
                            'line_discount'
                        ] =
                            $lineDiscount;


                        $allocation[
                            'discounted_gross'
                        ] =
                            $discountedGross;


                        $allocation[
                            'taxable_amount'
                        ] =
                            $taxableAmount;


                        $allocation[
                            'cgst_amount'
                        ] =
                            $cgstAmount;


                        $allocation[
                            'sgst_amount'
                        ] =
                            $sgstAmount;


                        $allocation[
                            'igst_amount'
                        ] =
                            $igstAmount;


                        $saleTaxableAmount +=
                            $taxableAmount;


                        $saleCgstAmount +=
                            $cgstAmount;


                        $saleSgstAmount +=
                            $sgstAmount;


                        $saleIgstAmount +=
                            $igstAmount;
                    }


                    unset(
                        $allocation
                    );


                    $saleTaxableAmount =
                        round(
                            $saleTaxableAmount,
                            2
                        );


                    $saleCgstAmount =
                        round(
                            $saleCgstAmount,
                            2
                        );


                    $saleSgstAmount =
                        round(
                            $saleSgstAmount,
                            2
                        );


                    $saleIgstAmount =
                        round(
                            $saleIgstAmount,
                            2
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | 4. Payment / IP Running Account
                    |--------------------------------------------------------------------------
                    */

                    $paidAmount =
                        0;


                    $balanceAmount =
                        0;


                    $changeAmount =
                        0;


                    if ($admission) {

                        /*
                         * Inpatient medicines are not paid at the
                         * pharmacy counter. They are posted to the
                         * central IP billing ledger.
                         */
                        $paymentMode =
                            'ip_billing';


                        $saleStatus =
                            'completed';

                    } else {

                        $paymentMode =
                            $validated[
                                'payment_mode'
                            ];


                        /*
                        |--------------------------------------------------------------------------
                        | Cash
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $paymentMode
                            === 'cash'
                        ) {

                            $cashReceived =
                                round(
                                    (float)
                                    (
                                        $validated[
                                            'cash_received'
                                        ]
                                        ?? 0
                                    ),
                                    2
                                );


                            if (
                                $cashReceived
                                < $totalAmount
                            ) {

                                throw ValidationException::withMessages([
                                    'cash_received' =>
                                        'Cash received must be at least the bill total.',
                                ]);
                            }


                            $paidAmount =
                                $totalAmount;


                            $changeAmount =
                                round(
                                    $cashReceived
                                    - $totalAmount,
                                    2
                                );
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | UPI / Card
                        |--------------------------------------------------------------------------
                        */

                        if (
                            in_array(
                                $paymentMode,
                                [
                                    'upi',
                                    'card',
                                ],
                                true
                            )
                        ) {

                            $paidAmount =
                                $totalAmount;
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Credit / MHIS
                        |--------------------------------------------------------------------------
                        */

                        if (
                            in_array(
                                $paymentMode,
                                [
                                    'credit',
                                    'mhis',
                                ],
                                true
                            )
                        ) {

                            $balanceAmount =
                                $totalAmount;
                        }


                        $saleStatus =
                            $balanceAmount > 0
                                ? 'credit'
                                : 'completed';
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | 5. Concurrency-Safe Sale Number
                    |--------------------------------------------------------------------------
                    */

                    DB::statement(
                        "SELECT pg_advisory_xact_lock(hashtext('pharmacy_sale_number'))"
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Create Sale
                    |--------------------------------------------------------------------------
                    */

                    $sale =
                        PharmacySale::create([

                            'sale_no' =>
                                $this->generateSaleNumber(),

                            'patient_id' =>
                                $patient->id,

                            'encounter_id' =>
                                $encounter?->id,

                            'admission_id' =>
                                $admission?->id,

                            'sale_at' =>
                                now(),

                            'subtotal' =>
                                $subtotal,

                            'discount' =>
                                $discount,

                            'taxable_amount' =>
                                $saleTaxableAmount,

                            'cgst_amount' =>
                                $saleCgstAmount,

                            'sgst_amount' =>
                                $saleSgstAmount,

                            'igst_amount' =>
                                $saleIgstAmount,

                            'total_amount' =>
                                $totalAmount,

                            'paid_amount' =>
                                $paidAmount,

                            'balance_amount' =>
                                $balanceAmount,

                            'payment_mode' =>
                                $paymentMode,

                            'transaction_reference' =>
                                $validated[
                                    'transaction_reference'
                                ]
                                ?? null,

                            'status' =>
                                $saleStatus,

                            'remarks' =>
                                $admission
                                    ? trim(
                                        (
                                            $validated[
                                                'remarks'
                                            ]
                                            ?? ''
                                        )
                                        . (
                                            ! empty(
                                                $validated[
                                                    'remarks'
                                                ]
                                                ?? null
                                            )
                                                ? ' | '
                                                : ''
                                        )
                                        . 'IP Billing: '
                                        . $ipBillingAccount->account_no
                                    )
                                    : $this->buildSaleRemarks(
                                        $validated[
                                            'remarks'
                                        ]
                                        ?? null,
                                        $changeAmount
                                    ),

                            'created_by' =>
                                $request
                                    ->user()
                                    ?->id,
                        ]);


                    /*
                    |--------------------------------------------------------------------------
                    | 6. Create Sale Items + Deduct Stock
                    |--------------------------------------------------------------------------
                    |
                    | A sale leaves hospital inventory completely.
                    |
                    | Therefore BOTH balances decrease:
                    |
                    | 1. PHARMACY location balance
                    | 2. hospital-wide batch quantity_available
                    |
                    */

                    foreach (
                        $allocations
                        as $allocation
                    ) {

                        /** @var PharmacyStockBatch $batch */
                        $batch =
                            $allocation[
                                'batch'
                            ];


                        /** @var PharmacyStockLocationBalance $locationBalance */
                        $locationBalance =
                            $allocation[
                                'location_balance'
                            ];


                        $quantity =
                            (int)
                            $allocation[
                                'quantity'
                            ];


                        /*
                        |--------------------------------------------------------------------------
                        | Re-check Locked Pharmacy Balance
                        |--------------------------------------------------------------------------
                        */

                        $currentPharmacyBalance =
                            (int)
                            $locationBalance
                                ->quantity_available;


                        $newPharmacyBalance =
                            $currentPharmacyBalance
                            - $quantity;


                        if (
                            $newPharmacyBalance
                            < 0
                        ) {

                            throw ValidationException::withMessages([
                                'medicines' =>
                                    'Pharmacy stock changed while processing. Please review the sale and try again.',
                            ]);
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Re-check Global Hospital Balance
                        |--------------------------------------------------------------------------
                        */

                        $currentGlobalBalance =
                            (int)
                            $batch
                                ->quantity_available;


                        $newGlobalBalance =
                            $currentGlobalBalance
                            - $quantity;


                        if (
                            $newGlobalBalance
                            < 0
                        ) {

                            throw ValidationException::withMessages([
                                'medicines' =>
                                    'Hospital stock balance is inconsistent for '
                                    . $allocation[
                                        'medicine'
                                    ]
                                        ->generic_name
                                    . '. Please review stock before dispensing.',
                            ]);
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Deduct Pharmacy Location
                        |--------------------------------------------------------------------------
                        */

                        $locationBalance->update([

                            'quantity_available' =>
                                $newPharmacyBalance,
                        ]);


                        /*
                        |--------------------------------------------------------------------------
                        | Deduct Hospital-wide Stock
                        |--------------------------------------------------------------------------
                        */

                        $batch->update([

                            'quantity_available' =>
                                $newGlobalBalance,
                        ]);


                        /*
                        |--------------------------------------------------------------------------
                        | Sale Item
                        |--------------------------------------------------------------------------
                        */

                        $saleItem =
                            PharmacySaleItem::create([

                                'pharmacy_sale_id' =>
                                    $sale->id,

                                'medicine_id' =>
                                    $allocation[
                                        'medicine'
                                    ]->id,

                                'pharmacy_stock_batch_id' =>
                                    $batch->id,

                                'medicine_code' =>
                                    $allocation[
                                        'medicine'
                                    ]->code,

                                'medicine_name' =>
                                    $allocation[
                                        'medicine'
                                    ]->generic_name,

                                'brand_name' =>
                                    $allocation[
                                        'medicine'
                                    ]->brand_name,

                                'strength' =>
                                    $allocation[
                                        'medicine'
                                    ]->strength,

                                'unit' =>
                                    $allocation[
                                        'medicine'
                                    ]->unit,

                                'batch_number' =>
                                    $batch->batch_number,

                                'quantity' =>
                                    $quantity,

                                'unit_price' =>
                                    $allocation[
                                        'unit_price'
                                    ],

                                'gst_percent' =>
                                    $allocation[
                                        'gst_percent'
                                    ],

                                'taxable_amount' =>
                                    $allocation[
                                        'taxable_amount'
                                    ],

                                'cgst_amount' =>
                                    $allocation[
                                        'cgst_amount'
                                    ],

                                'sgst_amount' =>
                                    $allocation[
                                        'sgst_amount'
                                    ],

                                'igst_amount' =>
                                    $allocation[
                                        'igst_amount'
                                    ],

                                'amount' =>
                                    $allocation[
                                        'gross_amount'
                                    ],
                            ]);


                        /*
                        |--------------------------------------------------------------------------
                        | Hospital Stock Ledger
                        |--------------------------------------------------------------------------
                        |
                        | balance_after remains the hospital-wide balance.
                        |
                        */

                        PharmacyStockMovement::create([

                            'pharmacy_stock_batch_id' =>
                                $batch->id,

                            'movement_type' =>
                                'dispense',

                            'quantity' =>
                                $quantity,

                            'balance_after' =>
                                $newGlobalBalance,

                            'reference_type' =>
                                'pharmacy_sale',

                            'reference_id' =>
                                $sale->id,

                            'remarks' =>
                                'Dispensed from Pharmacy location against sale '
                                . $sale->sale_no
                                . '. Item #'
                                . $saleItem->id
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
                                    ?->id,

                            'movement_at' =>
                                now(),
                        ]);


                        if ($ipBillingAccount) {

                            $alreadyPosted =
                                IpBillingCharge::query()
                                    ->where(
                                        'source_type',
                                        'pharmacy_sale_item'
                                    )
                                    ->where(
                                        'source_id',
                                        $saleItem->id
                                    )
                                    ->exists();


                            if (! $alreadyPosted) {

                                $medicineDescription =
                                    trim(
                                        $saleItem->medicine_name
                                        . (
                                            $saleItem->brand_name
                                                ? ' (' . $saleItem->brand_name . ')'
                                                : ''
                                        )
                                        . (
                                            $saleItem->strength
                                                ? ' ' . $saleItem->strength
                                                : ''
                                        )
                                    );


                                IpBillingCharge::create([

                                    'ip_billing_account_id' =>
                                        $ipBillingAccount->id,

                                    'admission_id' =>
                                        $admission->id,

                                    'charge_date' =>
                                        today(),

                                    'charge_type' =>
                                        'pharmacy',

                                    'service_id' =>
                                        null,

                                    'service_order_item_id' =>
                                        null,

                                    'code' =>
                                        $saleItem->medicine_code
                                        ?: 'MED-' . $saleItem->id,

                                    'description' =>
                                        $medicineDescription,

                                    'quantity' =>
                                        $quantity,

                                    'unit_price' =>
                                        $allocation[
                                            'unit_price'
                                        ],

                                    'discount' =>
                                        $allocation[
                                            'line_discount'
                                        ],

                                    'amount' =>
                                        $allocation[
                                            'discounted_gross'
                                        ],

                                    'source_type' =>
                                        'pharmacy_sale_item',

                                    'source_id' =>
                                        $saleItem->id,

                                    'status' =>
                                        'active',

                                    'remarks' =>
                                        'Posted from pharmacy sale '
                                        . $sale->sale_no
                                        . ', batch '
                                        . $saleItem->batch_number
                                        . '.',

                                    'created_by' =>
                                        $request
                                            ->user()
                                            ?->id,
                                ]);
                            }
                        }
                    }


                    if ($ipBillingAccount) {
                        $this->recalculateIpBillingAccount(
                            $ipBillingAccount
                        );
                    }


                    return $sale;
                }
            );


        if ($admission) {

            return redirect()
                ->route(
                    'ip-billing.show',
                    $admission
                )
                ->with(
                    'success',
                    'Medicines dispensed successfully. Pharmacy sale '
                    . $sale->sale_no
                    . ' was posted to the inpatient running bill.'
                );
        }


        return redirect()
            ->route(
                'pharmacy.dispensing.receipt',
                $sale
            )
            ->with(
                'success',
                'Medicines dispensed successfully.'
            );
    }



    /**
     * Show completed pharmacy sale.
     */
    public function show(
        PharmacySale $sale
    ): View {

        $sale->load([
            'patient',
            'encounter.department',
            'encounter.doctor',
            'items.medicine',
            'items.stockBatch',
            'createdBy',
            'returns.items',
        ]);


        return view(
            'pharmacy.dispensing.show',
            compact(
                'sale'
            )
        );
    }



    /**
     * Printable pharmacy receipt.
     */
    public function receipt(
        PharmacySale $sale
    ): View {

        $sale->load([
            'patient',
            'encounter.department',
            'encounter.doctor',
            'items.medicine',
            'createdBy',
        ]);


        return view(
            'pharmacy.dispensing.receipt',
            compact(
                'sale'
            )
        );
    }



    /**
     * Recalculate inpatient billing totals after pharmacy posting.
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
     * Generate pharmacy sale number.
     *
     * Example:
     * PHS-20260914-000001
     *
     * Caller must hold:
     * pharmacy_sale_number
     */
    private function generateSaleNumber(): string
    {
        $prefix =
            'PHS-'
            . now()->format(
                'Ymd'
            )
            . '-';


        $lastSale =
            PharmacySale::query()
                ->where(
                    'sale_no',
                    'like',
                    $prefix . '%'
                )
                ->orderByDesc(
                    'id'
                )
                ->first();


        $nextNumber =
            1;


        if ($lastSale) {

            $lastSequence =
                (int)
                substr(
                    $lastSale->sale_no,
                    -6
                );


            $nextNumber =
                $lastSequence
                + 1;
        }


        return
            $prefix
            . str_pad(
                (string)
                $nextNumber,
                6,
                '0',
                STR_PAD_LEFT
            );
    }



    /**
     * Add cash-change information to remarks.
     */
    private function buildSaleRemarks(
        ?string $remarks,
        float $changeAmount
    ): ?string {

        $parts =
            [];


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


        if (
            $changeAmount > 0
        ) {

            $parts[] =
                'Change returned: ₹'
                . number_format(
                    $changeAmount,
                    2,
                    '.',
                    ''
                );
        }


        return
            count(
                $parts
            )
                ? implode(
                    ' | ',
                    $parts
                )
                : null;
    }
}