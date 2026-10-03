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
            | Prevent two simultaneous requests from exporting the same voucher.
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
            | Once an export record exists, normal Download XML is blocked.
            | Re-export should be handled later through a separate explicit action.
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
            | finance_voucher_id is unique in tally_exports, providing a second
            | database-level safeguard against duplicate export records.
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

        /*
        |--------------------------------------------------------------------------
        | Failed Export
        |--------------------------------------------------------------------------
        */

        if (! $result['ok']) {
            return redirect()
                ->route('finance.tally.exports.index')
                ->with(
                    'error',
                    implode(' ', $result['errors'])
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Download XML
        |--------------------------------------------------------------------------
        */

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
            '/[^A-Za-z0-9_-]+/',
            '-',
            $voucher->voucher_no
        );

        return 'tally-'.$voucherNumber.'.xml';
    }
}
