<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Encounter;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpdController extends Controller
{
    /**
     * Show today's OPD encounters.
     */
    public function index()
    {
        $encounters = Encounter::with([
                'patient',
                'department',
                'doctor',
                'payments',
                'invoices',
            ])
            ->where(
                'encounter_type',
                'OPD'
            )
            ->whereDate(
                'encounter_date',
                today()
            )
            ->orderBy('queue_number')
            ->get();

        return view(
            'opd.index',
            compact('encounters')
        );
    }


    /**
     * Show OPD registration form.
     */
    public function create(Request $request)
    {
        $patient = null;

        if ($request->filled('patient_id')) {

            $patient = Patient::findOrFail(
                $request->patient_id
            );
        }

        $departments = Department::query()
            ->where(
                'type',
                'clinical'
            )
            ->where(
                'is_active',
                true
            )
            ->orderBy('name')
            ->get();

        $doctors = Employee::query()
            ->where(
                'is_doctor',
                true
            )
            ->where(
                'is_active',
                true
            )
            ->with('department')
            ->orderBy('first_name')
            ->get();

        return view(
            'opd.create',
            compact(
                'patient',
                'departments',
                'doctors'
            )
        );
    }


    /**
     * Register OPD encounter,
     * create invoice and record payment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([

            /*
            |--------------------------------------------------------------------------
            | Patient / OPD
            |--------------------------------------------------------------------------
            */

            'patient_id' => [
                'required',
                'exists:patients,id',
            ],

            'department_id' => [
                'required',
                'exists:departments,id',
            ],

            'doctor_id' => [
                'nullable',
                'exists:employees,id',
            ],

            'visit_type' => [
                'required',
                'in:new,follow_up,review,referral',
            ],

            'referred_by' => [
                'nullable',
                'string',
                'max:255',
            ],

            'reason_for_visit' => [
                'nullable',
                'string',
                'max:1000',
            ],


            /*
            |--------------------------------------------------------------------------
            | Charges
            |--------------------------------------------------------------------------
            */

            'consultation_fee' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
            ],

            'registration_fee' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
            ],


            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            'payment_mode' => [
                'required',
                'in:cash,upi,card,credit,mhis',
            ],

            'transaction_reference' => [
                'nullable',
                'string',
                'max:255',
                'required_if:payment_mode,upi,card',
            ],

            'amount_received' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Server-side amount calculation
        |--------------------------------------------------------------------------
        */

        $consultationFee = round(
            (float) $validated['consultation_fee'],
            2
        );

        $registrationFee = round(
            (float) $validated['registration_fee'],
            2
        );

        $totalAmount = round(
            $consultationFee + $registrationFee,
            2
        );

        $amountReceived = round(
            (float) $validated['amount_received'],
            2
        );


        /*
        |--------------------------------------------------------------------------
        | Prevent overpayment for non-cash payments
        |--------------------------------------------------------------------------
        */

        if (
            $validated['payment_mode'] !== 'cash' &&
            $amountReceived > $totalAmount
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'amount_received' =>
                        'Amount received cannot be greater than the total bill for this payment mode.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Calculate amount applied, balance and change
        |--------------------------------------------------------------------------
        */

        $paymentApplied = min(
            $amountReceived,
            $totalAmount
        );

        $balanceAmount = max(
            $totalAmount - $paymentApplied,
            0
        );

        $changeAmount = max(
            $amountReceived - $totalAmount,
            0
        );


        /*
        |--------------------------------------------------------------------------
        | Invoice status
        |--------------------------------------------------------------------------
        */

        if ($totalAmount <= 0) {

            $invoiceStatus = 'paid';

        } elseif ($paymentApplied <= 0) {

            $invoiceStatus = 'unpaid';

        } elseif ($paymentApplied < $totalAmount) {

            $invoiceStatus = 'partial';

        } else {

            $invoiceStatus = 'paid';
        }


        /*
        |--------------------------------------------------------------------------
        | Create Encounter + Invoice + Payment
        |--------------------------------------------------------------------------
        */

        $result = DB::transaction(
            function () use (
                $validated,
                $totalAmount,
                $amountReceived,
                $paymentApplied,
                $balanceAmount,
                $changeAmount,
                $invoiceStatus
            ) {

                /*
                |--------------------------------------------------------------------------
                | Queue number
                |--------------------------------------------------------------------------
                */

                $queueNumber =
                    $this->generateQueueNumber(
                        (int) $validated['department_id']
                    );


                /*
                |--------------------------------------------------------------------------
                | Create OPD encounter
                |--------------------------------------------------------------------------
                */

                $encounter = Encounter::create([

                    'encounter_no' =>
                        $this->generateEncounterNumber(),

                    'patient_id' =>
                        $validated['patient_id'],

                    'encounter_type' =>
                        'OPD',

                    'department_id' =>
                        $validated['department_id'],

                    'doctor_id' =>
                        $validated['doctor_id'] ?? null,

                    'encounter_date' =>
                        today(),

                    'encounter_time' =>
                        now()->format('H:i:s'),

                    'visit_type' =>
                        $validated['visit_type'],

                    'queue_number' =>
                        $queueNumber,

                    'referred_by' =>
                        $validated['referred_by'] ?? null,

                    'reason_for_visit' =>
                        $validated['reason_for_visit'] ?? null,

                    'status' =>
                        'waiting',

                    'created_by' =>
                        auth()->id(),
                ]);


                /*
                |--------------------------------------------------------------------------
                | Create invoice
                |--------------------------------------------------------------------------
                */

                $invoice = Invoice::create([

                    'invoice_no' =>
                        $this->generateInvoiceNumber(),

                    'patient_id' =>
                        $validated['patient_id'],

                    'encounter_id' =>
                        $encounter->id,

                    'invoice_date' =>
                        today(),

                    'invoice_type' =>
                        'OPD',

                    'subtotal' =>
                        $totalAmount,

                    'discount' =>
                        0,

                    'total_amount' =>
                        $totalAmount,

                    'paid_amount' =>
                        $paymentApplied,

                    'balance_amount' =>
                        $balanceAmount,

                    'status' =>
                        $invoiceStatus,

                    'created_by' =>
                        auth()->id(),
                ]);


                /*
                |--------------------------------------------------------------------------
                | Record payment
                |--------------------------------------------------------------------------
                */

                $payment = null;

                if ($paymentApplied > 0) {

                    $remarks = null;

                    if (
                        $validated['payment_mode'] === 'cash' &&
                        $changeAmount > 0
                    ) {

                        $remarks =
                            'Cash tendered: ₹'
                            . number_format(
                                $amountReceived,
                                2,
                                '.',
                                ''
                            )
                            . '; Change returned: ₹'
                            . number_format(
                                $changeAmount,
                                2,
                                '.',
                                ''
                            );
                    }


                    $payment = Payment::create([

                        'receipt_no' =>
                            $this->generateReceiptNumber(),

                        'invoice_id' =>
                            $invoice->id,

                        'patient_id' =>
                            $validated['patient_id'],

                        'encounter_id' =>
                            $encounter->id,

                        'payment_date' =>
                            now(),

                        'amount' =>
                            $paymentApplied,

                        'payment_mode' =>
                            $validated['payment_mode'],

                        'transaction_reference' =>
                            $validated['transaction_reference']
                                ?? null,

                        'remarks' =>
                            $remarks,

                        'received_by' =>
                            auth()->id(),
                    ]);
                }


                return [
                    'encounter' =>
                        $encounter,

                    'invoice' =>
                        $invoice,

                    'payment' =>
                        $payment,

                    'change_amount' =>
                        $changeAmount,
                ];
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Success message
        |--------------------------------------------------------------------------
        */

        $encounter =
            $result['encounter'];

        $invoice =
            $result['invoice'];

        $payment =
            $result['payment'];

        $changeAmount =
            $result['change_amount'];


        $message =
            'OPD registration completed. '
            . 'Queue number: '
            . $encounter->queue_number
            . '. Invoice: '
            . $invoice->invoice_no;


        if ($payment) {

            $message .=
                '. Receipt: '
                . $payment->receipt_no;
        }


        if ($changeAmount > 0) {

            $message .=
                '. Change to return: ₹'
                . number_format(
                    $changeAmount,
                    2
                );
        }


        if (
            (float) $invoice->balance_amount > 0
        ) {

            $message .=
                '. Balance due: ₹'
                . number_format(
                    (float) $invoice->balance_amount,
                    2
                );
        }


        return redirect()
            ->route('opd.index')
            ->with(
                'success',
                $message
            );
    }


    /**
     * Show printable OPD payment receipt.
     */
    public function receipt(Payment $payment)
    {
        $payment->load([
            'patient',
            'invoice',
            'encounter.department',
            'encounter.doctor',
            'receivedBy',
        ]);

        return view(
            'opd.receipt',
            compact('payment')
        );
    }


    /**
     * Show printable OPD card.
     */
    public function card(Encounter $encounter)
    {
        $encounter->load([
            'patient',
            'department',
            'doctor',
            'invoices.payments',
        ]);

        return view(
            'opd.card',
            compact('encounter')
        );
    }


    /**
     * Generate encounter number.
     *
     * Example:
     * ENC-20260909-000001
     */
    private function generateEncounterNumber(): string
    {
        $date =
            now()->format('Ymd');

        $last =
            Encounter::withTrashed()
                ->where(
                    'encounter_no',
                    'like',
                    "ENC-{$date}-%"
                )
                ->orderByDesc('id')
                ->first();

        $nextNumber = 1;

        if ($last) {

            $parts =
                explode(
                    '-',
                    $last->encounter_no
                );

            $nextNumber =
                ((int) end($parts)) + 1;
        }

        return sprintf(
            'ENC-%s-%06d',
            $date,
            $nextNumber
        );
    }


    /**
     * Generate invoice number.
     *
     * Example:
     * INV-20260909-000001
     */
    private function generateInvoiceNumber(): string
    {
        $date =
            now()->format('Ymd');

        $last =
            Invoice::query()
                ->where(
                    'invoice_no',
                    'like',
                    "INV-{$date}-%"
                )
                ->orderByDesc('id')
                ->first();

        $nextNumber = 1;

        if ($last) {

            $parts =
                explode(
                    '-',
                    $last->invoice_no
                );

            $nextNumber =
                ((int) end($parts)) + 1;
        }

        return sprintf(
            'INV-%s-%06d',
            $date,
            $nextNumber
        );
    }


    /**
     * Generate receipt number.
     *
     * Example:
     * RCP-20260909-000001
     */
    private function generateReceiptNumber(): string
    {
        $date =
            now()->format('Ymd');

        $last =
            Payment::query()
                ->where(
                    'receipt_no',
                    'like',
                    "RCP-{$date}-%"
                )
                ->orderByDesc('id')
                ->first();

        $nextNumber = 1;

        if ($last) {

            $parts =
                explode(
                    '-',
                    $last->receipt_no
                );

            $nextNumber =
                ((int) end($parts)) + 1;
        }

        return sprintf(
            'RCP-%s-%06d',
            $date,
            $nextNumber
        );
    }


    /**
     * Generate today's queue number
     * for a department.
     */
    private function generateQueueNumber(
        int $departmentId
    ): int {

        $lastQueue =
            Encounter::query()
                ->where(
                    'encounter_type',
                    'OPD'
                )
                ->where(
                    'department_id',
                    $departmentId
                )
                ->whereDate(
                    'encounter_date',
                    today()
                )
                ->max('queue_number');

        return ($lastQueue ?? 0) + 1;
    }
}