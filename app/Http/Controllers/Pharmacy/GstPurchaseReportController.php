<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\PharmacyGrnItem;
use App\Models\PharmacySupplier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GstPurchaseReportController extends Controller
{
    /**
     * Display GST Purchase Report.
     */
    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);

        $fromDate = $filters['from_date']
            ?? now()->startOfMonth()->toDateString();

        $toDate = $filters['to_date']
            ?? now()->endOfMonth()->toDateString();

        $supplierId = $filters['supplier_id'] ?? null;

        $items = $this->reportQuery(
            $fromDate,
            $toDate,
            $supplierId
        )
            ->orderBy('pharmacy_grns.supplier_invoice_date')
            ->orderBy('pharmacy_grns.grn_no')
            ->orderBy('pharmacy_grn_items.id')
            ->paginate(100)
            ->withQueryString();

        $suppliers = PharmacySupplier::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'gstin',
            ]);

        $totals = $this->reportQuery($fromDate, $toDate, $supplierId)
    ->select([])
    ->selectRaw(
                '
                COALESCE(SUM(pharmacy_grn_items.taxable_amount), 0) AS taxable_amount,
                COALESCE(SUM(pharmacy_grn_items.cgst_amount), 0) AS cgst_amount,
                COALESCE(SUM(pharmacy_grn_items.sgst_amount), 0) AS sgst_amount,
                COALESCE(SUM(pharmacy_grn_items.igst_amount), 0) AS igst_amount,
                COALESCE(SUM(pharmacy_grn_items.line_total), 0) AS line_total
                '
            )
            ->first();

        return view(
            'pharmacy.reports.gst-purchases.index',
            compact(
                'items',
                'suppliers',
                'totals',
                'fromDate',
                'toDate',
                'supplierId'
            )
        );
    }

    /**
     * Export GST Purchase Report as XLSX.
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);

        $fromDate = $filters['from_date']
            ?? now()->startOfMonth()->toDateString();

        $toDate = $filters['to_date']
            ?? now()->endOfMonth()->toDateString();

        $supplierId = $filters['supplier_id'] ?? null;

        $items = $this->reportQuery(
            $fromDate,
            $toDate,
            $supplierId
        )
            ->orderBy('pharmacy_grns.supplier_invoice_date')
            ->orderBy('pharmacy_grns.grn_no')
            ->orderBy('pharmacy_grn_items.id')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle('GST Purchases');

        /*
        |--------------------------------------------------------------------------
        | Report Header
        |--------------------------------------------------------------------------
        */

        $sheet->mergeCells('A1:T1');
        $sheet->setCellValue(
            'A1',
            'TURA CHRISTIAN HOSPITAL'
        );

        $sheet->mergeCells('A2:T2');
        $sheet->setCellValue(
            'A2',
            'Tura, Meghalaya'
        );

        $sheet->mergeCells('A3:T3');
        $sheet->setCellValue(
            'A3',
            'GST Submission Details on Medicine Purchases'
        );

        $sheet->mergeCells('A4:T4');
        $sheet->setCellValue(
            'A4',
            'Period: '
            . Carbon::parse($fromDate)->format('d M Y')
            . ' to '
            . Carbon::parse($toDate)->format('d M Y')
        );

        $sheet->getStyle('A1:T4')
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );

        $sheet->getStyle('A1')
            ->getFont()
            ->setBold(true)
            ->setSize(16);

        $sheet->getStyle('A3')
            ->getFont()
            ->setBold(true)
            ->setSize(13);

        /*
        |--------------------------------------------------------------------------
        | Column Headings
        |--------------------------------------------------------------------------
        */

        $headers = [
            'Sl. No.',
            'Invoice Date',
            'GRN No.',
            'Invoice No.',
            'Supplier',
            'Supplier GSTIN',
            'Medicine / Brand',
            'Batch No.',
            'Expiry Date',
            'Qty. Packs',
            'Bonus Packs',
            'Units / Pack',
            'Purchase Price',
            'MRP',
            'HSN/SAC',
            'GST %',
            'Taxable Amount',
            'CGST Amount',
            'SGST Amount',
            'IGST Amount',
        ];

        $headerRow = 6;

        foreach ($headers as $index => $header) {
            $column = $this->columnLetter(
                $index + 1
            );

            $sheet->setCellValue(
                $column . $headerRow,
                $header
            );
        }

        $sheet->getStyle(
            'A' . $headerRow . ':T' . $headerRow
        )
            ->getFont()
            ->setBold(true);

        $sheet->getStyle(
            'A' . $headerRow . ':T' . $headerRow
        )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            )
            ->setVertical(
                Alignment::VERTICAL_CENTER
            )
            ->setWrapText(true);

        $sheet->getStyle(
            'A' . $headerRow . ':T' . $headerRow
        )
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB('FFD9EAF7');

        /*
        |--------------------------------------------------------------------------
        | Data Rows
        |--------------------------------------------------------------------------
        */

        $row = $headerRow + 1;

        $totalTaxable = 0;
        $totalCgst = 0;
        $totalSgst = 0;
        $totalIgst = 0;

        foreach ($items as $index => $item) {
            $sheet->setCellValue(
                'A' . $row,
                $index + 1
            );

            $sheet->setCellValue(
                'B' . $row,
                $item->supplier_invoice_date
                    ? Carbon::parse(
                        $item->supplier_invoice_date
                    )->format('d-m-Y')
                    : ''
            );

            $sheet->setCellValue(
                'C' . $row,
                $item->grn_no
            );

            $sheet->setCellValue(
                'D' . $row,
                $item->supplier_invoice_no
            );

            $sheet->setCellValue(
                'E' . $row,
                $item->supplier_name
            );

            $sheet->setCellValue(
                'F' . $row,
                $item->supplier_gstin
            );

            $medicineName =
                $item->brand_name
                ?: $item->medicine_name;

            $sheet->setCellValue(
                'G' . $row,
                $medicineName
            );

            $sheet->setCellValue(
                'H' . $row,
                $item->batch_number
            );

            $sheet->setCellValue(
                'I' . $row,
                $item->expiry_date
                    ? Carbon::parse(
                        $item->expiry_date
                    )->format('m-Y')
                    : ''
            );

            $sheet->setCellValue(
                'J' . $row,
                (int) $item->purchase_qty
            );

            $sheet->setCellValue(
                'K' . $row,
                (int) $item->bonus_qty
            );

            $sheet->setCellValue(
                'L' . $row,
                (int) $item->units_per_pack
            );

            $sheet->setCellValue(
                'M' . $row,
                (float) $item->purchase_price
            );

            $sheet->setCellValue(
                'N' . $row,
                (float) ($item->mrp_per_pack ?? 0)
            );

            $sheet->setCellValue(
                'O' . $row,
                $item->hsn_code
            );

            $sheet->setCellValue(
                'P' . $row,
                (float) $item->gst_percent
            );

            $sheet->setCellValue(
                'Q' . $row,
                (float) $item->taxable_amount
            );

            $sheet->setCellValue(
                'R' . $row,
                (float) $item->cgst_amount
            );

            $sheet->setCellValue(
                'S' . $row,
                (float) $item->sgst_amount
            );

            $sheet->setCellValue(
                'T' . $row,
                (float) $item->igst_amount
            );

            $totalTaxable +=
                (float) $item->taxable_amount;

            $totalCgst +=
                (float) $item->cgst_amount;

            $totalSgst +=
                (float) $item->sgst_amount;

            $totalIgst +=
                (float) $item->igst_amount;

            $row++;
        }

        /*
        |--------------------------------------------------------------------------
        | Totals
        |--------------------------------------------------------------------------
        */

        $sheet->mergeCells(
            'A' . $row . ':P' . $row
        );

        $sheet->setCellValue(
            'A' . $row,
            'TOTAL'
        );

        $sheet->setCellValue(
            'Q' . $row,
            $totalTaxable
        );

        $sheet->setCellValue(
            'R' . $row,
            $totalCgst
        );

        $sheet->setCellValue(
            'S' . $row,
            $totalSgst
        );

        $sheet->setCellValue(
            'T' . $row,
            $totalIgst
        );

        $sheet->getStyle(
            'A' . $row . ':T' . $row
        )
            ->getFont()
            ->setBold(true);

        /*
        |--------------------------------------------------------------------------
        | Formatting
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle(
            'M7:T' . $row
        )
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');

        $sheet->getStyle(
            'A6:T' . $row
        )
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(
                Border::BORDER_THIN
            );

        $sheet->freezePane('A7');

        $sheet->setAutoFilter(
            'A6:T' . max(6, $row - 1)
        );

        foreach (
            range('A', 'T')
            as $column
        ) {
            $sheet
                ->getColumnDimension($column)
                ->setAutoSize(true);
        }

        $sheet
            ->getColumnDimension('G')
            ->setWidth(30);

        $fileName =
            'GST_Purchase_Report_'
            . Carbon::parse($fromDate)
                ->format('Ymd')
            . '_to_'
            . Carbon::parse($toDate)
                ->format('Ymd')
            . '.xlsx';

        return response()->streamDownload(
            function () use ($spreadsheet) {
                $writer = new Xlsx(
                    $spreadsheet
                );

                $writer->save('php://output');

                $spreadsheet
                    ->disconnectWorksheets();
            },
            $fileName,
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }

    /**
     * Base query used by both screen report and Excel export.
     */
    private function reportQuery(
        string $fromDate,
        string $toDate,
        ?int $supplierId = null
    ) {
        return PharmacyGrnItem::query()
            ->join(
                'pharmacy_grns',
                'pharmacy_grns.id',
                '=',
                'pharmacy_grn_items.pharmacy_grn_id'
            )
            ->leftJoin(
                'pharmacy_suppliers',
                'pharmacy_suppliers.id',
                '=',
                'pharmacy_grns.pharmacy_supplier_id'
            )
            ->leftJoin(
                'medicines',
                'medicines.id',
                '=',
                'pharmacy_grn_items.medicine_id'
            )
            ->where(
                'pharmacy_grns.status',
                'completed'
            )
            ->whereBetween(
                'pharmacy_grns.supplier_invoice_date',
                [
                    $fromDate,
                    $toDate,
                ]
            )
            ->when(
                $supplierId,
                fn ($query) =>
                    $query->where(
                        'pharmacy_grns.pharmacy_supplier_id',
                        $supplierId
                    )
            )
            ->select([
                'pharmacy_grn_items.id',
                'pharmacy_grn_items.medicine_name',
                'pharmacy_grn_items.brand_name',
                'pharmacy_grn_items.batch_number',
                'pharmacy_grn_items.expiry_date',
                'pharmacy_grn_items.purchase_qty',
                'pharmacy_grn_items.bonus_qty',
                'pharmacy_grn_items.units_per_pack',
                'pharmacy_grn_items.purchase_price',
                'pharmacy_grn_items.gst_percent',
                'pharmacy_grn_items.taxable_amount',
                'pharmacy_grn_items.cgst_amount',
                'pharmacy_grn_items.sgst_amount',
                'pharmacy_grn_items.igst_amount',
                'pharmacy_grn_items.line_total',

                'pharmacy_grns.grn_no',
                'pharmacy_grns.supplier_invoice_no',
                'pharmacy_grns.supplier_invoice_date',

                'pharmacy_suppliers.name as supplier_name',
                'pharmacy_suppliers.gstin as supplier_gstin',

                'medicines.hsn_code',
                'medicines.mrp_per_pack',
            ]);
    }

    /**
     * Validate report filters.
     */
    private function validatedFilters(
        Request $request
    ): array {
        return $request->validate([
            'from_date' => [
                'nullable',
                'date',
            ],

            'to_date' => [
                'nullable',
                'date',
                'after_or_equal:from_date',
            ],

            'supplier_id' => [
                'nullable',
                'integer',
                'exists:pharmacy_suppliers,id',
            ],
        ]);
    }

    /**
     * Convert a 1-based column number to Excel letters.
     */
    private function columnLetter(
        int $column
    ): string {
        $letter = '';

        while ($column > 0) {
            $column--;

            $letter =
                chr(
                    65 + ($column % 26)
                )
                . $letter;

            $column =
                intdiv(
                    $column,
                    26
                );
        }

        return $letter;
    }
}