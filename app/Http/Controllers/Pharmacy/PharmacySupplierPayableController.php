<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\PharmacyGrn;
use App\Models\PharmacyPurchaseReturnItem;
use App\Models\PharmacySupplierPayable;
use App\Models\PharmacySupplierPayment;
use App\Services\Pharmacy\PharmacySupplierPayableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PharmacySupplierPayableController extends Controller
{
    public function __construct(
        private readonly PharmacySupplierPayableService $payableService
    ) {
    }



    /**
     * Supplier Payables Register.
     */
    public function index(
        Request $request
    ): View {

        $query =
            PharmacySupplierPayable::query()
                ->with([
                    'supplier',
                    'grn',
                    'createdBy',
                ])
                ->orderByDesc(
                    'payable_date'
                )
                ->orderByDesc(
                    'id'
                );


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
                        'payable_no',
                        'ilike',
                        '%' . $search . '%'
                    )
                        ->orWhere(
                            'supplier_invoice_no',
                            'ilike',
                            '%' . $search . '%'
                        )
                        ->orWhereHas(
                            'supplier',
                            function ($supplierQuery) use ($search) {

                                $supplierQuery
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
                        )
                        ->orWhereHas(
                            'grn',
                            function ($grnQuery) use ($search) {

                                $grnQuery->where(
                                    'grn_no',
                                    'ilike',
                                    '%' . $search . '%'
                                );
                            }
                        );
                }
            );
        }


        if (
            $request->filled(
                'status'
            )
        ) {

            $query->where(
                'status',
                $request->status
            );
        }


        if (
            $request->filled(
                'from_date'
            )
        ) {

            $query->whereDate(
                'payable_date',
                '>=',
                $request->from_date
            );
        }


        if (
            $request->filled(
                'to_date'
            )
        ) {

            $query->whereDate(
                'payable_date',
                '<=',
                $request->to_date
            );
        }


        $payables =
            $query
                ->paginate(20)
                ->withQueryString();


        return view(
            'pharmacy.supplier-payables.index',
            compact(
                'payables'
            )
        );
    }



    /**
     * Create payable from a GRN.
     */
    public function createFromGrn(
        Request $request,
        PharmacyGrn $pharmacyGrn
    ): RedirectResponse {

        $payable =
            DB::transaction(
                function () use (
                    $request,
                    $pharmacyGrn
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Lock GRN
                    |--------------------------------------------------------------------------
                    */

                    $grn =
                        PharmacyGrn::query()
                            ->with([
                                'supplier',
                            ])
                            ->whereKey(
                                $pharmacyGrn->id
                            )
                            ->lockForUpdate()
                            ->firstOrFail();


                    if (
                        $grn->status
                        !== 'completed'
                    ) {

                        throw ValidationException::withMessages([
                            'grn' =>
                                'A payable can only be created from a completed GRN.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Prevent duplicate payable for same GRN
                    |--------------------------------------------------------------------------
                    */

                    $existing =
                        PharmacySupplierPayable::query()
                            ->where(
                                'pharmacy_grn_id',
                                $grn->id
                            )
                            ->lockForUpdate()
                            ->first();


                    if ($existing) {

                        return $existing;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Concurrency-safe payable number
                    |--------------------------------------------------------------------------
                    */

                    DB::statement(
                        "SELECT pg_advisory_xact_lock(hashtext('pharmacy_supplier_payable_number'))"
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Supplier Credit Terms
                    |--------------------------------------------------------------------------
                    */

                    $creditDays =
                        (int)
                        (
                            $grn->supplier?->credit_days
                            ?? 0
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Payable Date
                    |--------------------------------------------------------------------------
                    */

                    $payableDate =
                        $grn->supplier_invoice_date
                        ?? $grn->grn_date;


                    /*
                    |--------------------------------------------------------------------------
                    | Due Date
                    |--------------------------------------------------------------------------
                    */

                    $dueDate =
                        $payableDate
                            ? $payableDate
                                ->copy()
                                ->addDays(
                                    $creditDays
                                )
                            : null;


                    /*
                    |--------------------------------------------------------------------------
                    | Original GRN Amount
                    |--------------------------------------------------------------------------
                    */

                    $originalAmount =
                        round(
                            (float)
                            $grn->total_amount,
                            2
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Existing Purchase Return Adjustment
                    |--------------------------------------------------------------------------
                    |
                    | A purchase return may already exist before the supplier
                    | payable is created.
                    |
                    */

                    $returnAdjustment =
                        round(
                            (float)
                            PharmacyPurchaseReturnItem::query()
                                ->whereHas(
                                    'grnItem',
                                    function ($query) use ($grn) {

                                        $query->where(
                                            'pharmacy_grn_id',
                                            $grn->id
                                        );
                                    }
                                )
                                ->sum(
                                    'line_total'
                                ),
                            2
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Opening Outstanding
                    |--------------------------------------------------------------------------
                    */

                    $outstanding =
                        max(
                            0,
                            round(
                                $originalAmount
                                - $returnAdjustment,
                                2
                            )
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Opening Status
                    |--------------------------------------------------------------------------
                    */

                    $status =
                        $outstanding <= 0
                            ? 'paid'
                            : 'unpaid';


                    /*
                    |--------------------------------------------------------------------------
                    | Create Payable
                    |--------------------------------------------------------------------------
                    */

                    $payable =
                        PharmacySupplierPayable::create([

                            'payable_no' =>
                                $this->generatePayableNumber(),

                            'pharmacy_supplier_id' =>
                                $grn->pharmacy_supplier_id,

                            'pharmacy_grn_id' =>
                                $grn->id,

                            'supplier_invoice_no' =>
                                $grn->supplier_invoice_no,

                            'supplier_invoice_date' =>
                                $grn->supplier_invoice_date,

                            'payable_date' =>
                                $payableDate,

                            'due_date' =>
                                $dueDate,

                            'original_amount' =>
                                $originalAmount,

                            'return_adjustment' =>
                                $returnAdjustment,

                            'other_adjustment' =>
                                0,

                            'paid_amount' =>
                                0,

                            'outstanding_amount' =>
                                $outstanding,

                            'status' =>
                                $status,

                            'remarks' =>
                                'Created from '
                                . $grn->grn_no,

                            'created_by' =>
                                $request->user()->id,
                        ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Synchronise Financial State
                    |--------------------------------------------------------------------------
                    |
                    | Use the shared service immediately so all payable financial
                    | calculations follow the same rules.
                    |
                    */

                    $this->payableService->recalculate(
                        $payable,
                        $request->user()->id
                    );


                    $payable->refresh();


                    return $payable;
                }
            );


        return redirect()
            ->route(
                'pharmacy.supplier-payables.show',
                $payable
            )
            ->with(
                'success',
                'Supplier payable created successfully.'
            );
    }



    /**
     * Show one payable with payment history.
     */
   
/**
 * Show one payable with payment history.
 */
public function show(
    PharmacySupplierPayable $pharmacySupplierPayable
): View {

    $pharmacySupplierPayable->load([
        'supplier',
        'grn',
        'createdBy',
        'payments.createdBy',
        'payments.financeAccount',
        'supplierCredits',
    ]);

    $financeAccounts = FinanceAccount::query()
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

    return view(
        'pharmacy.supplier-payables.show',
        compact(
            'pharmacySupplierPayable',
            'financeAccounts'
        )
    );
}


    /**
     * Record supplier payment.
     */
    public function storePayment(
        Request $request,
        PharmacySupplierPayable $pharmacySupplierPayable
    ): RedirectResponse {

        $validated =
            $request->validate([

                'payment_date' => [
                    'required',
                    'date',
                ],

                'amount' => [
                    'required',
                    'numeric',
                    'gt:0',
                ],

                'finance_account_id' => [
    'required',
    'integer',
    'exists:finance_accounts,id',
],

                'payment_method' => [
                    'required',
                    'string',
                    'in:cash,bank_transfer,cheque,upi,neft,rtgs,imps,other',
                ],

                'reference_no' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'bank_name' => [
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


        $payment =
            DB::transaction(
                function () use (
                    $validated,
                    $request,
                    $pharmacySupplierPayable
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Lock Payable
                    |--------------------------------------------------------------------------
                    */

                    $payable =
                        PharmacySupplierPayable::query()
                            ->whereKey(
                                $pharmacySupplierPayable->id
                            )
                            ->lockForUpdate()
                            ->firstOrFail();


                    /*
                    |--------------------------------------------------------------------------
                    | Recalculate Before Accepting Payment
                    |--------------------------------------------------------------------------
                    |
                    | Ensures purchase returns, previous payments and supplier
                    | credits are synchronised before validating this payment.
                    |
                    */

                    $this->payableService->recalculate(
                        $payable,
                        $request->user()->id
                    );


                    $payable->refresh();


                    /*
                    |--------------------------------------------------------------------------
                    | Prevent Payment Against Settled Payable
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $payable->status
                        === 'paid'
                    ) {

                        throw ValidationException::withMessages([
                            'amount' =>
                                'This payable is already fully settled.',
                        ]);
                    }


                    $amount =
                        round(
                            (float)
                            $validated['amount'],
                            2
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Prevent Overpayment
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $amount
                        >
                        (float)
                        $payable->outstanding_amount
                    ) {

                        throw ValidationException::withMessages([
                            'amount' =>
                                'Payment cannot exceed the outstanding amount of ₹'
                                . number_format(
                                    (float)
                                    $payable->outstanding_amount,
                                    2
                                )
                                . '.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Concurrency-safe Payment Number
                    |--------------------------------------------------------------------------
                    */

                    DB::statement(
                        "SELECT pg_advisory_xact_lock(hashtext('pharmacy_supplier_payment_number'))"
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Create Supplier Payment
                    |--------------------------------------------------------------------------
                    */

                    $payment =
                        PharmacySupplierPayment::create([

                            'payment_no' =>
                                $this->generatePaymentNumber(),

                            'pharmacy_supplier_payable_id' =>
                                $payable->id,

                            'pharmacy_supplier_id' =>
                                $payable->pharmacy_supplier_id,

                                'finance_account_id' =>
    $validated['finance_account_id'],

                            'payment_date' =>
                                $validated['payment_date'],

                            'amount' =>
                                $amount,

                            'payment_method' =>
                                $validated['payment_method'],

                            'reference_no' =>
                                $validated['reference_no']
                                ?? null,

                            'bank_name' =>
                                $validated['bank_name']
                                ?? null,

                            'remarks' =>
                                $validated['remarks']
                                ?? null,

                            'created_by' =>
                                $request->user()->id,
                        ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Recalculate After Payment
                    |--------------------------------------------------------------------------
                    |
                    | This updates:
                    | - paid amount
                    | - return adjustment
                    | - outstanding amount
                    | - payable status
                    | - supplier credit, if applicable
                    |
                    */

                    $this->payableService->recalculate(
                        $payable,
                        $request->user()->id
                    );


                    return $payment;
                }
            );


        return redirect()
            ->route(
                'pharmacy.supplier-payables.show',
                $payment->pharmacy_supplier_payable_id
            )
            ->with(
                'success',
                'Supplier payment recorded successfully.'
            );
    }



    /**
     * Generate payable number.
     *
     * Example:
     * PAY-20260914-000001
     *
     * Caller must hold:
     * pharmacy_supplier_payable_number
     */
    private function generatePayableNumber(): string
    {
        $prefix =
            'PAY-'
            . now()->format('Ymd')
            . '-';


        $last =
            PharmacySupplierPayable::query()
                ->where(
                    'payable_no',
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
                    $last->payable_no,
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
     * Generate payment number.
     *
     * Example:
     * SPP-20260914-000001
     *
     * Caller must hold:
     * pharmacy_supplier_payment_number
     */
    private function generatePaymentNumber(): string
    {
        $prefix =
            'SPP-'
            . now()->format('Ymd')
            . '-';


        $last =
            PharmacySupplierPayment::query()
                ->where(
                    'payment_no',
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
                    $last->payment_no,
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