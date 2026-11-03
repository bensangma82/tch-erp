<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\MhisOpeningReceivable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MhisOpeningReceivableController extends Controller
{
    public function index()
    {
        $records = MhisOpeningReceivable::query()
            ->latest('opening_date')
            ->paginate(20);

        return view(
            'finance.mhis-opening-receivables.index',
            compact('records')
        );
    }

    public function approve(
        Request $request,
        MhisOpeningReceivable $opening
    ) {
        abort_unless(
            $request->user()->hasPermission('finance.mhis-opening.approve'),
            403
        );

        $validated = $request->validate([
            'reconciliation_confirmed' => ['required', 'accepted'],
        ]);

        DB::transaction(function () use ($opening, $request) {
            $opening = MhisOpeningReceivable::query()
                ->whereKey($opening->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($opening->status !== 'draft') {
                throw ValidationException::withMessages([
                    'opening' => 'Only draft opening balances can be approved.',
                ]);
            }

            if (
                $opening->created_by === null
                || (int) $opening->created_by === (int) $request->user()->id
            ) {
                throw ValidationException::withMessages([
                    'opening' => 'Independent Finance approval is required.',
                ]);
            }

            if (
                ! $opening->statement_reference
                || (float) $opening->opening_amount <= 0
            ) {
                throw ValidationException::withMessages([
                    'opening' => 'A valid opening balance and supporting statement are required.',
                ]);
            }

            $opening->status = 'approved';
            $opening->approved_by = $request->user()->id;
            $opening->approved_at = now();
            $opening->save();
        });

        return redirect()
            ->route('finance.mhis-opening-receivables.index')
            ->with('success', 'MHIS opening balance approved. Accounting posting remains pending.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'opening_date' => ['required', 'date', 'in:2027-04-01'],
            'opening_amount' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
                'max:9999999999999999.99',
            ],
            'statement_reference' => ['required', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ]);

        DB::transaction(function () use ($validated, $request) {
            if (MhisOpeningReceivable::query()
                ->where('opening_date', $validated['opening_date'])
                ->exists()) {
                throw ValidationException::withMessages([
                    'opening_date' => 'An opening MHIS receivable already exists for this date.',
                ]);
            }

            MhisOpeningReceivable::create([
                'opening_date' => $validated['opening_date'],
                'financial_year' => '2027-28',
                'opening_amount' => $validated['opening_amount'],
                'statement_reference' => $validated['statement_reference'],
                'remarks' => $validated['remarks'] ?? null,
                'status' => 'draft',
                'created_by' => $request->user()->id,
            ]);
        });

        return redirect()
            ->route('finance.mhis-opening-receivables.index')
            ->with('success', 'Draft opening MHIS receivable created. Finance approval is pending.');
    }
}
