<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceVoucher;
use App\Models\TallyExport;
use App\Services\Tally\TallyExportService;
use App\Services\Tally\TallyXmlBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

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
     *
     * A normal export is allowed only once.
     *
     * "exported" means the ERP successfully generated the XML file.
     * It does not mean that Tally has confirmed the import.
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
            /*
            |--------------------------------------------------------------------------
            | Lock Voucher
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | Duplicate Export Protection
            |--------------------------------------------------------------------------
            */

            if ($voucher->tallyExport !== null) {
                return [
                    'ok' => false,
                    'errors' => [
                        'Voucher '.$voucher->voucher_no
                        .' has already been exported to Tally.',
                    ],
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Export Eligibility
            |--------------------------------------------------------------------------
            */

            $eligibility = $tallyExportService
                ->checkEligibility($voucher);

            if (! $eligibility['eligible']) {
                return [
                    'ok' => false,
                    'errors' => $eligibility['errors'],
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Build XML
            |--------------------------------------------------------------------------
            */

            $xml = $tallyXmlBuilder->build($voucher);

            /*
            |--------------------------------------------------------------------------
            | Export Reference
            |--------------------------------------------------------------------------
            */

            $exportReference = 'TALLY-'.Str::upper(
                Str::uuid()->toString()
            );

            /*
            |--------------------------------------------------------------------------
            | Record Export
            |--------------------------------------------------------------------------
            */

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

            /*
            |--------------------------------------------------------------------------
            | Existing Export Required
            |--------------------------------------------------------------------------
            */

            if ($voucher->tallyExport === null) {
                return [
                    'ok' => false,
                    'errors' => [
                        'This voucher has never been exported. Use Download XML instead.',
                    ],
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Re-export Only Failed Vouchers
            |--------------------------------------------------------------------------
            */

            if ($voucher->tallyExport->status !== 'failed') {
                return [
                    'ok' => false,
                    'errors' => [
                        'Only vouchers marked as failed can be re-exported.',
                    ],
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Re-check Eligibility
            |--------------------------------------------------------------------------
            */

            $eligibility = $tallyExportService
                ->checkEligibility($voucher);

            if (! $eligibility['eligible']) {
                return [
                    'ok' => false,
                    'errors' => $eligibility['errors'],
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Build Fresh XML
            |--------------------------------------------------------------------------
            */

            $xml = $tallyXmlBuilder->build($voucher);

            /*
            |--------------------------------------------------------------------------
            | New Export Reference
            |--------------------------------------------------------------------------
            */

            $exportReference = 'TALLY-'.Str::upper(
                Str::uuid()->toString()
            );

            /*
            |--------------------------------------------------------------------------
            | Update Existing Export Record
            |--------------------------------------------------------------------------
            */

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
