<?php

namespace App\Http\Controllers;

use App\Models\Encounter;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServiceOrder;
use App\Services\StaffMedicalBenefitService;
use App\Services\StaffMedicalBenefitUtilizationService;
use App\Models\ServiceOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Billing Counter
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $encounters = Encounter::with([
            'patient',
            'department',
            'doctor',
            'serviceOrders.items',
            'invoices.payments',
        ])
            ->where('encounter_type', 'OPD')
            ->whereDate('encounter_date', today())
            ->orderBy('queue_number')
            ->get();

        return view(
            'billing.index',
            compact('encounters')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Investigation Entry
    |--------------------------------------------------------------------------
    */

    public function create(Encounter $encounter)
    {
        $encounter->load([
            'patient',
            'department',
            'doctor',
            'serviceOrders.items',
        ]);

        $services = Service::query()
            ->with('department')
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view(
            'billing.create',
            compact(
                'encounter',
                'services'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store Investigation Order
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        Encounter $encounter
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

        $serviceIds = collect(
            $validated['services']
        )
            ->pluck('service_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $services = Service::query()
            ->whereIn('id', $serviceIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        if ($services->count() !== $serviceIds->count()) {
            return back()
                ->withInput()
                ->withErrors([
                    'services' =>
                        'One or more selected services are unavailable or inactive.',
                ]);
        }

        $order = DB::transaction(function () use (
            $validated,
            $services,
            $encounter
        ) {
            $order = ServiceOrder::create([
                'order_no' =>
                    $this->generateOrderNumber(),

                'patient_id' =>
                    $encounter->patient_id,

                'encounter_id' =>
                    $encounter->id,

                'ordered_at' =>
                    now(),

                'status' =>
                    'pending_payment',

                'remarks' =>
                    $validated['remarks'] ?? null,

                'created_by' =>
                    auth()->id(),
            ]);

            foreach (
                $validated['services']
                as $selectedService
            ) {
                $service = $services->get(
                    (int) $selectedService['service_id']
                );

                $quantity =
                    (int) $selectedService['quantity'];

                /*
                |--------------------------------------------------------------------------
                | Price comes from the database
                |--------------------------------------------------------------------------
                */

                $unitPrice = round(
                    (float) $service->price,
                    2
                );

                $amount = round(
                    $unitPrice * $quantity,
                    2
                );

                ServiceOrderItem::create([
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
            }

            return $order;
        });

        return redirect()
            ->route(
                'billing.payment',
                $order
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Payment Review
    |--------------------------------------------------------------------------
    */

    public function payment(
    ServiceOrder $serviceOrder,
    StaffMedicalBenefitService $staffMedicalBenefitService
) {
    $serviceOrder->load([
        'patient',
        'encounter.department',
        'encounter.doctor',
        'items',
    ]);

    $total = round(
        (float) $serviceOrder->items->sum('amount'),
        2
    );

    /*
    |--------------------------------------------------------------------------
    | Staff Medical Benefit Eligibility
    |--------------------------------------------------------------------------
    |
    | This is read-only. Opening the payment page does not consume
    | benefit entitlement or create any financial transaction.
    |
    */

    $staffMedicalBenefit = null;

    if ($serviceOrder->patient) {
        $staffMedicalBenefit =
            $staffMedicalBenefitService
                ->resolvePatientBenefit(
                    $serviceOrder->patient,
                    now()
                );
    }

    return view(
        'billing.payment',
        compact(
            'serviceOrder',
            'total',
            'staffMedicalBenefit'
        )
    );
}


    /*
    |--------------------------------------------------------------------------
    | Process Investigation Payment
    |--------------------------------------------------------------------------
    */

    public function processPayment(
        Request $request,
        ServiceOrder $serviceOrder,
        StaffMedicalBenefitService $staffMedicalBenefitService,
        StaffMedicalBenefitUtilizationService $staffMedicalBenefitUtilizationService
    ) {
        $serviceOrder->load([
            'patient',
            'encounter',
            'items',
        ]);

        $alreadyInvoiced = InvoiceItem::query()
            ->whereIn(
                'service_order_item_id',
                $serviceOrder->items->pluck('id')
            )
            ->exists();

        if ($alreadyInvoiced) {
            return redirect()
                ->route('billing.index')
                ->with(
                    'success',
                    'This investigation order has already been billed.'
                );
        }

        $validated = $request->validate([
            'payment_mode' => [
                'required',
                'in:cash,upi,card,credit,mhis,staff_medical_benefit',
            ],
            'amount_received' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999.99',
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

        $total = round(
            (float) $serviceOrder->items->sum('amount'),
            2
        );

        if ($total <= 0) {
            throw ValidationException::withMessages([
                'payment_mode' =>
                    'This investigation order has no payable amount.',
            ]);
        }

        $paymentMode = $validated['payment_mode'];

        $amountReceived = round(
            (float) ($validated['amount_received'] ?? 0),
            2
        );

        if (
            in_array($paymentMode, ['upi', 'card'], true)
            && $amountReceived > $total
        ) {
            throw ValidationException::withMessages([
                'amount_received' =>
                    'UPI/Card payment cannot be greater than the bill amount.',
            ]);
        }

        if (
            in_array(
                $paymentMode,
                ['cash', 'upi', 'card'],
                true
            )
            && $amountReceived <= 0
        ) {
            throw ValidationException::withMessages([
                'amount_received' =>
                    'Enter the amount received.',
            ]);
        }

        if (
            in_array(
                $paymentMode,
                ['credit', 'mhis'],
                true
            )
        ) {
            $amountReceived = 0;
        }

        $changeAmount = 0;
        $appliedPayment = 0;

        if ($paymentMode === 'cash') {
            $appliedPayment = min(
                $amountReceived,
                $total
            );

            $changeAmount = max(
                $amountReceived - $total,
                0
            );

        } elseif (
            in_array(
                $paymentMode,
                ['upi', 'card'],
                true
            )
        ) {
            $appliedPayment = min(
                $amountReceived,
                $total
            );
        }

        $staffMedicalBenefit = null;

        if ($paymentMode === 'staff_medical_benefit') {
            $staffMedicalBenefit =
                $staffMedicalBenefitService
                    ->resolvePatientBenefit(
                        $serviceOrder->patient,
                        now()
                    );

            if (! $staffMedicalBenefit) {
                throw ValidationException::withMessages([
                    'payment_mode' =>
                        'This patient is not currently eligible for Staff Medical Benefit.',
                ]);
            }

            if ((float) $staffMedicalBenefit['balance'] <= 0) {
                throw ValidationException::withMessages([
                    'payment_mode' =>
                        'No Staff Medical Benefit balance is available.',
                ]);
            }
        }

        $balance = round(
            max(
                $total - $appliedPayment,
                0
            ),
            2
        );

        if ($appliedPayment >= $total) {
            $invoiceStatus = 'paid';
        } elseif ($appliedPayment > 0) {
            $invoiceStatus = 'partial';
        } else {
            $invoiceStatus = 'unpaid';
        }

        try {
            $result = DB::transaction(function () use (
                $serviceOrder,
                $validated,
                $paymentMode,
                $total,
                $appliedPayment,
                $balance,
                $invoiceStatus,
                $amountReceived,
                $changeAmount,
                $staffMedicalBenefit,
                $staffMedicalBenefitUtilizationService
            ) {
                $invoice = Invoice::create([
                    'invoice_no' =>
                        $this->generateInvoiceNumber(),
                    'patient_id' =>
                        $serviceOrder->patient_id,
                    'encounter_id' =>
                        $serviceOrder->encounter_id,
                    'invoice_date' =>
                        now(),
                    'invoice_type' =>
                        'investigation',
                    'subtotal' =>
                        $total,
                    'discount' =>
                        0,
                    'total_amount' =>
                        $total,
                    'paid_amount' =>
                        $appliedPayment,
                    'balance_amount' =>
                        $balance,
                    'status' =>
                        $invoiceStatus,
                    'created_by' =>
                        auth()->id(),
                ]);

                foreach ($serviceOrder->items as $orderItem) {
                    InvoiceItem::create([
                        'invoice_id' =>
                            $invoice->id,
                        'service_order_item_id' =>
                            $orderItem->id,
                        'service_id' =>
                            $orderItem->service_id,
                        'code' =>
                            $orderItem->service_code,
                        'description' =>
                            $orderItem->service_name,
                        'quantity' =>
                            $orderItem->quantity,
                        'unit_price' =>
                            $orderItem->unit_price,
                        'discount' =>
                            0,
                        'amount' =>
                            $orderItem->amount,
                    ]);
                }

                $finalAppliedPayment = $appliedPayment;
                $finalBalance = $balance;
                $finalInvoiceStatus = $invoiceStatus;

                if ($paymentMode === 'staff_medical_benefit') {
                    $benefitTransaction =
                        $staffMedicalBenefitUtilizationService
                            ->utilize(
                                benefitAccount:
                                    $staffMedicalBenefit['account'],
                                patient:
                                    $serviceOrder->patient,
                                beneficiaryType:
                                    $staffMedicalBenefit['beneficiary_type'],
                                requestedAmount:
                                    $total,
                                sourceType:
                                    'invoice',
                                sourceId:
                                    $invoice->id,
                                sourceReference:
                                    $invoice->invoice_no,
                                transactionDate:
                                    now(),
                                dependent:
                                    $staffMedicalBenefit['dependent'],
                                grossBillAmount:
                                    $total,
                                mhisApprovedAmount:
                                    0,
                                residualBeforeBenefit:
                                    $total,
                                remarks:
                                    $validated['remarks'] ?? null,
                                userId:
                                    auth()->id()
                            );

                    $finalAppliedPayment = round(
                        (float) $benefitTransaction->amount,
                        2
                    );

                    $finalBalance = round(
                        max(
                            $total - $finalAppliedPayment,
                            0
                        ),
                        2
                    );

                    if ($finalAppliedPayment >= $total) {
                        $finalInvoiceStatus = 'paid';
                    } elseif ($finalAppliedPayment > 0) {
                        $finalInvoiceStatus = 'partial';
                    } else {
                        $finalInvoiceStatus = 'unpaid';
                    }

                    $invoice->update([
                        'paid_amount' =>
                            $finalAppliedPayment,
                        'balance_amount' =>
                            $finalBalance,
                        'status' =>
                            $finalInvoiceStatus,
                    ]);
                }

                $payment = null;

                if ($finalAppliedPayment > 0) {
                    $paymentRemarks =
                        $validated['remarks'] ?? null;

                    if (
                        $paymentMode === 'cash'
                        && $changeAmount > 0
                    ) {
                        $changeText =
                            'Cash received ₹' .
                            number_format(
                                $amountReceived,
                                2,
                                '.',
                                ''
                            ) .
                            '; change returned ₹' .
                            number_format(
                                $changeAmount,
                                2,
                                '.',
                                ''
                            );

                        $paymentRemarks =
                            $paymentRemarks
                                ? $paymentRemarks . ' | ' . $changeText
                                : $changeText;
                    }

                    if ($paymentMode === 'staff_medical_benefit') {
                        $benefitText =
                            'Staff Medical Benefit applied ₹' .
                            number_format(
                                $finalAppliedPayment,
                                2,
                                '.',
                                ''
                            );

                        $paymentRemarks =
                            $paymentRemarks
                                ? $paymentRemarks . ' | ' . $benefitText
                                : $benefitText;
                    }

                    $payment = Payment::create([
                        'receipt_no' =>
                            $this->generateReceiptNumber(),
                        'invoice_id' =>
                            $invoice->id,
                        'patient_id' =>
                            $serviceOrder->patient_id,
                        'encounter_id' =>
                            $serviceOrder->encounter_id,
                        'payment_date' =>
                            now(),
                        'amount' =>
                            $finalAppliedPayment,
                        'payment_mode' =>
                            $paymentMode,
                        'transaction_reference' =>
                            $validated['transaction_reference'] ?? null,
                        'remarks' =>
                            $paymentRemarks,
                        'received_by' =>
                            auth()->id(),
                    ]);
                }

                if ($finalInvoiceStatus === 'paid') {
                    $serviceOrder->update([
                        'status' => 'paid',
                    ]);

                } elseif (
                    in_array(
                        $paymentMode,
                        ['credit', 'mhis'],
                        true
                    )
                ) {
                    $serviceOrder->update([
                        'status' => 'authorized',
                    ]);

                } else {
                    $serviceOrder->update([
                        'status' => 'pending_payment',
                    ]);
                }

                return [
                    'invoice' => $invoice,
                    'payment' => $payment,
                ];
            });

        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages([
                'payment_mode' =>
                    $exception->getMessage(),
            ]);
        }

        if ($result['payment']) {
            return redirect()
                ->route(
                    'billing.receipt',
                    $result['payment']
                );
        }

        return redirect()
            ->route('billing.index')
            ->with(
                'success',
                strtoupper($paymentMode) .
                ' investigation bill recorded successfully.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Outstanding Investigation Invoice Payment
    |--------------------------------------------------------------------------
    */

    public function invoicePayment(
        Invoice $invoice
    ) {
        $invoice->load([
            'patient',
            'encounter.department',
            'encounter.doctor',
            'items',
            'payments',
        ]);

        if ($invoice->invoice_type !== 'investigation') {
            abort(404);
        }

        if ((float) $invoice->balance_amount <= 0) {
            return redirect()
                ->route('billing.index')
                ->with('success', 'This investigation invoice is already fully paid.');
        }

        return view(
            'billing.invoice-payment',
            compact('invoice')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Process Outstanding Investigation Invoice Payment
    |--------------------------------------------------------------------------
    */

    public function processInvoicePayment(
        Request $request,
        Invoice $invoice
    ) {
        $validated = $request->validate([
            'payment_mode' => [
                'required',
                'in:cash,upi,card',
            ],
            'amount_received' => [
                'required',
                'numeric',
                'gt:0',
                'max:9999999.99',
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

        $paymentMode = $validated['payment_mode'];

        $amountReceived = round(
            (float) $validated['amount_received'],
            2
        );

        try {
            $payment = DB::transaction(function () use (
                $invoice,
                $validated,
                $paymentMode,
                $amountReceived
            ) {
                $lockedInvoice = Invoice::query()
                    ->whereKey($invoice->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedInvoice->invoice_type !== 'investigation') {
                    throw new \RuntimeException(
                        'This payment screen is only for investigation invoices.'
                    );
                }

                $currentPaid = round(
                    (float) $lockedInvoice->payments()->sum('amount'),
                    2
                );

                $invoiceTotal = round(
                    (float) $lockedInvoice->total_amount,
                    2
                );

                $currentBalance = round(
                    max($invoiceTotal - $currentPaid, 0),
                    2
                );

                if ($currentBalance <= 0) {
                    throw new \RuntimeException(
                        'This investigation invoice is already fully paid.'
                    );
                }

                if (
                    in_array($paymentMode, ['upi', 'card'], true)
                    && $amountReceived > $currentBalance
                ) {
                    throw new \RuntimeException(
                        'UPI/Card payment cannot be greater than the outstanding balance.'
                    );
                }

                $changeAmount = 0;

                if ($paymentMode === 'cash') {
                    $appliedPayment = min(
                        $amountReceived,
                        $currentBalance
                    );

                    $changeAmount = max(
                        $amountReceived - $currentBalance,
                        0
                    );
                } else {
                    $appliedPayment = min(
                        $amountReceived,
                        $currentBalance
                    );
                }

                $newPaid = round(
                    $currentPaid + $appliedPayment,
                    2
                );

                $newBalance = round(
                    max($invoiceTotal - $newPaid, 0),
                    2
                );

                $newStatus = $newBalance <= 0
                    ? 'paid'
                    : 'partial';

                $lockedInvoice->update([
                    'paid_amount' => $newPaid,
                    'balance_amount' => $newBalance,
                    'status' => $newStatus,
                ]);

                $paymentRemarks =
                    $validated['remarks'] ?? null;

                if (
                    $paymentMode === 'cash'
                    && $changeAmount > 0
                ) {
                    $changeText =
                        'Cash received ₹' .
                        number_format(
                            $amountReceived,
                            2,
                            '.',
                            ''
                        ) .
                        '; change returned ₹' .
                        number_format(
                            $changeAmount,
                            2,
                            '.',
                            ''
                        );

                    $paymentRemarks =
                        $paymentRemarks
                            ? $paymentRemarks . ' | ' . $changeText
                            : $changeText;
                }

                $payment = Payment::create([
                    'receipt_no' =>
                        $this->generateReceiptNumber(),
                    'invoice_id' =>
                        $lockedInvoice->id,
                    'patient_id' =>
                        $lockedInvoice->patient_id,
                    'encounter_id' =>
                        $lockedInvoice->encounter_id,
                    'payment_date' =>
                        now(),
                    'amount' =>
                        $appliedPayment,
                    'payment_mode' =>
                        $paymentMode,
                    'transaction_reference' =>
                        $validated['transaction_reference'] ?? null,
                    'remarks' =>
                        $paymentRemarks,
                    'received_by' =>
                        auth()->id(),
                ]);

                if ($newStatus === 'paid') {
                    $serviceOrderIds = InvoiceItem::query()
                        ->where('invoice_items.invoice_id', $lockedInvoice->id)
                        ->whereNotNull('invoice_items.service_order_item_id')
                        ->join(
                            'service_order_items',
                            'service_order_items.id',
                            '=',
                            'invoice_items.service_order_item_id'
                        )
                        ->pluck('service_order_items.service_order_id')
                        ->unique();

                    if ($serviceOrderIds->isNotEmpty()) {
                        ServiceOrder::query()
                            ->whereIn('id', $serviceOrderIds)
                            ->update([
                                'status' => 'paid',
                            ]);
                    }
                }

                return $payment;
            });

        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages([
                'amount_received' =>
                    $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route(
                'billing.receipt',
                $payment
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Investigation Receipt
    |--------------------------------------------------------------------------
    */

    public function receipt(
        Payment $payment
    ) {
        $payment->load([
            'patient',
            'invoice.items',
            'encounter.department',
            'encounter.doctor',
            'receivedBy',
        ]);

        return view(
            'billing.receipt',
            compact('payment')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Number Generators
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
            $lastSequence =
                (int) substr(
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


    private function generateInvoiceNumber(): string
    {
        $prefix =
            'INV-' .
            now()->format('Ymd') .
            '-';

        $lastInvoice = Invoice::query()
            ->where(
                'invoice_no',
                'like',
                $prefix . '%'
            )
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;

        if ($lastInvoice) {
            $lastSequence =
                (int) substr(
                    $lastInvoice->invoice_no,
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


    private function generateReceiptNumber(): string
    {
        $prefix =
            'RCP-' .
            now()->format('Ymd') .
            '-';

        $lastPayment = Payment::query()
            ->where(
                'receipt_no',
                'like',
                $prefix . '%'
            )
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;

        if ($lastPayment) {
            $lastSequence =
                (int) substr(
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
}