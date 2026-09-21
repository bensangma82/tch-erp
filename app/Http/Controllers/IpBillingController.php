<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\BedTariff;
use App\Models\IpBillingAccount;
use App\Models\IpBillingAdvance;
use App\Models\IpBillingCharge;
use App\Models\IpBillingMhisClaim;
use App\Models\IpBillingMhisReceipt;
use App\Models\IpBillingPayment;
use App\Models\Service;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IpBillingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | IP Billing Dashboard
    |--------------------------------------------------------------------------
    |
    | Shows currently admitted patients together with their running
    | inpatient billing account.
    |
    */

    public function index(Request $request)
    {
        $search = trim(
            (string) $request->query('search', '')
        );

        $admissions = Admission::query()
            ->with([
                'patient',
                'department',
                'consultant',
                'bed.ward',
                'currentBedAllocation.bed.ward',
                'billingAccount',
                'billingAccount.charges',
                'billingAccount.advances',
                'billingAccount.payments',
                'billingAccount.mhisClaims',
                'billingAccount.mhisReceipts',
            ])
            ->whereNull('closed_at')
            ->whereNull('discharged_at')
            ->when(
                $search !== '',
                function ($query) use ($search) {

                    $query->where(function ($subQuery) use ($search) {

                        $subQuery
                            ->where(
                                'admission_no',
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
                    });
                }
            )
            ->orderByDesc('admitted_at')
            ->paginate(25)
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Display Billing Summary
        |--------------------------------------------------------------------------
        */

        $admissions->getCollection()->transform(
            function ($admission) {

                $account = $admission->billingAccount;

                if (! $account) {

                    $admission->billing_summary = [
                        'subtotal' => 0,
                        'discount' => 0,
                        'net_amount' => 0,
                        'advance_amount' => 0,
                        'paid_amount' => 0,
                        'mhis_approved_amount' => 0,
                        'balance_amount' => 0,
                    ];

                    return $admission;
                }


                $activeCharges = $account->charges
                    ->where(
                        'status',
                        'active'
                    );


                $subtotal = round(
                    (float) $activeCharges->sum(
                        function ($charge) {
                            return round(
                                (float) $charge->quantity
                                * (float) $charge->unit_price,
                                2
                            );
                        }
                    ),
                    2
                );


                $discount = round(
                    (float) $activeCharges->sum(
                        function ($charge) {
                            return (float) $charge->discount;
                        }
                    ),
                    2
                );


                /*
                 * Gross subtotal = quantity × unit price before discount.
                 * Net amount = final payable value stored on each charge.
                 */
                $netAmount = round(
                    (float) $activeCharges->sum(
                        function ($charge) {
                            return (float) $charge->amount;
                        }
                    ),
                    2
                );


                $advanceAmount = round(
                    (float) $account->advances
                        ->where(
                            'status',
                            'active'
                        )
                        ->sum(
                            function ($advance) {
                                return (float) $advance->amount;
                            }
                        ),
                    2
                );


                $paidAmount = round(
                    (float) $account->payments
                        ->where(
                            'status',
                            'active'
                        )
                        ->sum(
                            function ($payment) {
                                return (float) $payment->amount;
                            }
                        ),
                    2
                );


                $mhisApprovedAmount = round(
                    (float) $account->mhisClaims
                        ->whereIn(
                            'status',
                            [
                                'approved',
                                'submitted',
                                'settled',
                            ]
                        )
                        ->sum(
                            function ($claim) {
                                return (float) $claim->approved_amount;
                            }
                        ),
                    2
                );


                $balanceAmount = round(
                    max(
                        $netAmount
                        - $advanceAmount
                        - $paidAmount
                        - $mhisApprovedAmount,
                        0
                    ),
                    2
                );


                $admission->billing_summary = [
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'net_amount' => $netAmount,
                    'advance_amount' => $advanceAmount,
                    'paid_amount' => $paidAmount,
                    'mhis_approved_amount' => $mhisApprovedAmount,
                    'balance_amount' => $balanceAmount,
                ];


                return $admission;
            }
        );


        return view(
            'ip-billing.index',
            compact(
                'admissions',
                'search'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Open / View Running IP Billing Account
    |--------------------------------------------------------------------------
    */

    public function show(Admission $admission)
{
    $admission->load([
        'patient',
        'department',
        'consultant',
        'bed.ward',
        'currentBedAllocation.bed.ward',
        'bedAllocations.bed.ward',
    ]);


    $account = $this->getOrCreateAccount(
        $admission
    );


    $this->recalculateAccount(
        $account
    );


    $account->refresh();


    $account->load([
        'charges.service',
        'charges.createdBy',
        'advances.receivedBy',
        'payments.receivedBy',
        'mhisClaims.createdBy',
        'mhisClaims.updatedBy',
        'mhisClaims.receipts.receivedBy',
        'mhisReceipts.receivedBy',
    ]);


    /*
    |--------------------------------------------------------------------------
    | Active IP Charge Master Services
    |--------------------------------------------------------------------------
    |
    | These services are available for direct inpatient billing.
    |
    | Laboratory and radiology are excluded because they should continue
    | through the IP Investigation workflow.
    |
    */

    $chargeServices = Service::query()
        ->with('department')
        ->where(
            'is_active',
            true
        )
        ->whereIn(
            'category',
            [
                'procedure',
                'consultation',
                'nursing',
                'equipment',
                'consumable',
                'facility',
                'other',
            ]
        )
        ->orderBy('category')
        ->orderBy('name')
        ->get();


    return view(
        'ip-billing.show',
        compact(
            'admission',
            'account',
            'chargeServices'
        )
    );
}


    /*
    |--------------------------------------------------------------------------
    | Add IP Laboratory / Radiology Investigations
    |--------------------------------------------------------------------------
    */

    public function createInvestigationOrder(
        Admission $admission
    ) {
        $admission->load([
            'patient',
            'department',
            'consultant',
            'bed.ward',
            'currentBedAllocation.bed.ward',
        ]);


        $account = $this->getOrCreateAccount(
            $admission
        );


        if ($account->status !== 'open') {
            throw ValidationException::withMessages([
                'investigations' =>
                    'Investigations can only be added while the IP billing account is open.',
            ]);
        }


        /*
         * Laboratory and radiology investigations are selected
         * directly from the active Service Master.
         */
        $services = Service::query()
            ->where(
                'is_active',
                true
            )
            ->whereIn(
                'category',
                [
                    'laboratory',
                    'radiology',
                ]
            )
            ->orderBy('category')
            ->orderBy('name')
            ->get();


        return view(
            'ip-billing.investigations',
            compact(
                'admission',
                'account',
                'services'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store IP Laboratory / Radiology Investigations
    |--------------------------------------------------------------------------
    */

    public function storeInvestigationOrder(
        Request $request,
        Admission $admission
    ) {
        $validated = $request->validate([
            'services' => [
                'required',
                'array',
                'min:1',
            ],

            'services.*.service_id' => [
                'required',
                'integer',
                'exists:services,id',
            ],

            'services.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);


        $account = $this->getOrCreateAccount(
            $admission
        );


        if ($account->status !== 'open') {
            throw ValidationException::withMessages([
                'services' =>
                    'Investigations can only be added while the IP billing account is open.',
            ]);
        }


        $serviceIds = collect(
            $validated['services']
        )
            ->pluck('service_id')
            ->map(
                fn ($id) =>
                    (int) $id
            )
            ->unique()
            ->values();


        /*
         * Service Master is authoritative for service details and price.
         * No submitted browser price is accepted.
         */
        $services = Service::query()
            ->whereIn(
                'id',
                $serviceIds
            )
            ->where(
                'is_active',
                true
            )
            ->whereIn(
                'category',
                [
                    'laboratory',
                    'radiology',
                ]
            )
            ->get()
            ->keyBy('id');


        if (
            $services->count()
            !== $serviceIds->count()
        ) {
            throw ValidationException::withMessages([
                'services' =>
                    'One or more selected investigations are unavailable, inactive or invalid.',
            ]);
        }


        $order = DB::transaction(
            function () use (
                $validated,
                $services,
                $admission,
                $account
            ) {

                /*
                 * Lock the account so finalization cannot race with
                 * diagnostic posting.
                 */
                $lockedAccount =
                    IpBillingAccount::query()
                        ->whereKey(
                            $account->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                if (
                    $lockedAccount->status
                    !== 'open'
                ) {
                    throw ValidationException::withMessages([
                        'services' =>
                            'The IP billing account has already been finalized.',
                    ]);
                }


                /*
                 * IP investigations do not require separate immediate
                 * payment. The order is authorized against the running
                 * inpatient account.
                 */
                $order = ServiceOrder::create([
                    'order_no' =>
                        $this->generateOrderNumber(),

                    'patient_id' =>
                        $admission->patient_id,

                    'encounter_id' =>
                        null,

                    'admission_id' =>
                        $admission->id,

                    'ordered_at' =>
                        now(),

                    'status' =>
                        'authorized',

                    'remarks' =>
                        $validated['remarks']
                        ?? null,

                    'created_by' =>
                        auth()->id(),
                ]);


                foreach (
                    $validated['services']
                    as $selectedService
                ) {

                    $service =
                        $services->get(
                            (int) $selectedService[
                                'service_id'
                            ]
                        );


                    if (! $service) {
                        throw ValidationException::withMessages([
                            'services' =>
                                'A selected investigation could not be loaded.',
                        ]);
                    }


                    $quantity =
                        (int) $selectedService[
                            'quantity'
                        ];


                    $unitPrice = round(
                        (float) $service->price,
                        2
                    );


                    $amount = round(
                        $unitPrice * $quantity,
                        2
                    );


                    /*
                     * Keep ServiceOrderItem semantics identical to the
                     * existing OPD diagnostic workflow.
                     */
                    $item = ServiceOrderItem::create([
                        'service_order_id' =>
                            $order->id,

                        'service_id' =>
                            $service->id,

                        'service_code' =>
                            $service->code,

                        'service_name' =>
                            $service->name,

                        'category' =>
                            $service->category,

                        'quantity' =>
                            $quantity,

                        'unit_price' =>
                            $unitPrice,

                        'amount' =>
                            $amount,

                        'status' =>
                            'ordered',

                        'requires_sample' =>
                            $service->requires_sample,

                        'requires_report' =>
                            $service->requires_report,
                    ]);


                    /*
                     * Defensive application-level duplicate protection.
                     * The whole transaction is rolled back if a duplicate
                     * diagnostic ledger posting is detected.
                     */
                    $alreadyPosted =
                        IpBillingCharge::query()
                            ->where(
                                'service_order_item_id',
                                $item->id
                            )
                            ->exists();


                    if ($alreadyPosted) {
                        throw ValidationException::withMessages([
                            'services' =>
                                'Duplicate inpatient investigation charge detected.',
                        ]);
                    }


                    $chargeType =
                        $service->category
                        === 'radiology'
                            ? 'radiology'
                            : 'laboratory';


                    /*
                     * Every IP diagnostic item is posted once into the
                     * central inpatient ledger.
                     */
                    IpBillingCharge::create([
                        'ip_billing_account_id' =>
                            $lockedAccount->id,

                        'admission_id' =>
                            $admission->id,

                        'charge_date' =>
                            now(),

                        'charge_type' =>
                            $chargeType,

                        'service_id' =>
                            $service->id,

                        'service_order_item_id' =>
                            $item->id,

                        'code' =>
                            $service->code,

                        'description' =>
                            $service->name,

                        'quantity' =>
                            $quantity,

                        'unit_price' =>
                            $unitPrice,

                        'discount' =>
                            0,

                        /*
                         * amount stores the net line value.
                         */
                        'amount' =>
                            $amount,

                        'source_type' =>
                            'service_order_item',

                        'source_id' =>
                            $item->id,

                        'status' =>
                            'active',

                        'remarks' =>
                            'IP investigation order: ' .
                            $order->order_no,

                        'created_by' =>
                            auth()->id(),
                    ]);
                }


                return $order;
            }
        );


        $this->recalculateAccount(
            $account
        );


        return redirect()
            ->route(
                'ip-billing.show',
                $admission
            )
            ->with(
                'success',
                'Investigation order ' .
                $order->order_no .
                ' added successfully to the inpatient bill.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Generate Bed Charges
    |--------------------------------------------------------------------------
    |
    | Charging rule used by this first version:
    |
    | - Maximum one automatic bed charge per admission per calendar date.
    | - If the patient occupied more than one bed on the same date, the bed
    |   occupied for the greatest duration on that date is selected.
    | - Each occupied calendar date is charged as one full bed day.
    | - The tariff applicable on that specific calendar date is used.
    | - Existing generated bed-day charges are never duplicated.
    |
    | This gives deterministic, duplicate-safe billing across bed transfers.
    | Hospital-specific admission/discharge cut-off rules can be introduced
    | later without changing the ledger structure.
    |
    */

    public function generateBedCharges(
        Admission $admission
    ) {
        $admission->load([
            'patient',
            'bedAllocations.bed.ward',
        ]);


        if (! $admission->admitted_at) {
            throw ValidationException::withMessages([
                'bed_charges' =>
                    'This admission does not have a valid admission date.',
            ]);
        }


        if ($admission->bedAllocations->isEmpty()) {
            throw ValidationException::withMessages([
                'bed_charges' =>
                    'No bed allocation history is available for this admission.',
            ]);
        }


        $account = $this->getOrCreateAccount(
            $admission
        );


        if ($account->status !== 'open') {
            throw ValidationException::withMessages([
                'bed_charges' =>
                    'Bed charges can only be generated while the IP billing account is open.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Billing Window
        |--------------------------------------------------------------------------
        |
        | For an active admission, generate up to today.
        | For a closed/discharged admission, stop on that date.
        |
        */

        $billingStart = $admission->admitted_at
            ->copy()
            ->startOfDay();


        $billingEndAt =
            $admission->discharged_at
            ?? $admission->closed_at
            ?? now();


        $billingEnd = $billingEndAt
            ->copy()
            ->startOfDay();


        if ($billingEnd->lt($billingStart)) {
            throw ValidationException::withMessages([
                'bed_charges' =>
                    'The admission billing period is invalid.',
            ]);
        }


        $allocations = $admission->bedAllocations
            ->filter(
                fn ($allocation) =>
                    $allocation->bed
                    && $allocation->bed->ward
                    && $allocation->allocated_at
            )
            ->sortBy('allocated_at')
            ->values();


        if ($allocations->isEmpty()) {
            throw ValidationException::withMessages([
                'bed_charges' =>
                    'No valid bed allocations with ward information were found.',
            ]);
        }


        $generatedCount = 0;
        $existingCount = 0;
        $noAllocationCount = 0;
        $missingTariffs = [];


        DB::transaction(function () use (
            $admission,
            $account,
            $billingStart,
            $billingEnd,
            $allocations,
            &$generatedCount,
            &$existingCount,
            &$noAllocationCount,
            &$missingTariffs
        ) {

            $lockedAccount = IpBillingAccount::query()
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();


            $date = $billingStart->copy();


            while ($date->lte($billingEnd)) {

                $chargeDate = $date->copy();

                $dayStart = $chargeDate
                    ->copy()
                    ->startOfDay();

                $dayEnd = $chargeDate
                    ->copy()
                    ->endOfDay();


                /*
                |--------------------------------------------------------------------------
                | Duplicate Protection
                |--------------------------------------------------------------------------
                |
                | BED-YYYYMMDD is unique in practice within one IP account.
                | We intentionally check all statuses, not only active charges,
                | so a cancelled bed-day is not silently regenerated.
                |
                */

                $chargeCode =
                    'BED-' .
                    $chargeDate->format('Ymd');


                $alreadyExists = IpBillingCharge::query()
                    ->where(
                        'ip_billing_account_id',
                        $lockedAccount->id
                    )
                    ->where(
                        'charge_type',
                        'bed'
                    )
                    ->where(
                        'code',
                        $chargeCode
                    )
                    ->exists();


                if ($alreadyExists) {

                    $existingCount++;

                    $date->addDay();

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Bed Occupied for Greatest Duration on this Calendar Date
                |--------------------------------------------------------------------------
                */

                $selectedAllocation = null;
                $selectedSeconds = -1;


                foreach ($allocations as $allocation) {

                    $allocationStart =
                        $allocation->allocated_at
                            ->copy();


                    $allocationEnd =
                        $allocation->released_at
                            ? $allocation->released_at->copy()
                            : (
                                $admission->discharged_at
                                ?? $admission->closed_at
                                ?? now()
                            )->copy();


                    /*
                     * No overlap with this calendar day.
                     */
                    if (
                        $allocationStart->gt($dayEnd)
                        || $allocationEnd->lt($dayStart)
                    ) {
                        continue;
                    }


                    $overlapStart =
                        $allocationStart->gt($dayStart)
                            ? $allocationStart
                            : $dayStart->copy();


                    $overlapEnd =
                        $allocationEnd->lt($dayEnd)
                            ? $allocationEnd
                            : $dayEnd->copy();


                    if ($overlapEnd->lt($overlapStart)) {
                        continue;
                    }


                    $seconds = abs(
                        $overlapStart->diffInSeconds(
                            $overlapEnd,
                            false
                        )
                    );


                    if ($seconds > $selectedSeconds) {

                        $selectedSeconds =
                            $seconds;

                        $selectedAllocation =
                            $allocation;
                    }
                }


                if (! $selectedAllocation) {

                    $noAllocationCount++;

                    $date->addDay();

                    continue;
                }


                $bed =
                    $selectedAllocation->bed;

                $ward =
                    $bed->ward;


                /*
                |--------------------------------------------------------------------------
                | Tariff Applicable on This Charge Date
                |--------------------------------------------------------------------------
                */

                $tariff = BedTariff::query()
                    ->where(
                        'ward_id',
                        $ward->id
                    )
                    ->where(
                        'bed_type',
                        $bed->bed_type
                    )
                    ->where(
                        'is_active',
                        true
                    )
                    ->whereDate(
                        'effective_from',
                        '<=',
                        $chargeDate->toDateString()
                    )
                    ->where(function ($query) use ($chargeDate) {

                        $query
                            ->whereNull('effective_to')
                            ->orWhereDate(
                                'effective_to',
                                '>=',
                                $chargeDate->toDateString()
                            );
                    })
                    ->orderByDesc('effective_from')
                    ->orderByDesc('id')
                    ->first();


                if (! $tariff) {

                    $missingTariffs[] = [
                        'date' =>
                            $chargeDate->format('d M Y'),

                        'ward' =>
                            $ward->name,

                        'bed_type' =>
                            $bed->bed_type,
                    ];

                    $date->addDay();

                    continue;
                }


                $rate = round(
                    (float) $tariff->rate_per_day,
                    2
                );


                IpBillingCharge::create([
                    'ip_billing_account_id' =>
                        $lockedAccount->id,

                    'admission_id' =>
                        $admission->id,

                    'charge_date' =>
                        $chargeDate->copy()->startOfDay(),

                    'charge_type' =>
                        'bed',

                    'service_id' =>
                        null,

                    'service_order_item_id' =>
                        null,

                    'code' =>
                        $chargeCode,

                    'description' =>
                        'Bed Charge - ' .
                        $ward->name .
                        ' / Bed ' .
                        $bed->bed_number .
                        ' (' .
                        ucfirst(
                            str_replace(
                                '_',
                                ' ',
                                $bed->bed_type
                            )
                        ) .
                        ')',

                    'quantity' =>
                        1,

                    'unit_price' =>
                        $rate,

                    'discount' =>
                        0,

                    'amount' =>
                        $rate,

                    'source_type' =>
                        'bed_allocation',

                    'source_id' =>
                        $selectedAllocation->id,

                    'status' =>
                        'active',

                    'remarks' =>
                        'Automatic daily bed charge. Tariff ID: ' .
                        $tariff->id,

                    'created_by' =>
                        auth()->id(),
                ]);


                $generatedCount++;


                $date->addDay();
            }
        });


        $this->recalculateAccount(
            $account
        );


        /*
        |--------------------------------------------------------------------------
        | Result Message
        |--------------------------------------------------------------------------
        */

        $message =
            $generatedCount .
            ' bed charge' .
            ($generatedCount === 1 ? '' : 's') .
            ' generated.';


        if ($existingCount > 0) {

            $message .=
                ' ' .
                $existingCount .
                ' existing bed-day' .
                ($existingCount === 1 ? '' : 's') .
                ' skipped.';
        }


        if ($noAllocationCount > 0) {

            $message .=
                ' ' .
                $noAllocationCount .
                ' date' .
                ($noAllocationCount === 1 ? '' : 's') .
                ' had no valid bed allocation.';
        }


        if (count($missingTariffs) > 0) {

            $uniqueMissingTariffs = collect(
                $missingTariffs
            )
                ->unique(
                    fn ($item) =>
                        $item['ward'] .
                        '|' .
                        $item['bed_type']
                )
                ->values();


            $missingDescription = $uniqueMissingTariffs
                ->take(5)
                ->map(
                    fn ($item) =>
                        $item['ward'] .
                        ' / ' .
                        ucfirst(
                            str_replace(
                                '_',
                                ' ',
                                $item['bed_type']
                            )
                        )
                )
                ->implode(', ');


            $message .=
                ' No applicable tariff was found for ' .
                count($missingTariffs) .
                ' bed-day' .
                (count($missingTariffs) === 1 ? '' : 's') .
                ': ' .
                $missingDescription .
                '.';
        }


        return redirect()
            ->route(
                'ip-billing.show',
                $admission
            )
            ->with(
                'success',
                $message
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Receive IP Advance / Deposit
    |--------------------------------------------------------------------------
    */

    public function receiveAdvance(
        Request $request,
        Admission $admission
    ) {
        $validated = $request->validate([
            'amount' => [
                'required',
                'numeric',
                'min:1',
                'max:9999999.99',
            ],

            'payment_mode' => [
                'required',
                'in:cash,upi,card',
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


        if (
            in_array(
                $validated['payment_mode'],
                ['upi', 'card'],
                true
            )
            && empty(
                trim(
                    (string) (
                        $validated['transaction_reference']
                        ?? ''
                    )
                )
            )
        ) {
            throw ValidationException::withMessages([
                'transaction_reference' =>
                    'Transaction reference is required for UPI or card payments.',
            ]);
        }


        $account = $this->getOrCreateAccount(
            $admission
        );


        if ($account->status !== 'open') {
            throw ValidationException::withMessages([
                'amount' =>
                    'Advance can only be received while the IP billing account is open.',
            ]);
        }


        $advance = DB::transaction(function () use (
            $validated,
            $admission,
            $account
        ) {

            $lockedAccount = IpBillingAccount::query()
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();


            if ($lockedAccount->status !== 'open') {
                throw ValidationException::withMessages([
                    'amount' =>
                        'Advance can only be received while the IP billing account is open.',
                ]);
            }


            return IpBillingAdvance::create([
                'ip_billing_account_id' =>
                    $lockedAccount->id,

                'admission_id' =>
                    $admission->id,

                'patient_id' =>
                    $admission->patient_id,

                'receipt_no' =>
                    $this->generateAdvanceReceiptNumber(),

                'payment_date' =>
                    now(),

                'amount' =>
                    round(
                        (float) $validated['amount'],
                        2
                    ),

                'payment_mode' =>
                    $validated['payment_mode'],

                'transaction_reference' =>
                    $validated['transaction_reference']
                    ?? null,

                'remarks' =>
                    $validated['remarks']
                    ?? null,

                'status' =>
                    'active',

                'received_by' =>
                    auth()->id(),
            ]);
        });


        $this->recalculateAccount(
            $account
        );


        return redirect()
            ->route(
                'ip-billing.show',
                $admission
            )
            ->with(
                'success',
                'Advance of ₹' .
                number_format(
                    (float) $advance->amount,
                    2
                ) .
                ' received successfully. Receipt: ' .
                $advance->receipt_no
            );
    }


/*
|--------------------------------------------------------------------------
| Receive IP Balance Payment
|--------------------------------------------------------------------------
|
| Collects payment against the outstanding patient balance
| of a finalized IP bill.
|
*/

public function receivePayment(
    Request $request,
    Admission $admission
) {
    $validated = $request->validate([
        'amount' => [
            'required',
            'numeric',
            'min:0.01',
            'max:9999999.99',
        ],

        'payment_mode' => [
            'required',
            'in:cash,upi,card',
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


    /*
    |--------------------------------------------------------------------------
    | UPI / Card Reference
    |--------------------------------------------------------------------------
    */

    if (
        in_array(
            $validated['payment_mode'],
            ['upi', 'card'],
            true
        )
        && empty(
            trim(
                (string) (
                    $validated['transaction_reference']
                    ?? ''
                )
            )
        )
    ) {
        throw ValidationException::withMessages([
            'transaction_reference' =>
                'Transaction reference is required for UPI or card payments.',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Billing Account
    |--------------------------------------------------------------------------
    */

    $account = IpBillingAccount::query()
        ->where(
            'admission_id',
            $admission->id
        )
        ->firstOrFail();


    /*
    |--------------------------------------------------------------------------
    | Payment Transaction
    |--------------------------------------------------------------------------
    */

    $payment = DB::transaction(function () use (
        $validated,
        $admission,
        $account
    ) {

        /*
        |--------------------------------------------------------------------------
        | Lock Billing Account
        |--------------------------------------------------------------------------
        */

        $lockedAccount = IpBillingAccount::query()
            ->whereKey($account->id)
            ->lockForUpdate()
            ->firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Only Finalized Bills
        |--------------------------------------------------------------------------
        */

        if ($lockedAccount->status !== 'finalized') {
            throw ValidationException::withMessages([
                'amount' =>
                    'Payment can only be collected against a finalized IP bill.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Recalculate Current Outstanding Balance
        |--------------------------------------------------------------------------
        |
        | We deliberately calculate this inside the transaction
        | so that stale balance values cannot result in overpayment.
        |
        */

        $lockedAccount->load([
            'charges',
            'advances',
            'payments',
            'mhisClaims',
        ]);


        $activeCharges = $lockedAccount->charges
            ->where(
                'status',
                'active'
            );


        $netAmount = round(
            (float) $activeCharges->sum(
                function ($charge) {
                    return (float) $charge->amount;
                }
            ),
            2
        );


        $advanceAmount = round(
            (float) $lockedAccount->advances
                ->where(
                    'status',
                    'active'
                )
                ->sum(
                    function ($advance) {
                        return (float) $advance->amount;
                    }
                ),
            2
        );


        $paidAmount = round(
            (float) $lockedAccount->payments
                ->where(
                    'status',
                    'active'
                )
                ->sum(
                    function ($payment) {
                        return (float) $payment->amount;
                    }
                ),
            2
        );


        $mhisApprovedAmount = round(
            (float) $lockedAccount->mhisClaims
                ->whereIn(
                    'status',
                    [
                        'approved',
                        'submitted',
                        'settled',
                    ]
                )
                ->sum(
                    function ($claim) {
                        return (float) $claim->approved_amount;
                    }
                ),
            2
        );


        $currentBalance = round(
            max(
                $netAmount
                - $advanceAmount
                - $paidAmount
                - $mhisApprovedAmount,
                0
            ),
            2
        );


        /*
        |--------------------------------------------------------------------------
        | Payment Amount
        |--------------------------------------------------------------------------
        */

        $paymentAmount = round(
            (float) $validated['amount'],
            2
        );


        /*
        |--------------------------------------------------------------------------
        | Prevent Overpayment
        |--------------------------------------------------------------------------
        */

        if ($paymentAmount > $currentBalance) {
            throw ValidationException::withMessages([
                'amount' =>
                    'Payment cannot exceed the outstanding patient balance of ₹' .
                    number_format(
                        $currentBalance,
                        2
                    ) .
                    '.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Generate Receipt Number
        |--------------------------------------------------------------------------
        */

        $receiptNo =
            $this->generatePaymentReceiptNumber();


        /*
        |--------------------------------------------------------------------------
        | Create Patient Payment
        |--------------------------------------------------------------------------
        */

        $payment = IpBillingPayment::create([
            'ip_billing_account_id' =>
                $lockedAccount->id,

            'admission_id' =>
                $admission->id,

            'patient_id' =>
                $admission->patient_id,

            'receipt_no' =>
                $receiptNo,

            'payment_date' =>
                now(),

            'amount' =>
                $paymentAmount,

            'payment_mode' =>
                $validated['payment_mode'],

            'transaction_reference' =>
                $validated['transaction_reference']
                ?? null,

            'remarks' =>
                $validated['remarks']
                ?? null,

            'status' =>
                'active',

            'received_by' =>
                auth()->id(),
        ]);


        /*
        |--------------------------------------------------------------------------
        | Recalculate Account
        |--------------------------------------------------------------------------
        */

        $this->recalculateAccount(
            $lockedAccount
        );


        return $payment;
    });


    /*
    |--------------------------------------------------------------------------
    | Redirect
    |--------------------------------------------------------------------------
    */

    return redirect()
        ->route(
            'ip-billing.show',
            $admission
        )
        ->with(
            'success',
            'Payment of ₹' .
            number_format(
                (float) $payment->amount,
                2
            ) .
            ' received successfully. Receipt: ' .
            $payment->receipt_no
        );
}
    /*
    |--------------------------------------------------------------------------
    | Add Manual IP Charge
    |--------------------------------------------------------------------------
    */

   public function storeCharge(
    Request $request,
    Admission $admission
) {
    $validated = $request->validate([

        'service_id' => [
            'required',
            'integer',
            'exists:services,id',
        ],

        'quantity' => [
            'required',
            'numeric',
            'min:0.01',
            'max:99999.99',
        ],

        'discount' => [
            'nullable',
            'numeric',
            'min:0',
            'max:9999999.99',
        ],

        'remarks' => [
            'nullable',
            'string',
            'max:2000',
        ],

    ]);


    /*
    |--------------------------------------------------------------------------
    | Load Authoritative Service Master Record
    |--------------------------------------------------------------------------
    |
    | Description, code and price are deliberately NOT accepted from the
    | browser. PostgreSQL Service Master is authoritative.
    |
    */

    $service = Service::query()
        ->whereKey(
            $validated['service_id']
        )
        ->where(
            'is_active',
            true
        )
        ->whereIn(
            'category',
            [
                'procedure',
                'consultation',
                'nursing',
                'equipment',
                'consumable',
                'facility',
                'other',
            ]
        )
        ->first();


    if (! $service) {

        throw ValidationException::withMessages([
            'service_id' =>
                'The selected charge is unavailable, inactive or invalid.',
        ]);

    }


    $quantity = round(
        (float) $validated['quantity'],
        2
    );


    $unitPrice = round(
        (float) $service->price,
        2
    );


    $discount = round(
        (float) (
            $validated['discount']
            ?? 0
        ),
        2
    );


    $grossAmount = round(
        $quantity * $unitPrice,
        2
    );


    if ($discount > $grossAmount) {

        throw ValidationException::withMessages([
            'discount' =>
                'Discount cannot be greater than the gross charge amount.',
        ]);

    }


    $amount = round(
        $grossAmount - $discount,
        2
    );


    $account = $this->getOrCreateAccount(
        $admission
    );


    if ($account->status !== 'open') {

        throw ValidationException::withMessages([
            'service_id' =>
                'Charges can only be added while the IP billing account is open.',
        ]);

    }


    $charge = DB::transaction(
        function () use (
            $validated,
            $admission,
            $account,
            $service,
            $quantity,
            $unitPrice,
            $discount,
            $amount
        ) {

            /*
             * Lock account to prevent a charge being posted while
             * finalization is occurring simultaneously.
             */

            $lockedAccount =
                IpBillingAccount::query()
                    ->whereKey(
                        $account->id
                    )
                    ->lockForUpdate()
                    ->firstOrFail();


            if ($lockedAccount->status !== 'open') {

                throw ValidationException::withMessages([
                    'service_id' =>
                        'The IP billing account has already been finalized.',
                ]);

            }


            /*
             * Re-load and lock the Service Master record so the price used
             * for billing remains authoritative inside this transaction.
             */

            $lockedService =
                Service::query()
                    ->whereKey(
                        $service->id
                    )
                    ->lockForUpdate()
                    ->first();


            if (
                ! $lockedService
                || ! $lockedService->is_active
                || ! in_array(
                    $lockedService->category,
                    [
                        'procedure',
                        'consultation',
                        'nursing',
                        'equipment',
                        'consumable',
                        'facility',
                        'other',
                    ],
                    true
                )
            ) {

                throw ValidationException::withMessages([
                    'service_id' =>
                        'The selected charge is no longer available.',
                ]);

            }


            /*
             * Recalculate from the locked database price.
             */

            $authoritativeUnitPrice =
                round(
                    (float) $lockedService->price,
                    2
                );


            $authoritativeGross =
                round(
                    $quantity
                    * $authoritativeUnitPrice,
                    2
                );


            if (
                $discount
                > $authoritativeGross
            ) {

                throw ValidationException::withMessages([
                    'discount' =>
                        'Discount cannot be greater than the gross charge amount.',
                ]);

            }


            $authoritativeAmount =
                round(
                    $authoritativeGross
                    - $discount,
                    2
                );


            return IpBillingCharge::create([

                'ip_billing_account_id' =>
                    $lockedAccount->id,

                'admission_id' =>
                    $admission->id,

                'charge_date' =>
                    now(),

                'charge_type' =>
                    $lockedService->category,

                'service_id' =>
                    $lockedService->id,

                'service_order_item_id' =>
                    null,

                'code' =>
                    $lockedService->code,

                'description' =>
                    $lockedService->name,

                'quantity' =>
                    $quantity,

                'unit_price' =>
                    $authoritativeUnitPrice,

                'discount' =>
                    $discount,

                'amount' =>
                    $authoritativeAmount,

                'source_type' =>
                    'service_master',

                'source_id' =>
                    $lockedService->id,

                'status' =>
                    'active',

                'remarks' =>
                    $validated['remarks']
                    ?? null,

                'created_by' =>
                    auth()->id(),

            ]);

        }
    );


    $this->recalculateAccount(
        $account
    );


    return redirect()
        ->route(
            'ip-billing.show',
            $admission
        )
        ->with(
            'success',
            'Charge added successfully: '
            . $charge->description
            . ' — ₹'
            . number_format(
                (float) $charge->amount,
                2
            )
        );
}



    /*
    |--------------------------------------------------------------------------
    | Create / Update MHIS Claim
    |--------------------------------------------------------------------------
    |
    | MHIS approval and MHIS cash receipt are deliberately separated.
    |
    | - claim_amount = amount submitted / claimed from MHIS
    | - approved_amount = amount approved / authorized by MHIS
    | - actual MHIS receipts are stored separately in
    |   ip_billing_mhis_receipts and may arrive months later in parts
    |
    | settlement_amount and settlement_date remain on the claim only as
    | compatibility summary fields and are maintained automatically from
    | active MHIS receipt records.
    |
    */

    public function storeMhisClaim(
        Request $request,
        Admission $admission
    ) {
        $validated = $request->validate([
            'claim_no' => [
                'nullable',
                'string',
                'max:255',
            ],

            'authorization_no' => [
                'nullable',
                'string',
                'max:255',
            ],

            'package_code' => [
                'nullable',
                'string',
                'max:255',
            ],

            'package_name' => [
                'nullable',
                'string',
                'max:500',
            ],

            'claim_amount' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'approved_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            /*
             * Kept in validation for compatibility with the existing form.
             * These values are not accepted as the accounting source of truth.
             * Actual settlement comes from MHIS receipt records.
             */
            'settlement_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],

            'status' => [
                'required',
                'in:pending,approved,submitted,settled,rejected',
            ],

            'approval_date' => [
                'nullable',
                'date',
            ],

            'settlement_date' => [
                'nullable',
                'date',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ]);


        $account = $this->getOrCreateAccount(
            $admission
        );


        /*
         * Claim / approval details may legitimately change after discharge
         * because MHIS processing can continue for months. Therefore we do
         * not require the IP billing account itself to remain open here.
         */


        $claimAmount = round(
            (float) $validated['claim_amount'],
            2
        );


        $approvedAmount = round(
            (float) (
                $validated['approved_amount']
                ?? 0
            ),
            2
        );


        if (
            in_array(
                $validated['status'],
                ['approved', 'submitted', 'settled'],
                true
            )
            && $approvedAmount <= 0
        ) {
            throw ValidationException::withMessages([
                'approved_amount' =>
                    'Approved amount is required when the MHIS claim is approved, submitted or settled.',
            ]);
        }


        DB::transaction(function () use (
            $validated,
            $admission,
            $account,
            $claimAmount,
            $approvedAmount
        ) {

            $lockedAccount = IpBillingAccount::query()
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();


            $existingClaim = IpBillingMhisClaim::query()
                ->where(
                    'ip_billing_account_id',
                    $lockedAccount->id
                )
                ->lockForUpdate()
                ->first();


            $existingReceived = $existingClaim
                ? round(
                    (float) IpBillingMhisReceipt::query()
                        ->where(
                            'ip_billing_mhis_claim_id',
                            $existingClaim->id
                        )
                        ->where(
                            'status',
                            'active'
                        )
                        ->sum('amount'),
                    2
                )
                : 0.00;


            if (
                $approvedAmount > 0
                && $existingReceived > $approvedAmount
            ) {
                throw ValidationException::withMessages([
                    'approved_amount' =>
                        'Approved amount cannot be reduced below the MHIS amount already received (₹' .
                        number_format(
                            $existingReceived,
                            2
                        ) .
                        ').',
                ]);
            }


            $effectiveStatus =
                $validated['status'];


            $effectiveSettlementDate =
                null;


            if (
                $approvedAmount > 0
                && $existingReceived >= $approvedAmount
            ) {
                $effectiveStatus =
                    'settled';

                $effectiveSettlementDate =
                    IpBillingMhisReceipt::query()
                        ->where(
                            'ip_billing_mhis_claim_id',
                            $existingClaim?->id
                        )
                        ->where(
                            'status',
                            'active'
                        )
                        ->max('receipt_date');
            } elseif (
                $effectiveStatus === 'settled'
            ) {
                /*
                 * A claim cannot be manually marked fully settled while
                 * outstanding receivable still exists.
                 */
                $effectiveStatus =
                    'submitted';
            }


            $data = [
                'admission_id' =>
                    $admission->id,

                'patient_id' =>
                    $admission->patient_id,

                'claim_no' =>
                    $validated['claim_no']
                    ?? null,

                'authorization_no' =>
                    $validated['authorization_no']
                    ?? null,

                'package_code' =>
                    $validated['package_code']
                    ?? null,

                'package_name' =>
                    $validated['package_name']
                    ?? null,

                'claim_amount' =>
                    $claimAmount,

                'approved_amount' =>
                    $approvedAmount,

                /*
                 * Compatibility summary only. Actual receipts are the
                 * source of truth.
                 */
                'settlement_amount' =>
                    $existingReceived,

                'status' =>
                    $effectiveStatus,

                'approval_date' =>
                    $validated['approval_date']
                    ?? null,

                'settlement_date' =>
                    $effectiveSettlementDate,

                'remarks' =>
                    $validated['remarks']
                    ?? null,

                'updated_by' =>
                    auth()->id(),
            ];


            if ($existingClaim) {

                $existingClaim->update(
                    $data
                );

                return;
            }


            IpBillingMhisClaim::create(
                array_merge(
                    $data,
                    [
                        'ip_billing_account_id' =>
                            $lockedAccount->id,

                        'created_by' =>
                            auth()->id(),
                    ]
                )
            );
        });


        $this->recalculateAccount(
            $account
        );


        return redirect()
            ->route(
                'ip-billing.show',
                $admission
            )
            ->with(
                'success',
                'MHIS claim details saved successfully.'
            );
    }



    /*
    |--------------------------------------------------------------------------
    | Record MHIS Receipt / Partial Payment
    |--------------------------------------------------------------------------
    |
    | Each payment received from MHIS is stored separately so that:
    |
    | - receipts can arrive months after discharge,
    | - multiple partial payments can be recorded,
    | - outstanding MHIS receivable remains auditable,
    | - the original approved amount is never overwritten by cash receipts.
    |
    */

    public function storeMhisReceipt(
        Request $request,
        Admission $admission
    ) {
        $validated = $request->validate([
            'receipt_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:99999999.99',
            ],

            'payment_reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'transaction_reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'bank_reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ]);


        $account = $this->getOrCreateAccount(
            $admission
        );


        $receipt = DB::transaction(function () use (
            $validated,
            $admission,
            $account
        ) {

            $lockedAccount = IpBillingAccount::query()
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();


            $claim = IpBillingMhisClaim::query()
                ->where(
                    'ip_billing_account_id',
                    $lockedAccount->id
                )
                ->lockForUpdate()
                ->first();


            if (! $claim) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'Create the MHIS claim before recording an MHIS payment.',
                ]);
            }


            if (
                in_array(
                    $claim->status,
                    ['pending', 'rejected'],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'MHIS payment can only be recorded after the claim has been approved or submitted.',
                ]);
            }


            $approvedAmount = round(
                (float) $claim->approved_amount,
                2
            );


            if ($approvedAmount <= 0) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'The MHIS approved amount must be greater than zero before recording payment.',
                ]);
            }


            $alreadyReceived = round(
                (float) IpBillingMhisReceipt::query()
                    ->where(
                        'ip_billing_mhis_claim_id',
                        $claim->id
                    )
                    ->where(
                        'status',
                        'active'
                    )
                    ->sum('amount'),
                2
            );


            $receiptAmount = round(
                (float) $validated['amount'],
                2
            );


            $outstandingBeforeReceipt = round(
                max(
                    $approvedAmount - $alreadyReceived,
                    0
                ),
                2
            );


            if ($outstandingBeforeReceipt <= 0) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'This MHIS claim is already fully settled.',
                ]);
            }


            if (
                $receiptAmount
                > $outstandingBeforeReceipt
            ) {
                throw ValidationException::withMessages([
                    'amount' =>
                        'Receipt amount cannot exceed the outstanding MHIS receivable of ₹' .
                        number_format(
                            $outstandingBeforeReceipt,
                            2
                        ) .
                        '.',
                ]);
            }


            $receipt = IpBillingMhisReceipt::create([
                'ip_billing_mhis_claim_id' =>
                    $claim->id,

                'ip_billing_account_id' =>
                    $lockedAccount->id,

                'admission_id' =>
                    $admission->id,

                'patient_id' =>
                    $admission->patient_id,

                'receipt_date' =>
                    $validated['receipt_date'],

                'amount' =>
                    $receiptAmount,

                'payment_reference' =>
                    $validated['payment_reference']
                    ?? null,

                'transaction_reference' =>
                    $validated['transaction_reference']
                    ?? null,

                'bank_reference' =>
                    $validated['bank_reference']
                    ?? null,

                'remarks' =>
                    $validated['remarks']
                    ?? null,

                'status' =>
                    'active',

                'received_by' =>
                    auth()->id(),
            ]);


            $totalReceived = round(
                $alreadyReceived
                + $receiptAmount,
                2
            );


            $fullySettled =
                $totalReceived >= $approvedAmount;


            $claim->update([
                /*
                 * Compatibility summary only. Receipt rows remain the
                 * accounting source of truth.
                 */
                'settlement_amount' =>
                    $totalReceived,

                'settlement_date' =>
                    $fullySettled
                        ? $validated['receipt_date']
                        : null,

                'status' =>
                    $fullySettled
                        ? 'settled'
                        : (
                            $claim->status === 'approved'
                                ? 'approved'
                                : 'submitted'
                        ),

                'updated_by' =>
                    auth()->id(),
            ]);


            return $receipt;
        });


        $account->refresh();

        $account->load([
            'mhisClaims.receipts',
        ]);


        $claim =
            $account->mhisClaims
                ->first();


        $totalReceived = round(
            (float) (
                $claim?->receipts
                    ->where(
                        'status',
                        'active'
                    )
                    ->sum(
                        fn ($item) =>
                            (float) $item->amount
                    )
                ?? 0
            ),
            2
        );


        $approvedAmount = round(
            (float) (
                $claim?->approved_amount
                ?? 0
            ),
            2
        );


        $outstanding = round(
            max(
                $approvedAmount
                - $totalReceived,
                0
            ),
            2
        );


        return redirect()
            ->route(
                'ip-billing.show',
                $admission
            )
            ->with(
                'success',
                'MHIS payment of ₹' .
                number_format(
                    (float) $receipt->amount,
                    2
                ) .
                ' recorded successfully. Outstanding MHIS receivable: ₹' .
                number_format(
                    $outstanding,
                    2
                ) .
                '.'
            );
    }





    /*
    |--------------------------------------------------------------------------
    | Finalize IP Bill
    |--------------------------------------------------------------------------
    |
    | Finalization freezes the ordinary IP billing ledger. Bed charges,
    | manual charges and advances can no longer be added after this point.
    | MHIS claim processing and MHIS receipts may continue independently
    | because settlement can occur months after discharge.
    |
    */

    public function finalizeBill(
        Admission $admission
    ) {
        $account = $this->getOrCreateAccount(
            $admission
        );


        if ($account->status === 'finalized') {

            return redirect()
                ->route(
                    'ip-billing.final-bill',
                    $admission
                );
        }


        if ($account->status !== 'open') {
            throw ValidationException::withMessages([
                'finalize' =>
                    'Only an open IP billing account can be finalized.',
            ]);
        }


        $this->recalculateAccount(
            $account
        );


        DB::transaction(function () use (
            $account
        ) {

            $lockedAccount = IpBillingAccount::query()
                ->whereKey($account->id)
                ->lockForUpdate()
                ->firstOrFail();


            if ($lockedAccount->status === 'finalized') {
                return;
            }


            if ($lockedAccount->status !== 'open') {
                throw ValidationException::withMessages([
                    'finalize' =>
                        'Only an open IP billing account can be finalized.',
                ]);
            }


            if (! $lockedAccount->final_bill_no) {

                $lockedAccount->final_bill_no =
                    $this->generateFinalBillNumber();
            }


            $lockedAccount->status =
                'finalized';

            $lockedAccount->finalized_at =
                now();

            $lockedAccount->finalized_by =
                auth()->id();

            $lockedAccount->save();
        });


        $account->refresh();


        return redirect()
            ->route(
                'ip-billing.final-bill',
                $admission
            )
            ->with(
                'success',
                'IP bill finalized successfully. Final Bill No: ' .
                $account->final_bill_no
            );
    }



    /*
    |--------------------------------------------------------------------------
    | View / Print Final IP Bill
    |--------------------------------------------------------------------------
    */

    public function finalBill(
        Admission $admission
    ) {
        $admission->load([
            'patient',
            'department',
            'consultant',
            'bed.ward',
            'currentBedAllocation.bed.ward',
            'bedAllocations.bed.ward',
        ]);


        $account = IpBillingAccount::query()
            ->where(
                'admission_id',
                $admission->id
            )
            ->firstOrFail();


        if (
            $account->status !== 'finalized'
            || ! $account->final_bill_no
        ) {
            throw ValidationException::withMessages([
                'final_bill' =>
                    'This IP bill has not been finalized yet.',
            ]);
        }


        $account->load([
            'patient',
            'finalizedBy',
            'charges.service',
            'charges.createdBy',
            'advances.receivedBy',
            'payments.receivedBy',
            'mhisClaims.createdBy',
            'mhisClaims.updatedBy',
            'mhisClaims.receipts.receivedBy',
            'mhisReceipts.receivedBy',
        ]);


        $activeCharges = $account->charges
            ->where(
                'status',
                'active'
            );


        $grossAmount = round(
            (float) $activeCharges->sum(
                function ($charge) {
                    return round(
                        (float) $charge->quantity
                        * (float) $charge->unit_price,
                        2
                    );
                }
            ),
            2
        );


        $discountAmount = round(
            (float) $activeCharges->sum(
                function ($charge) {
                    return (float) $charge->discount;
                }
            ),
            2
        );


        $netAmount = round(
            (float) $activeCharges->sum(
                function ($charge) {
                    return (float) $charge->amount;
                }
            ),
            2
        );


        $advanceAmount = round(
            (float) $account->advances
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


        $paidAmount = round(
            (float) $account->paid_amount,
            2
        );


        $mhisClaim =
            $account->mhisClaims
                ->first();


        $mhisApprovedAmount = round(
            (float) $account->mhisClaims
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


        $mhisReceivedAmount = round(
            (float) $account->mhisReceipts
                ->where(
                    'status',
                    'active'
                )
                ->sum(
                    fn ($receipt) =>
                        (float) $receipt->amount
                ),
            2
        );


        $mhisOutstandingAmount = round(
            max(
                $mhisApprovedAmount
                - $mhisReceivedAmount,
                0
            ),
            2
        );


        $patientBalance = round(
            max(
                $netAmount
                - $advanceAmount
                - $paidAmount
                - $mhisApprovedAmount,
                0
            ),
            2
        );


        $chargeGroups = $activeCharges
            ->groupBy(
                function ($charge) {

                    return match (
                        $charge->charge_type
                    ) {
                        'bed' =>
                            'Bed / Ward Charges',

                        'laboratory', 'lab' =>
                            'Laboratory',

                        'radiology', 'imaging' =>
                            'Radiology / Imaging',

                        'pharmacy', 'medicine' =>
                            'Pharmacy / Medicines',

                        'dialysis' =>
                            'Dialysis',

                        'procedure' =>
                            'Procedures',

                        'consultation' =>
                            'Consultation / Professional Fees',

                        default =>
                            'Other Charges',
                    };
                }
            );


        return view(
            'ip-billing.final-bill',
            compact(
                'admission',
                'account',
                'activeCharges',
                'chargeGroups',
                'grossAmount',
                'discountAmount',
                'netAmount',
                'advanceAmount',
                'paidAmount',
                'mhisClaim',
                'mhisApprovedAmount',
                'mhisReceivedAmount',
                'mhisOutstandingAmount',
                'patientBalance'
            )
        );
    }




    /*
    |--------------------------------------------------------------------------
    | View / Print IP Advance Receipt
    |--------------------------------------------------------------------------
    */

    public function advanceReceipt(
        IpBillingAdvance $ipBillingAdvance
    ) {
        $ipBillingAdvance->load([
            'patient',
            'admission.patient',
            'admission.department',
            'admission.consultant',
            'admission.bed.ward',
            'admission.currentBedAllocation.bed.ward',
            'billingAccount',
            'receivedBy',
        ]);


        $admission =
            $ipBillingAdvance->admission;


        $account =
            $ipBillingAdvance->billingAccount;


        $patient =
            $ipBillingAdvance->patient
            ?? $admission?->patient;


        if (! $admission || ! $account || ! $patient) {
            abort(
                404,
                'The inpatient advance receipt is incomplete or unavailable.'
            );
        }


        return view(
            'ip-billing.advance-receipt',
            compact(
                'ipBillingAdvance',
                'admission',
                'account',
                'patient'
            )
        );
    }




    /*
    |--------------------------------------------------------------------------
    | Get or Create Running IP Billing Account
    |--------------------------------------------------------------------------
    */

    private function getOrCreateAccount(
        Admission $admission
    ): IpBillingAccount {

        return DB::transaction(
            function () use ($admission) {

                $account = IpBillingAccount::query()
                    ->where(
                        'admission_id',
                        $admission->id
                    )
                    ->lockForUpdate()
                    ->first();


                if ($account) {
                    return $account;
                }


                return IpBillingAccount::create([
                    'admission_id' =>
                        $admission->id,

                    'patient_id' =>
                        $admission->patient_id,

                    'account_no' =>
                        $this->generateAccountNumber(),

                    'opened_at' =>
                        now(),

                    'status' =>
                        'open',

                    'subtotal' =>
                        0,

                    'discount_amount' =>
                        0,

                    'net_amount' =>
                        0,

                    'advance_amount' =>
                        0,

                    'paid_amount' =>
                        0,

                    'balance_amount' =>
                        0,

                    'created_by' =>
                        auth()->id(),
                ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Recalculate Billing Account Totals
    |--------------------------------------------------------------------------
    */

    private function recalculateAccount(
        IpBillingAccount $account
    ): void {

        $account->load([
            'charges',
            'advances',
            'payments',
            'mhisClaims',
            'mhisReceipts',
        ]);


        $activeCharges = $account->charges
            ->where(
                'status',
                'active'
            );


        $subtotal = round(
            (float) $activeCharges->sum(
                function ($charge) {
                    return round(
                        (float) $charge->quantity
                        * (float) $charge->unit_price,
                        2
                    );
                }
            ),
            2
        );


        $discountAmount = round(
            (float) $activeCharges->sum(
                function ($charge) {
                    return (float) $charge->discount;
                }
            ),
            2
        );


        $netAmount = round(
            (float) $activeCharges->sum(
                function ($charge) {
                    return (float) $charge->amount;
                }
            ),
            2
        );


        $advanceAmount = round(
            (float) $account->advances
                ->where(
                    'status',
                    'active'
                )
                ->sum(
                    function ($advance) {
                        return (float) $advance->amount;
                    }
                ),
            2
        );


        $paidAmount = round(
            (float) $account->payments
                ->where(
                    'status',
                    'active'
                )
                ->sum(
                    function ($payment) {
                        return (float) $payment->amount;
                    }
                ),
            2
        );


        $mhisApprovedAmount = round(
            (float) $account->mhisClaims
                ->whereIn(
                    'status',
                    [
                        'approved',
                        'submitted',
                        'settled',
                    ]
                )
                ->sum(
                    function ($claim) {
                        return (float) $claim->approved_amount;
                    }
                ),
            2
        );


        $balanceAmount = round(
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

            'paid_amount' =>
                $paidAmount,

            'balance_amount' =>
                $balanceAmount,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Generate Service Order Number
    |--------------------------------------------------------------------------
    */

    private function generateOrderNumber(): string
    {
        $prefix =
            'ORD-' .
            now()->format('Ymd') .
            '-';


        $lastOrder = ServiceOrder::query()
            ->where(
                'order_no',
                'like',
                $prefix . '%'
            )
            ->orderByDesc('id')
            ->first();


        $nextNumber = 1;


        if ($lastOrder) {

            $lastSequence = (int) substr(
                $lastOrder->order_no,
                -6
            );


            $nextNumber =
                $lastSequence + 1;
        }


        return $prefix .
            str_pad(
                $nextNumber,
                6,
                '0',
                STR_PAD_LEFT
            );
    }




    /*
    |--------------------------------------------------------------------------
    | Generate Manual IP Charge Code
    |--------------------------------------------------------------------------
    */

    private function generateManualChargeCode(): string
    {
        $prefix =
            'MCH-' .
            now()->format('Ymd') .
            '-';


        $lastCharge = IpBillingCharge::query()
            ->where(
                'code',
                'like',
                $prefix . '%'
            )
            ->orderByDesc('id')
            ->first();


        $nextNumber = 1;


        if ($lastCharge) {

            $lastSequence = (int) substr(
                $lastCharge->code,
                -6
            );

            $nextNumber =
                $lastSequence + 1;
        }


        return $prefix .
            str_pad(
                $nextNumber,
                6,
                '0',
                STR_PAD_LEFT
            );
    }




    /*
    |--------------------------------------------------------------------------
    | Generate IP Advance Receipt Number
    |--------------------------------------------------------------------------
    */

    private function generateAdvanceReceiptNumber(): string
    {
        $prefix =
            'IPR-' .
            now()->format('Ymd') .
            '-';


        $lastAdvance = IpBillingAdvance::query()
            ->where(
                'receipt_no',
                'like',
                $prefix . '%'
            )
            ->orderByDesc('id')
            ->first();


        $nextNumber = 1;


        if ($lastAdvance) {

            $lastSequence = (int) substr(
                $lastAdvance->receipt_no,
                -6
            );

            $nextNumber =
                $lastSequence + 1;
        }


        return $prefix .
            str_pad(
                $nextNumber,
                6,
                '0',
                STR_PAD_LEFT
            );
    }


/*
|--------------------------------------------------------------------------
| Generate IP Balance Payment Receipt Number
|--------------------------------------------------------------------------
*/

private function generatePaymentReceiptNumber(): string
{
    $prefix =
        'IPP-' .
        now()->format('Ymd') .
        '-';


    $lastPayment = IpBillingPayment::query()
        ->where(
            'receipt_no',
            'like',
            $prefix . '%'
        )
        ->orderByDesc('id')
        ->first();


    $nextNumber = 1;


    if ($lastPayment) {

        $lastSequence = (int) substr(
            $lastPayment->receipt_no,
            -6
        );

        $nextNumber =
            $lastSequence + 1;
    }


    return $prefix .
        str_pad(
            $nextNumber,
            6,
            '0',
            STR_PAD_LEFT
        );
}

    /*
    |--------------------------------------------------------------------------
    | Generate Final IP Bill Number
    |--------------------------------------------------------------------------
    */

    private function generateFinalBillNumber(): string
    {
        $prefix =
            'IPF-' .
            now()->format('Ymd') .
            '-';


        $lastAccount = IpBillingAccount::query()
            ->where(
                'final_bill_no',
                'like',
                $prefix . '%'
            )
            ->orderByDesc('id')
            ->first();


        $nextNumber = 1;


        if ($lastAccount) {

            $lastSequence = (int) substr(
                $lastAccount->final_bill_no,
                -6
            );


            $nextNumber =
                $lastSequence + 1;
        }


        return $prefix .
            str_pad(
                $nextNumber,
                6,
                '0',
                STR_PAD_LEFT
            );
    }



    /*
    |--------------------------------------------------------------------------
    | Generate IP Billing Account Number
    |--------------------------------------------------------------------------
    */

    private function generateAccountNumber(): string
    {
        $prefix =
            'IPB-' .
            now()->format('Ymd') .
            '-';


        $lastAccount = IpBillingAccount::query()
            ->where(
                'account_no',
                'like',
                $prefix . '%'
            )
            ->orderByDesc('id')
            ->first();


        $nextNumber = 1;


        if ($lastAccount) {

            $lastSequence = (int) substr(
                $lastAccount->account_no,
                -6
            );

            $nextNumber =
                $lastSequence + 1;
        }


        return $prefix .
            str_pad(
                $nextNumber,
                6,
                '0',
                STR_PAD_LEFT
            );
    }
}
