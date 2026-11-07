<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\TallyExport;
use App\Services\Tally\TallyHttpClient;
use App\Services\Tally\TallyExportLock;
use App\Services\Tally\TallyJournalExportService;
use App\Services\Tally\TallyJournalReconciliationService;
use App\Services\Tally\TallyVoucherXmlRouter;
use App\Services\Tally\TallyResponseParser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class TallyJournalExportController extends Controller
{

    /**
     * Display posted journals and Tally export status.
     */
    public function index(): View
    {
        $request = request();

        $status = $request->query('status', 'not_exported');
        $allowed = [
            'not_exported', 'in_progress',
            'confirmed', 'needs_attention', 'all',
        ];

        if (! in_array($status, $allowed, true)) {
            $status = 'not_exported';
        }

        $query = JournalEntry::query()
            ->where('status', 'posted');

        match ($status) {
            'not_exported' => $query->whereDoesntHave('tallyExport'),
            'in_progress' => $query->whereHas('tallyExport',
                fn ($q) => $q->whereIn('status', [
                    'pending', 'sending', 'exported',
                ])),
            'confirmed' => $query->whereHas('tallyExport',
                fn ($q) => $q->where('status', 'confirmed')),
            'needs_attention' => $query->whereHas('tallyExport',
                fn ($q) => $q->whereIn('status', [
                    'failed', 'unknown',
                ])),
            default => null,
        };

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where('journal_no', 'ilike', '%'
                . addcslashes($search, '%_\\') . '%');
        }

        if ($request->filled('date_from')) {
            $query->whereDate('journal_date', '>=',
                $request->query('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('journal_date', '<=',
                $request->query('date_to'));
        }

        $journals = $query
            ->orderByDesc('journal_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $journalIds = $journals
            ->getCollection()
            ->pluck('id');

        $exports = TallyExport::query()
            ->whereIn('journal_entry_id', $journalIds)
            ->get()
            ->keyBy('journal_entry_id');

        return view('finance.tally.journals.index', [
            'journals' => $journals,
            'exports' => $exports,
            'tallyCompany' => config('tally.company'),
            'selectedStatus' => $status,
        ]);
    }

    /**
     * Validate a posted journal and generate XML in memory.
     *
     * Read-only operation.
     * No database writes or Tally transmission.
     */
    public function preview(
        JournalEntry $journalEntry,
        TallyJournalExportService $service,
        TallyVoucherXmlRouter $builder
    ): JsonResponse {

        $eligibility = $service->checkEligibility(
            $journalEntry
        );

        if (! $eligibility['eligible']) {
            return response()->json([
                'eligible' => false,
                'journal' => $journalEntry->journal_no,
                'errors' => $eligibility['errors'],
            ]);
        }

        try {
            $xml = $builder->build($journalEntry);
        } catch (Throwable $exception) {
            return response()->json([
                'eligible' => false,
                'journal' => $journalEntry->journal_no,
                'errors' => [
                    $exception->getMessage(),
                ],
            ]);
        }

        return response()->json([
            'eligible' => true,
            'journal' => $journalEntry->journal_no,
            'tally_company' => config('tally.company'),
            'xml_generated' => true,
            'xml_bytes' => strlen($xml),
        ]);
    }

    /**
     * Display the human-readable journal preview.
     *
     * Read-only operation.
     */
    public function showPreview(
        JournalEntry $journalEntry,
        TallyJournalExportService $service
    ): View {

        $journalEntry->load([
            'lines.generalLedgerAccount',
            'tallyExport',
        ]);

        $eligibility = $service->checkEligibility(
            $journalEntry
        );

        $totalDebit = $journalEntry->lines->sum(
            fn ($line) => (float) $line->debit
        );

        $totalCredit = $journalEntry->lines->sum(
            fn ($line) => (float) $line->credit
        );

        return view('finance.tally.journals.preview', [
            'journal' => $journalEntry,
            'export' => $journalEntry->tallyExport,
            'eligibility' => $eligibility,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'tallyCompany' => config('tally.company'),
        ]);
    }

    /**
     * Manually transmit a posted GL journal to TallyPrime.
     *
     * Direct transmission is disabled by default.
     *
     * Creates a durable export record before HTTP transmission.
     * Unknown outcomes require reconciliation before retry.
     */
    public function send(
        Request $request,
        JournalEntry $journalEntry,
        TallyJournalExportService $service,
        TallyVoucherXmlRouter $builder,
        TallyHttpClient $client,
        TallyResponseParser $parser
    ): RedirectResponse {


        /*
        |--------------------------------------------------------------------------
        | Journal Export Feature Flag
        |--------------------------------------------------------------------------
        */

        if (! filter_var(
            config('tally.journal_export_enabled', false),
            FILTER_VALIDATE_BOOLEAN
        )) {
            return back()->with(
                'error',
                'Direct Tally Journal Export is currently disabled.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Request Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'tally_accounting_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'date_override_reason' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Prepare Export Transaction
        |--------------------------------------------------------------------------
        */

        $prepared = DB::transaction(function () use (
            $journalEntry,
            $validated,
            $service,
            $builder
        ): array {

            $journal = JournalEntry::query()
                ->whereKey($journalEntry->id)
                ->lockForUpdate()
                ->firstOrFail();

                /*
|--------------------------------------------------------------------------
| Cross-Workflow Export Lock
|--------------------------------------------------------------------------
| Prevent simultaneous preparation of a GL Journal and
| Finance Voucher for the same accounting event.
|--------------------------------------------------------------------------
*/

TallyExportLock::acquire(
    $journal->source_type,
    $journal->source_id,
    $journal->source_event
);

            /*
            |--------------------------------------------------------------------------
            | Duplicate Export Protection
            |--------------------------------------------------------------------------
            */

            $existingExport = TallyExport::query()
                ->where('journal_entry_id', $journal->id)
                ->exists();

            if ($existingExport) {
                return [
                    'ok' => false,
                    'message' =>
                        'This journal already has a Tally export record. '
                        .'Reconciliation is required before another export.',
                ];
            }

            // Enforce Sales-before-Receipt dependency inside
            // the locked export transaction.
            $dependencyError = app(
                \App\Services\Tally\TallyJournalExportPreflightService::class
            )->failure($journal);

            if ($dependencyError !== null) {
                return [
                    'ok' => false,
                    'message' => $dependencyError,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Journal Eligibility Validation
            |--------------------------------------------------------------------------
            */

            $eligibility = $service->checkEligibility(
                $journal
            );

            if (! $eligibility['eligible']) {
                return [
                    'ok' => false,
                    'message' => implode(
                        ' ',
                        $eligibility['errors']
                    ),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Accounting Date
            |--------------------------------------------------------------------------
            */

            $originalDate = $journal
                ->journal_date
                ->format('Y-m-d');

            $tallyDate = $validated[
                'tally_accounting_date'
            ];

            $reason = trim(
                (string) (
                    $validated['date_override_reason']
                    ?? ''
                )
            );

            $dateOverridden = $tallyDate !== $originalDate;

            if (
                $dateOverridden
                && $reason === ''
            ) {
                return [
                    'ok' => false,
                    'message' =>
                        'A reason is required when the Tally '
                        .'accounting date differs from the ERP date.',
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Educational Edition Date Override
            |--------------------------------------------------------------------------
            */

            if (
                $dateOverridden
                && ! app()->environment(
                    'local',
                    'testing'
                )
            ) {
                return [
                    'ok' => false,
                    'message' =>
                        'Accounting date overrides are restricted '
                        .'to the local testing environment.',
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Stable Remote Identity
            |--------------------------------------------------------------------------
            */

            $remoteId = Str::uuid()->toString();

            /*
            |--------------------------------------------------------------------------
            | Generate Tally XML
            |--------------------------------------------------------------------------
            */

            $xml = $builder->build(
                $journal,
                $remoteId,
                $dateOverridden
                    ? $tallyDate
                    : null
            );

            /*
            |--------------------------------------------------------------------------
            | Create Persistent Export Record
            |--------------------------------------------------------------------------
            */

            $exportReference = 'TALLY-'.Str::upper(
                Str::uuid()->toString()
            );

            $export = TallyExport::create([
                'finance_voucher_id' => null,
                'journal_entry_id' => $journal->id,

                'status' => 'sending',

                'export_reference' => $exportReference,
                'remote_id' => $remoteId,

                'tally_voucher_id' => null,

                'tally_accounting_date' => $tallyDate,

                'date_override_reason' =>
                    $dateOverridden ? $reason : null,

                'exported_by' => auth()->id(),

                'exported_at' => null,
                'confirmed_at' => null,

                'tally_response' => null,
                'error_message' => null,

                'remarks' =>
                    'Manual GL Journal transmission initiated.',
            ]);

            return [
                'ok' => true,
                'journal_no' => $journal->journal_no,
                'export_id' => $export->id,
                'xml' => $xml,
            ];
        });

        /*
        |--------------------------------------------------------------------------
        | Handle Preparation Failure
        |--------------------------------------------------------------------------
        */

        if (! $prepared['ok']) {
            return back()->with(
                'error',
                $prepared['message']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Transmit XML to TallyPrime
        |--------------------------------------------------------------------------
        |
        | HTTP transmission occurs outside the database transaction.
        |--------------------------------------------------------------------------
        */

        try {
            $responseXml = $client->postXml(
                $prepared['xml']
            );

        } catch (Throwable $exception) {

            TallyExport::query()
                ->whereKey($prepared['export_id'])
                ->update([
                    'status' => 'unknown',

                    'error_message' =>
                        'Transmission outcome uncertain: '
                        .$exception->getMessage(),

                    'remarks' =>
                        'Check TallyPrime before attempting any retry.',
                ]);

            return back()->with(
                'error',
                'Tally transmission outcome is uncertain. '
                .'Reconcile the journal before retrying.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Parse Tally Response
        |--------------------------------------------------------------------------
        */

        try {

            $parsed = $parser->parse(
                $responseXml
            );

            /*
            |--------------------------------------------------------------------------
            | Validate Response XML
            |--------------------------------------------------------------------------
            */

            if (
                trim($responseXml) === ''
                || simplexml_load_string(
                    $responseXml,
                    \SimpleXMLElement::class,
                    LIBXML_NONET
                ) === false
            ) {
                throw new \RuntimeException(
                    'TallyPrime returned empty or invalid XML.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Confirm Successful Import
            |--------------------------------------------------------------------------
            */

            if (
    $parsed['success']
    && $parsed['created'] === 1
    && $parsed['altered'] === 0
    && $parsed['ignored'] === 0
    && $parsed['errors'] === 0
    && $parsed['cancelled'] === 0
) {

                TallyExport::query()
                    ->whereKey($prepared['export_id'])
                    ->update([
                        'status' => 'confirmed',

                        'tally_voucher_id' =>
                            $parsed['last_voucher_id'],

                        'tally_response' =>
                            $parsed['raw'],

                        'exported_at' => now(),
                        'confirmed_at' => now(),

                        'error_message' => null,

                        'remarks' =>
                            'Direct GL Journal import confirmed.',
                    ]);

                return back()->with(
                    'success',
                    'Journal '
                    .$prepared['journal_no']
                    .' was successfully imported into TallyPrime.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Unconfirmed Tally Response
            |--------------------------------------------------------------------------
            |
            | Do not assume that a failed response means
            | the voucher was never created.
            |--------------------------------------------------------------------------
            */

            TallyExport::query()
                ->whereKey($prepared['export_id'])
                ->update([
                    'status' => 'unknown',

                    'tally_response' =>
                        $parsed['raw'],

                    'error_message' =>
                        $parsed['message']
                        ?? 'TallyPrime did not confirm the import.',

                    'remarks' =>
                        'Reconciliation required before retry.',
                ]);

            return back()->with(
                'error',
                'TallyPrime did not confirm the journal import. '
                .'Reconciliation is required.'
            );

        } catch (Throwable $exception) {

            /*
            |--------------------------------------------------------------------------
            | Uncertain Response
            |--------------------------------------------------------------------------
            */

            TallyExport::query()
                ->whereKey($prepared['export_id'])
                ->update([
                    'status' => 'unknown',

                    'tally_response' => $responseXml,

                    'error_message' =>
                        $exception->getMessage(),

                    'remarks' =>
                        'Uncertain Tally response. '
                        .'Manual reconciliation required.',
                ]);

            return back()->with(
                'error',
                'Unable to confirm the Tally import result. '
                .'Check TallyPrime before retrying.'
            );
        }
    }

    /**
     * Send up to 20 selected journals using the existing individual
     * transmission workflow. No automatic retries.
     */
    public function sendSelected(
        Request $request,
        TallyJournalExportService $service,
        TallyVoucherXmlRouter $builder,
        TallyHttpClient $client,
        TallyResponseParser $parser
    ): RedirectResponse {
        if (! filter_var(
            config('tally.journal_export_enabled', false),
            FILTER_VALIDATE_BOOLEAN
        )) {
            return back()->with('error', 'Tally export is disabled.');
        }

        $validated = $request->validate([
            'journal_ids' => ['required', 'array', 'min:1', 'max:20'],
            'journal_ids.*' => [
                'required', 'integer', 'distinct',
                'exists:journal_entries,id',
            ],
        ]);

        $results = [];
        $stopped = false;

        foreach ($validated['journal_ids'] as $id) {
            $journal = JournalEntry::findOrFail($id);
            $number = $journal->journal_no;

            // Never retry a journal with any existing export record.
            if (
                $journal->status !== 'posted'
                || TallyExport::where('journal_entry_id', $id)->exists()
            ) {
                $results[] = [
                    'journal' => $number,
                    'status' => 'skipped',
                    'message' => 'Not eligible or already exported.',
                ];
                continue;
            }

            if (! $journal->journal_date) {
                $results[] = [
                    'journal' => $number,
                    'status' => 'preparation_failed',
                    'message' => 'Accounting date missing.',
                ];
                continue;
            }

            try {
                $dependencyError = app(
                    \App\Services\Tally\TallyJournalExportPreflightService::class
                )->failure($journal);

                if ($dependencyError !== null) {
                    $results[] = [
                        'journal' => $number,
                        'status' => 'skipped',
                        'message' => $dependencyError,
                    ];
                    continue;
                }

                $eligibility = $service->checkEligibility($journal);

                if (! ($eligibility['eligible'] ?? false)) {
                    $results[] = [
                        'journal' => $number,
                        'status' => 'skipped',
                        'message' => 'Journal eligibility check failed.',
                    ];
                    continue;
                }

                // Individual journal dates are preserved.
                $singleRequest = Request::create(
                    $request->url(),
                    'POST',
                    [
                        'tally_accounting_date' =>
                            $journal->journal_date->format('Y-m-d'),
                    ]
                );

                // Use the original individual transmission method.
                $this->send(
                    $singleRequest,
                    $journal,
                    $service,
                    $builder,
                    $client,
                    $parser
                );
            } catch (Throwable $exception) {
                report($exception);

                $export = TallyExport::where(
                    'journal_entry_id', $id
                )->first();

                if (! $export) {
                    $results[] = [
                        'journal' => $number,
                        'status' => 'preparation_failed',
                        'message' => 'Preparation failed; review server log.',
                    ];
                    continue;
                }

                // Exception after export preparation:
                // do not assume transmission did not occur.
                $results[] = [
                    'journal' => $number,
                    'status' => 'needs_review',
                    'message' => 'Export outcome uncertain. Stop batch.',
                ];

                $stopped = true;
                break;
            }

            $export = TallyExport::where(
                'journal_entry_id', $id
            )->first();

            if (! $export) {
                $results[] = [
                    'journal' => $number,
                    'status' => 'preparation_failed',
                    'message' => 'No export created; review validation.',
                ];
                continue;
            }

            if ($export->status === 'confirmed') {
                $results[] = [
                    'journal' => $number,
                    'status' => 'confirmed',
                    'message' => 'Tally import confirmed.',
                ];
                continue;
            }

            // Any non-confirmed export requires investigation.
            // Never automatically retry or continue transmitting.
            $results[] = [
                'journal' => $number,
                'status' => 'needs_review',
                'message' => 'Tally outcome not confirmed. Stop batch.',
            ];

            $stopped = true;
            break;
        }

        // Replace individual send flash messages with batch results.
        session()->forget(['success', 'error']);

        return back()
            ->with('tally_bulk_results', $results)
            ->with('tally_bulk_stopped', $stopped);
    }


    public function sendGuarded(
        Request $request,
        JournalEntry $journalEntry,
        TallyJournalExportService $service,
        TallyVoucherXmlRouter $builder,
        TallyHttpClient $client,
        TallyResponseParser $parser
    ): RedirectResponse {
        try {
            $dependencyError = app(
                \App\Services\Tally\TallyJournalExportPreflightService::class
            )->failure($journalEntry);

            if ($dependencyError !== null) {
                return back()->with('error', $dependencyError);
            }

            return $this->send(
                $request,
                $journalEntry,
                $service,
                $builder,
                $client,
                $parser
            );
        } catch (Throwable $exception) {
            report($exception);

            // Never presume a previously prepared export
            // was not transmitted.
            $export = TallyExport::query()
                ->where('journal_entry_id', $journalEntry->id)
                ->first();

            if ($export !== null) {
                return back()->with(
                    'error',
                    'Tally export failed or its outcome is uncertain. '
                    .'Export status: '.$export->status.'. '
                    .'Review the export record and reconcile '
                    .'with Tally before taking further action.'
                );
            }

            $knownErrors = [
                'Export the referenced IPD Sales voucher first.',
                'Referenced IPD final Sales journal was not found.',
                'Advance application has no final invoice reference.',
                'Final bill or patient reference is invalid.',
                'Missing journal lines.',
                'Missing Tally GL mapping.',
                'Invalid debit/credit line.',
                'Unsupported OPD Sales GL structure.',
                'Invalid Patient Receivables line.',
                'Invalid monetary amount.',
            ];

            if (in_array($exception->getMessage(), $knownErrors, true)) {
                return back()->with(
                    'error',
                    'Tally export validation failed: '
                    .$exception->getMessage()
                );
            }

            return back()->with(
                'error',
                'Tally export preparation failed. '
                .'No export record was found. '
                .'Check the application log before retrying.'
            );
        }
    }

}
