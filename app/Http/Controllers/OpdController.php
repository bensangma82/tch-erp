<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Encounter;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpdController extends Controller
{
    public function index()
    {
        $encounters = Encounter::with([
                'patient',
                'department',
                'doctor',
                'payments',
                'invoices',
            ])
            ->where('encounter_type', 'OPD')
            ->whereDate('encounter_date', today())
            ->orderBy('queue_number')
            ->get();

        return view('opd.index', compact('encounters'));
    }

    public function create(Request $request)
    {
        $patient = null;
        $recentVisits = collect();

        if ($request->filled('patient_id')) {
            $patient = Patient::findOrFail($request->patient_id);

            $recentVisits = Encounter::query()
                ->where('patient_id', $patient->id)
                ->where('encounter_type', 'OPD')
                ->whereDate('encounter_date', '>=', today()->subDays(7))
                ->with([
                    'department',
                    'doctor',
                ])
                ->orderByDesc('encounter_date')
                ->orderByDesc('id')
                ->get();
        }

        $departments = Department::query()
            ->where('type', 'clinical')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $doctors = Employee::query()
            ->where('is_doctor', true)
            ->where('is_active', true)
            ->with('department')
            ->orderBy('first_name')
            ->get();

        $internalReferralService = Service::query()
            ->where('code', 'INT-REF')
            ->where('is_active', true)
            ->first();

        $internalReferralFee = $internalReferralService
            ? (float) $internalReferralService->price
            : null;

        /*
        |--------------------------------------------------------------------------
        | Department OPD consultation fees
        |--------------------------------------------------------------------------
        |
        | During rollout, only departments with an active OPD-CONS-* service
        | are automated. Other departments can still use the manual fee until
        | their Service Master entry is created.
        |
        */
        $departmentConsultationFees = Service::query()
            ->where('category', 'consultation')
            ->where('is_active', true)
            ->where('code', 'like', 'OPD-CONS-%')
            ->whereNotNull('department_id')
            ->orderBy('id')
            ->get()
            ->groupBy('department_id')
            ->map(function ($services) {
                if ($services->count() !== 1) {
                    return null;
                }

                return [
                    'service_id' => $services->first()->id,
                    'code' => $services->first()->code,
                    'name' => $services->first()->name,
                    'price' => (float) $services->first()->price,
                ];
            })
            ->filter();

        return view(
            'opd.create',
            compact(
                'patient',
                'departments',
                'doctors',
                'recentVisits',
                'internalReferralFee',
                'departmentConsultationFees'
            )
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
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

            'referral_type' => [
                'nullable',
                'in:internal,external',
                'required_if:visit_type,referral',
            ],

            'referred_from_department_id' => [
                'nullable',
                'exists:departments,id',
                'required_if:referral_type,internal',
            ],

            'referring_doctor_id' => [
                'nullable',
                'exists:employees,id',
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
        | Internal referral rule
        |--------------------------------------------------------------------------
        */
        $isInternalReferral =
            $validated['visit_type'] === 'referral'
            &&
            ($validated['referral_type'] ?? null) === 'internal';

        $internalReferralService = null;
        $internalReferralFee = null;

        if ($isInternalReferral) {
            $internalReferralService = Service::query()
                ->where('code', 'INT-REF')
                ->where('is_active', true)
                ->first();

            if (! $internalReferralService) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'visit_type' =>
                            'Internal referral fee is not configured in Service Master. Please create or activate service code INT-REF.',
                    ]);
            }

            $internalReferralFee =
                round(
                    (float) $internalReferralService->price,
                    2
                );

            $sourceDepartmentId =
                (int) $validated['referred_from_department_id'];

            $destinationDepartmentId =
                (int) $validated['department_id'];

            if ($sourceDepartmentId === $destinationDepartmentId) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'referred_from_department_id' =>
                            'For an internal referral, the referring department and destination department must be different.',
                    ]);
            }

            $sourceDepartment =
                Department::findOrFail($sourceDepartmentId);

            $referralText =
                'Internal referral from '
                . $sourceDepartment->name;

            if (! empty($validated['referring_doctor_id'])) {
                $referringDoctor =
                    Employee::findOrFail(
                        $validated['referring_doctor_id']
                    );

                $referralText .=
                    ' - '
                    . $referringDoctor->full_name;
            }

            $validated['referred_by'] =
                $referralText;
        }

        /*
        |--------------------------------------------------------------------------
        | Normal department consultation service
        |--------------------------------------------------------------------------
        |
        | The backend is authoritative whenever an active OPD-CONS-* service
        | exists for the selected department.
        |
        */
        $departmentConsultationService = null;
        $departmentConsultationFee = null;

        if (! $isInternalReferral) {
            $departmentConsultationServices = Service::query()
                ->where('category', 'consultation')
                ->where('is_active', true)
                ->where('code', 'like', 'OPD-CONS-%')
                ->where(
                    'department_id',
                    $validated['department_id']
                )
                ->get();

            if ($departmentConsultationServices->count() > 1) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'department_id' =>
                            'More than one active OPD consultation service is configured for this department. Please keep only one active OPD-CONS-* service in Service Master.',
                    ]);
            }

            $departmentConsultationService =
                $departmentConsultationServices->first();

            if ($departmentConsultationService) {
                $departmentConsultationFee =
                    round(
                        (float) $departmentConsultationService->price,
                        2
                    );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Automatic 7-day free follow-up check
        |--------------------------------------------------------------------------
        |
        | Internal referral has precedence over free follow-up.
        |
        */
        $lastSameDepartmentVisit = null;
        $isFreeFollowUp = false;

        if (! $isInternalReferral) {
            $lastSameDepartmentVisit = Encounter::query()
                ->where(
                    'patient_id',
                    $validated['patient_id']
                )
                ->where(
                    'encounter_type',
                    'OPD'
                )
                ->where(
                    'department_id',
                    $validated['department_id']
                )
                ->whereDate(
                    'encounter_date',
                    '>=',
                    today()->subDays(7)
                )
                ->whereDate(
                    'encounter_date',
                    '<=',
                    today()
                )
                ->latest('encounter_date')
                ->latest('id')
                ->first();

            $isFreeFollowUp =
                $lastSameDepartmentVisit !== null;
        }

        if ($isFreeFollowUp) {
            $validated['visit_type'] =
                'follow_up';
        }

        /*
        |--------------------------------------------------------------------------
        | Server-side amount calculation
        |--------------------------------------------------------------------------
        */
        if ($isInternalReferral) {
            $consultationFee = $internalReferralFee;
        } elseif ($isFreeFollowUp) {
            $consultationFee = 0.00;
        } elseif ($departmentConsultationService) {
            $consultationFee = $departmentConsultationFee;
        } else {
            /*
             * Temporary fallback during Service Master rollout.
             * Once every clinical department has an OPD-CONS-* service,
             * this manual fallback can be removed.
             */
            $consultationFee = round(
                (float) $validated['consultation_fee'],
                2
            );
        }

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

        if ($totalAmount <= 0) {
            $invoiceStatus = 'paid';
        } elseif ($paymentApplied <= 0) {
            $invoiceStatus = 'unpaid';
        } elseif ($paymentApplied < $totalAmount) {
            $invoiceStatus = 'partial';
        } else {
            $invoiceStatus = 'paid';
        }

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
                $queueNumber =
                    $this->generateQueueNumber(
                        (int) $validated['department_id']
                    );

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
                    'encounter' => $encounter,
                    'invoice' => $invoice,
                    'payment' => $payment,
                    'change_amount' => $changeAmount,
                ];
            }
        );

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

        if ($isInternalReferral) {
            $message .=
                '. Internal referral fee ₹'
                . number_format(
                    (float) $internalReferralFee,
                    2
                )
                . ' applied.';
        } elseif ($isFreeFollowUp) {
            $message .=
                '. Free follow-up consultation applied.';
        } elseif ($departmentConsultationService) {
            $message .=
                '. Consultation fee ₹'
                . number_format(
                    (float) $departmentConsultationFee,
                    2
                )
                . ' applied from Service Master.';
        }

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

        if ((float) $invoice->balance_amount > 0) {
            $message .=
                '. Balance due: ₹'
                . number_format(
                    (float) $invoice->balance_amount,
                    2
                );
        }

        return redirect()
            ->route('opd.index')
            ->with('success', $message);
    }

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

    private function generateEncounterNumber(): string
    {
        $date = now()->format('Ymd');

        $last = Encounter::withTrashed()
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

    private function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');

        $last = Invoice::query()
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

    private function generateReceiptNumber(): string
    {
        $date = now()->format('Ymd');

        $last = Payment::query()
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

    private function generateQueueNumber(
        int $departmentId
    ): int {
        $lastQueue = Encounter::query()
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
