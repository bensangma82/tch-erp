<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceVoucher;
use App\Models\TallyExport;
use App\Services\Tally\TallyExportService;
use App\Services\Tally\TallyHttpClient;
use App\Services\Tally\TallyResponseParser;
use App\Services\Tally\TallyXmlBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TallyExportController extends Controller
{
    /**
     * Display posted Finance vouchers and their Tally export readiness.
     */
    public function index(
        Request $request,
        TallyExportService $tallyExportService
    ): View {
        $query = FinanceVoucher::query()
            ->where('status', 'posted')
            ->with([
                'financeHead.tallyMapping',
                'financeAccount.tallyMapping',
                'destinationAccount.tallyMapping',
                'tallyExport',
            ]);

        if ($request->filled('voucher_type')) {
            $voucherType = $request
                ->string('voucher_type')
                ->toString();

            if (in_array(
                $voucherType,
                ['receipt', 'payment', 'transfer'],
                true
            )) {
                $query->where(
                    'voucher_type',
                    $voucherType
                );
            }
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

        $eligibility = [];

        foreach ($vouchers as $voucher) {
            $eligibility[$voucher->id] =
                $tallyExportService->checkEligibility(
                    $voucher
                );
        }

        return view(
            'finance.tally.exports.index',
            [
                'vouchers' => $vouchers,
                'eligibility' => $eligibility,
            ]
        );
    }

    /**
     * Generate and download Tally XML for one posted Finance voucher.
     */
    public function download(
        FinanceVoucher $financeVoucher,
        TallyExportService $tallyExportService,
        TallyXmlBuilder $tallyXmlBuilder
    ): Response|RedirectResponse {
        $result = DB::transaction(function () use (
            $financeVoucher,
            $tallyExportService,
            $tallyXmlBuilder
        ): array {
            $voucher = FinanceVoucher::query()
                ->whereKey($financeVoucher->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $voucher->load([
                'financeHead.tallyMapping',
                'financeAccount.tallyMapping',
                'destinationAccount.tallyMapping',
                'tallyExport',
            ]);

            if ($voucher->tallyExport !== null) {
                return [
                    'ok' => false,
                    'errors' => [
                        'Voucher '.$voucher->voucher_no
                        .' has already been exported to Tally.',
                    ],
                ];
            }

            $eligibility = $tallyExportService
                ->checkEligibility($voucher);

            if (! $eligibility['eligible']) {
                return [
                    'ok' => false,
                    'errors' => $eligibility['errors'],
                ];
            }

            $xml = $tallyXmlBuilder->build($voucher);

            $exportReference = 'TALLY-'.Str::upper(
                Str::uuid()->toString()
            );

            TallyExport::create([
                'finance_voucher_id' => $voucher->id,
                'status' => 'exported',
                'export_reference' => $exportReference,
                'exported_at' => now(),
                'exported_by' => auth()->id(),
                'confirmed_at' => null,
                'error_message' => null,
                'remarks' => null,
            ]);

            return [
                'ok' => true,
                'xml' => $xml,
                'filename' => $this->filename($voucher),
            ];
        });

        if (! $result['ok']) {
            return redirect()
                ->route('finance.tally.exports.index')
                ->with(
                    'error',
                    implode(' ', $result['errors'])
                );
        }

        return response(
            $result['xml'],
            200,
            [
                'Content-Type' => 'application/xml; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$result['filename'].'"',
            ]
        );
    }

    /**
     * Send one Finance voucher directly to TallyPrime.
     */
    public function send(
        FinanceVoucher $financeVoucher,
        TallyExportService $tallyExportService,
        TallyXmlBuilder $tallyXmlBuilder,
        TallyHttpClient $tallyHttpClient,
        TallyResponseParser $tallyResponseParser
    ): RedirectResponse {
        $prepared = DB::transaction(function () use (
            $financeVoucher,
            $tallyExportService,
            $tallyXmlBuilder
        ): array {
            $voucher = FinanceVoucher::query()
                ->whereKey($financeVoucher->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $voucher->load([
                'financeHead.tallyMapping',
                'financeAccount.tallyMapping',
                'destinationAccount.tallyMapping',
                'tallyExport',
            ]);

            if ($voucher->tallyExport !== null) {
                return [
                    'ok' => false,
                    'message' => 'Voucher '.$voucher->voucher_no
                        .' already has a Tally export record.',
                ];
            }

            $eligibility = $tallyExportService
                ->checkEligibility($voucher);

            if (! $eligibility['eligible']) {
                return [
                    'ok' => false,
                    'message' => implode(
                        ' ',
                        $eligibility['errors']
                    ),
                ];
            }

            $xml = $tallyXmlBuilder->build($voucher);

            $exportReference = 'TALLY-'.Str::upper(
                Str::uuid()->toString()
            );

            $export = TallyExport::create([
                'finance_voucher_id' => $voucher->id,
                'status' => 'sending',
                'export_reference' => $exportReference,
                'exported_at' => null,
                'exported_by' => auth()->id(),
                'confirmed_at' => null,
                'error_message' => null,
                'remarks' => 'Direct Tally send initiated.',
            ]);

            return [
                'ok' => true,
                'voucher_no' => $voucher->voucher_no,
                'xml' => $xml,
                'export_id' => $export->id,
            ];
        });

        if (! $prepared['ok']) {
            return redirect()
                ->route('finance.tally.exports.index')
                ->with(
                    'error',
                    $prepared['message']
                );
        }

        try {
            $responseXml = $tallyHttpClient->postXml(
                $prepared['xml']
            );
        } catch (Throwable $exception) {
            TallyExport::query()
                ->whereKey($prepared['export_id'])
                ->update([
                    'status' => 'unknown',
                    'error_message' => 'Tally posting result is uncertain: '
                        .$exception->getMessage(),
                    'remarks' => 'Direct send attempted. Check Tally manually before any retry.',
                ]);

            return redirect()
                ->route('finance.tally.exports.index')
                ->with(
                    'error',
                    'The connection to TallyPrime became uncertain while sending '
                    .$prepared['voucher_no']
                    .'. Check Tally before attempting any retry.'
                );
        }

        $parsed = $tallyResponseParser->parse(
            $responseXml
        );

        if ($parsed['success']) {
            $remarks = 'Direct Tally import successful.'
                .' Created: '.$parsed['created']
                .'; Altered: '.$parsed['altered'];

            if ($parsed['last_voucher_id'] !== null) {
                $remarks .= '; Tally Voucher ID: '
                    .$parsed['last_voucher_id'];
            }

            TallyExport::query()
                ->whereKey($prepared['export_id'])
                ->update([
                    'status' => 'confirmed',
                    'exported_at' => now(),
                    'confirmed_at' => now(),
                    'error_message' => null,
                    'remarks' => $remarks,
                ]);

            return redirect()
                ->route('finance.tally.exports.index')
                ->with(
                    'success',
                    'Voucher '.$prepared['voucher_no']
                    .' was sent directly to Tally and confirmed successfully.'
                );
        }

        TallyExport::query()
            ->whereKey($prepared['export_id'])
            ->update([
                'status' => 'failed',
                'exported_at' => now(),
                'confirmed_at' => null,
                'error_message' => $parsed['message']
                    ?? 'TallyPrime rejected the voucher.',
                'remarks' => 'Direct Tally import failed.'
                    .' Created: '.$parsed['created']
                    .'; Altered: '.$parsed['altered']
                    .'; Ignored: '.$parsed['ignored']
                    .'; Errors: '.$parsed['errors'],
            ]);

        return redirect()
            ->route('finance.tally.exports.index')
            ->with(
                'error',
                'TallyPrime rejected voucher '
                .$prepared['voucher_no']
                .': '
                .($parsed['message'] ?? 'Unknown Tally error.')
            );
    }

    /**
     * Confirm that an exported voucher was successfully imported into Tally.
     */
    public function confirm(
        FinanceVoucher $financeVoucher
    ): RedirectResponse {
        $result = DB::transaction(function () use (
            $financeVoucher
        ): array {
            $voucher = FinanceVoucher::query()
                ->whereKey($financeVoucher->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $voucher->load('tallyExport');

            if ($voucher->tallyExport === null) {
                return [
                    'ok' => false,
                    'message' => 'This voucher has not been exported to Tally yet.',
                ];
            }

            if ($voucher->tallyExport->status === 'confirmed') {
                return [
                    'ok' => false,
                    'message' => 'This voucher has already been confirmed as imported into Tally.',
                ];
            }

            if ($voucher->tallyExport->status !== 'exported') {
                return [
                    'ok' => false,
                    'message' => 'Only exported vouchers can be confirmed as imported.',
                ];
            }

            $voucher->tallyExport->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'error_message' => null,
            ]);

            return [
                'ok' => true,
                'message' => 'Voucher '.$voucher->voucher_no
                    .' confirmed as imported into Tally.',
            ];
        });

        return redirect()
            ->route('finance.tally.exports.index')
            ->with(
                $result['ok'] ? 'success' : 'error',
                $result['message']
            );
    }

    /**
     * Mark an exported voucher as rejected or failed in Tally.
     */
    public function markFailed(
        Request $request,
        FinanceVoucher $financeVoucher
    ): RedirectResponse {
        $validated = $request->validate([
            'error_message' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        $result = DB::transaction(function () use (
            $financeVoucher,
            $validated
        ): array {
            $voucher = FinanceVoucher::query()
                ->whereKey($financeVoucher->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $voucher->load('tallyExport');

            if ($voucher->tallyExport === null) {
                return [
                    'ok' => false,
                    'message' => 'This voucher has not been exported to Tally yet.',
                ];
            }

            if ($voucher->tallyExport->status === 'confirmed') {
                return [
                    'ok' => false,
                    'message' => 'A confirmed Tally import cannot be marked as failed.',
                ];
            }

            if ($voucher->tallyExport->status === 'failed') {
                return [
                    'ok' => false,
                    'message' => 'This voucher is already marked as failed.',
                ];
            }

            if ($voucher->tallyExport->status !== 'exported') {
                return [
                    'ok' => false,
                    'message' => 'Only exported vouchers can be marked as failed.',
                ];
            }

            $voucher->tallyExport->update([
                'status' => 'failed',
                'error_message' => $validated['error_message'],
                'confirmed_at' => null,
            ]);

            return [
                'ok' => true,
                'message' => 'Voucher '.$voucher->voucher_no
                    .' marked as failed. It can now be re-exported.',
            ];
        });

        return redirect()
            ->route('finance.tally.exports.index')
            ->with(
                $result['ok'] ? 'success' : 'error',
                $result['message']
            );
    }

    /**
     * Re-export a voucher only after its previous Tally import failed.
     */
    public function reExport(
        FinanceVoucher $financeVoucher,
        TallyExportService $tallyExportService,
        TallyXmlBuilder $tallyXmlBuilder
    ): Response|RedirectResponse {
        $result = DB::transaction(function () use (
            $financeVoucher,
            $tallyExportService,
            $tallyXmlBuilder
        ): array {
            $voucher = FinanceVoucher::query()
                ->whereKey($financeVoucher->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $voucher->load([
                'financeHead.tallyMapping',
                'financeAccount.tallyMapping',
                'destinationAccount.tallyMapping',
                'tallyExport',
            ]);

            if ($voucher->tallyExport === null) {
                return [
                    'ok' => false,
                    'errors' => [
                        'This voucher has never been exported. Use Download XML instead.',
                    ],
                ];
            }

            if ($voucher->tallyExport->status !== 'failed') {
                return [
                    'ok' => false,
                    'errors' => [
                        'Only vouchers marked as failed can be re-exported.',
                    ],
                ];
            }

            $eligibility = $tallyExportService
                ->checkEligibility($voucher);

            if (! $eligibility['eligible']) {
                return [
                    'ok' => false,
                    'errors' => $eligibility['errors'],
                ];
            }

            $xml = $tallyXmlBuilder->build($voucher);

            $exportReference = 'TALLY-'.Str::upper(
                Str::uuid()->toString()
            );

            $voucher->tallyExport->update([
                'status' => 'exported',
                'export_reference' => $exportReference,
                'exported_at' => now(),
                'exported_by' => auth()->id(),
                'confirmed_at' => null,
                'error_message' => null,
            ]);

            return [
                'ok' => true,
                'xml' => $xml,
                'filename' => $this->filename($voucher),
            ];
        });

        if (! $result['ok']) {
            return redirect()
                ->route('finance.tally.exports.index')
                ->with(
                    'error',
                    implode(' ', $result['errors'])
                );
        }

        return response(
            $result['xml'],
            200,
            [
                'Content-Type' => 'application/xml; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$result['filename'].'"',
            ]
        );
    }

    /**
     * Create a filesystem-safe XML filename.
     */
    private function filename(
        FinanceVoucher $voucher
    ): string {
        $voucherNumber = preg_replace(
            '/[^A-Za-z0-9\_-]+/',
            '-',
            $voucher->voucher_no
        );

        return 'tally-'.$voucherNumber.'.xml';
    }
}
