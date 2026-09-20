<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\FinanceHead;
use App\Models\FinanceVoucher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FinanceVoucherController extends Controller
{
    /**
     * Display Finance vouchers.
     */
    public function index(Request $request): View
    {
        $query = FinanceVoucher::query()
            ->with([
                'financeHead',
                'financeAccount',
                'destinationAccount',
                'createdBy',
                'postedBy',
            ]);

        if ($request->filled('voucher_type')) {
            $query->where(
                'voucher_type',
                $request->string('voucher_type')->toString()
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->string('status')->toString()
            );
        }

        if ($request->filled('date_from')) {
            $query->whereDate(
                'voucher_date',
                '>=',
                $request->date('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'voucher_date',
                '<=',
                $request->date('date_to')
            );
        }

        $vouchers = $query
            ->orderByDesc('voucher_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('finance.vouchers.index', compact('vouchers'));
    }

    /**
     * Show form to create a Finance voucher.
     */
    public function create(Request $request): View
    {
        $type = $request->string('type')->toString();

        if (!in_array($type, ['receipt', 'payment', 'transfer'], true)) {
            $type = 'receipt';
        }

        $accounts = FinanceAccount::query()
            ->where('is_active', true)
            ->orderBy('account_type')
            ->orderBy('name')
            ->get();

        $heads = collect();

        if ($type === 'receipt') {
            $heads = FinanceHead::query()
                ->where('head_type', 'income')
                ->where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get();
        }

        if ($type === 'payment') {
            $heads = FinanceHead::query()
                ->where('head_type', 'expense')
                ->where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get();
        }

        return view(
            'finance.vouchers.create',
            compact('type', 'accounts', 'heads')
        );
    }

    /**
     * Store a Finance voucher as Draft.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'voucher_type' => [
                'required',
                Rule::in(['receipt', 'payment', 'transfer']),
            ],

            'voucher_date' => [
                'required',
                'date',
            ],

            'finance_head_id' => [
                'nullable',
                'integer',
                'exists:finance_heads,id',
            ],

            'finance_account_id' => [
                'required',
                'integer',
                'exists:finance_accounts,id',
            ],

            'destination_account_id' => [
                'nullable',
                'integer',
                'exists:finance_accounts,id',
            ],

            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'payment_mode' => [
                'nullable',
                'string',
                'max:50',
            ],

            'reference_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'party_name' => [
                'nullable',
                'string',
                'max:200',
            ],

            'narration' => [
                'nullable',
                'string',
            ],
        ]);

        $type = $validated['voucher_type'];

        $sourceAccount = FinanceAccount::query()
            ->whereKey($validated['finance_account_id'])
            ->where('is_active', true)
            ->first();

        if (!$sourceAccount) {
            return back()
                ->withErrors([
                    'finance_account_id' =>
                        'The selected finance account is not active.',
                ])
                ->withInput();
        }

        /*
         * Receipt / Payment validation.
         */
        if (in_array($type, ['receipt', 'payment'], true)) {
            if (empty($validated['finance_head_id'])) {
                return back()
                    ->withErrors([
                        'finance_head_id' =>
                            'A finance head is required.',
                    ])
                    ->withInput();
            }

            $head = FinanceHead::query()
                ->whereKey($validated['finance_head_id'])
                ->where('is_active', true)
                ->first();

            if (!$head) {
                return back()
                    ->withErrors([
                        'finance_head_id' =>
                            'The selected finance head is not active.',
                    ])
                    ->withInput();
            }

            $expectedHeadType =
                $type === 'receipt' ? 'income' : 'expense';

            if ($head->head_type !== $expectedHeadType) {
                return back()
                    ->withErrors([
                        'finance_head_id' =>
                            'The selected finance head does not match the voucher type.',
                    ])
                    ->withInput();
            }

            $validated['destination_account_id'] = null;
        }

        /*
         * Transfer validation.
         */
        if ($type === 'transfer') {
            $validated['finance_head_id'] = null;

            if (empty($validated['destination_account_id'])) {
                return back()
                    ->withErrors([
                        'destination_account_id' =>
                            'Destination account is required for a transfer.',
                    ])
                    ->withInput();
            }

            if (
                (int) $validated['finance_account_id']
                === (int) $validated['destination_account_id']
            ) {
                return back()
                    ->withErrors([
                        'destination_account_id' =>
                            'Source and destination accounts must be different.',
                    ])
                    ->withInput();
            }

            $destinationAccount = FinanceAccount::query()
                ->whereKey($validated['destination_account_id'])
                ->where('is_active', true)
                ->first();

            if (!$destinationAccount) {
                return back()
                    ->withErrors([
                        'destination_account_id' =>
                            'The destination account is not active.',
                    ])
                    ->withInput();
            }
        }

        $voucher = DB::transaction(function () use ($validated) {
            $voucher = new FinanceVoucher();

            $voucher->voucher_no =
                $this->generateVoucherNumber(
                    $validated['voucher_type']
                );

            $voucher->voucher_type =
                $validated['voucher_type'];

            $voucher->voucher_date =
                $validated['voucher_date'];

            $voucher->finance_head_id =
                $validated['finance_head_id'] ?? null;

            $voucher->finance_account_id =
                $validated['finance_account_id'];

            $voucher->destination_account_id =
                $validated['destination_account_id'] ?? null;

            $voucher->amount =
                $validated['amount'];

            $voucher->payment_mode =
                $validated['payment_mode'] ?? null;

            $voucher->reference_no =
                $validated['reference_no'] ?? null;

            $voucher->party_name =
                $validated['party_name'] ?? null;

            $voucher->narration =
                $validated['narration'] ?? null;

            $voucher->status = 'draft';
            $voucher->created_by = auth()->id();

            $voucher->save();

            return $voucher;
        });

        return redirect()
            ->route('finance.vouchers.show', $voucher)
            ->with(
                'success',
                'Finance voucher created as draft.'
            );
    }

    /**
     * Display one Finance voucher.
     */
    public function show(FinanceVoucher $financeVoucher): View
    {
        $financeVoucher->load([
            'financeHead',
            'financeAccount',
            'destinationAccount',
            'createdBy',
            'postedBy',
            'cancelledBy',
        ]);

        return view(
            'finance.vouchers.show',
            compact('financeVoucher')
        );
    }

    /**
     * Post a Draft voucher.
     */
    public function post(
        FinanceVoucher $financeVoucher
    ): RedirectResponse {
        if ($financeVoucher->status !== 'draft') {
            return back()->withErrors([
                'voucher' =>
                    'Only draft vouchers can be posted.',
            ]);
        }

        DB::transaction(function () use ($financeVoucher) {
            $voucher = FinanceVoucher::query()
                ->whereKey($financeVoucher->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($voucher->status !== 'draft') {
                return;
            }

            $voucher->status = 'posted';
            $voucher->posted_by = auth()->id();
            $voucher->posted_at = now();

            $voucher->save();
        });

        return back()->with(
            'success',
            'Finance voucher posted successfully.'
        );
    }

    /**
     * Cancel a Posted voucher.
     *
     * Financial vouchers are retained for audit history.
     */
    public function cancel(
        Request $request,
        FinanceVoucher $financeVoucher
    ): RedirectResponse {
        $validated = $request->validate([
            'cancellation_reason' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        if ($financeVoucher->status !== 'posted') {
            return back()->withErrors([
                'voucher' =>
                    'Only posted vouchers can be cancelled.',
            ]);
        }

        DB::transaction(
            function () use ($financeVoucher, $validated) {
                $voucher = FinanceVoucher::query()
                    ->whereKey($financeVoucher->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($voucher->status !== 'posted') {
                    return;
                }

                $voucher->status = 'cancelled';
                $voucher->cancelled_by = auth()->id();
                $voucher->cancelled_at = now();
                $voucher->cancellation_reason =
                    $validated['cancellation_reason'];

                $voucher->save();
            }
        );

        return back()->with(
            'success',
            'Finance voucher cancelled successfully.'
        );
    }

    /**
     * Generate the next voucher number.
     *
     * Examples:
     * REC-20260920-00001
     * PAY-20260920-00001
     * TRF-20260920-00001
     */
    private function generateVoucherNumber(
        string $voucherType
    ): string {
        $prefix = match ($voucherType) {
            'receipt' => 'REC',
            'payment' => 'PAY',
            'transfer' => 'TRF',
            default => 'VOU',
        };

        $date = now()->format('Ymd');

        /*
         * PostgreSQL transaction-level advisory lock.
         * Prevents two users generating the same voucher number
         * at the same time.
         */
        DB::select(
            "SELECT pg_advisory_xact_lock(hashtext(?))",
            ["finance-voucher-{$prefix}-{$date}"]
        );

        $pattern = "{$prefix}-{$date}-%";

        $lastVoucher = FinanceVoucher::query()
            ->where('voucher_no', 'like', $pattern)
            ->orderByDesc('voucher_no')
            ->first();

        $sequence = 1;

        if ($lastVoucher) {
            $lastSequence = (int) substr(
                $lastVoucher->voucher_no,
                -5
            );

            $sequence = $lastSequence + 1;
        }

        return sprintf(
            '%s-%s-%05d',
            $prefix,
            $date,
            $sequence
        );
    }
}