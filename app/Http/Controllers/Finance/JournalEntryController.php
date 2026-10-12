<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\TallyExport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JournalEntryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Journal Entries Listing
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:draft,posted,reversed'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $query = JournalEntry::query()
            ->with('financeVoucher')
            ->withSum('lines as total_debit', 'debit')
            ->withSum('lines as total_credit', 'credit')
            ->orderByDesc('journal_date')
            ->orderByDesc('id');

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('journal_no', 'ilike', "%{$search}%")
                    ->orWhere('reference_no', 'ilike', "%{$search}%")
                    ->orWhere('narration', 'ilike', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate(
                'journal_date',
                '>=',
                $filters['date_from']
            );
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate(
                'journal_date',
                '<=',
                $filters['date_to']
            );
        }

        $journals = $query
            ->paginate(25)
            ->withQueryString();

        $exports = TallyExport::query()
            ->whereIn(
                'journal_entry_id',
                $journals->getCollection()->pluck('id')
            )
            ->get()
            ->keyBy('journal_entry_id');

        return view('finance.journals.index', [
            'journals' => $journals,
            'exports' => $exports,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Journal Entry Details
    |--------------------------------------------------------------------------
    */

    public function show(JournalEntry $journalEntry): View
    {
        $journalEntry->load([
            'lines.generalLedgerAccount',
            'lines.department',
            'financeVoucher',
            'createdBy',
            'postedBy',
            'reversalOf',
            'reversals',
        ]);

        $totalDebit = $journalEntry->lines->sum('debit');
        $totalCredit = $journalEntry->lines->sum('credit');

        $export = TallyExport::query()
            ->where('journal_entry_id', $journalEntry->id)
            ->first();

        return view('finance.journals.show', [
            'journal' => $journalEntry,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'export' => $export,
        ]);
    }
}
