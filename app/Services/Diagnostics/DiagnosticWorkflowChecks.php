<?php

namespace App\Services\Diagnostics;

use App\Models\FinanceHead;
use App\Models\FinanceVoucher;
use App\Models\PayrollRun;
use App\Services\Tally\TallyExportService;
use Illuminate\Database\QueryException;

final class DiagnosticWorkflowChecks
{
    private const TALLY_SCAN_LIMIT = 30;

    public function __construct(private readonly TallyExportService $tallyExport) {}

    public function scan(): array
    {
        $findings = [];
        $unavailable = [];

        $checks = [
            'Break-even classification' => $this->breakEven(...),
            'Tally export readiness' => $this->tallyMappings(...),
            'Payroll runs' => $this->payroll(...),
            'Payroll finance vouchers' => $this->payrollVouchers(...),
        ];

        foreach ($checks as $name => $check) {
            try {
                array_push($findings, ...$check());
            } catch (QueryException) {
                $unavailable[] = $name;
            }
        }

        return ['findings' => $findings, 'unavailable' => $unavailable];
    }

    private function breakEven(): array
    {
        $count = FinanceHead::query()->missingBreakEvenClassification()->count();

        if ($count === 0) {
            return [];
        }

        return [[
            'title' => 'Break-even expense classification',
            'detail' => $count.' expense head(s) still need classification.',
            'label' => 'Classify expense heads',
            'url' => route('finance.master.index', ['classify_break_even' => 1]).'#break-even-expense-heads',
        ]];
    }

    private function tallyMappings(): array
    {
        $vouchers = FinanceVoucher::query()
            ->where('status', 'posted')
            ->where('origin', '<>', 'tally_import')
            ->whereIn('voucher_type', ['receipt', 'payment', 'transfer'])
            ->whereDoesntHave('tallyExport')
            ->with(['financeHead.tallyMapping', 'financeAccount.tallyMapping', 'destinationAccount.tallyMapping', 'tallyExport'])
            ->orderByDesc('voucher_date')
            ->orderByDesc('id')
            ->limit(self::TALLY_SCAN_LIMIT)
            ->get();

        $mappings = [];
        foreach ($vouchers as $voucher) {
            if ($voucher->source_type === 'hospital_expense_payment' && $voucher->source_event === 'supplier_payment') {
                continue;
            }

            $check = $this->tallyExport->checkEligibility($voucher);
            foreach ($check['solutions'] ?? [] as $index => $solution) {
                if ($solution['route'] !== 'finance.tally.index') {
                    continue;
                }

                $fragment = $solution['fragment'];
                $mappings[$fragment] ??= [
                    'title' => 'Tally ledger mapping',
                    'detail' => $check['errors'][$index],
                    'label' => $solution['label'],
                    'url' => route($solution['route'], $solution['parameters']).'#'.$fragment,
                ];
            }
        }

        if (count($mappings) <= 8) {
            return array_values($mappings);
        }

        return [...array_slice(array_values($mappings), 0, 8), [
            'title' => 'More Tally mappings',
            'detail' => (count($mappings) - 8).' more mapping(s) need attention in the vouchers checked.',
            'label' => 'Review blocked vouchers',
            'url' => route('finance.tally.exports.index'),
        ]];
    }

    private function payroll(): array
    {
        return PayrollRun::query()
            ->where('status', 'calculated')
            ->whereDoesntHave('entries')
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'payroll_no', 'period_start'])
            ->map(fn (PayrollRun $run) => [
                'title' => 'Empty payroll run',
                'detail' => $run->payroll_no.' has no employee entries for '.$run->period_start->format('F Y').'. Check salary structures before recalculating.',
                'label' => 'Open payroll run',
                'url' => route('admin.hr.payroll.runs.show', $run),
            ])
            ->all();
    }

    private function payrollVouchers(): array
    {
        return FinanceVoucher::query()
            ->where('origin', 'erp_payroll')
            ->where('voucher_type', 'payment')
            ->whereNotNull('payroll_run_id')
            ->where('status', 'draft')
            ->orderByDesc('voucher_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'voucher_no'])
            ->map(fn (FinanceVoucher $voucher) => [
                'title' => 'Payroll voucher awaiting posting',
                'detail' => $voucher->voucher_no.' is ready for Finance review before posting.',
                'label' => 'Review payroll voucher',
                'url' => route('finance.vouchers.show', $voucher),
            ])
            ->all();
    }
}
