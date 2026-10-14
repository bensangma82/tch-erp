<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Models\TallyExport;
use App\Services\Tally\TallyHttpClient;
use App\Services\Tally\TallyExportLock;
use App\Services\Tally\TallyJournalExportService;
use App\Services\Tally\TallyJournalReconciliationService;
use App\Services\Tally\TallyJournalXmlBuilder;
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
        $journals = JournalEntry::query()
            ->where('status', 'posted')
            ->orderByDesc('journal_date')
            ->orderByDesc('id')
            ->paginate(25);

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
        TallyJournalXmlBuilder $builder
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
        TallyJournalXmlBuilder $builder,
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
}
