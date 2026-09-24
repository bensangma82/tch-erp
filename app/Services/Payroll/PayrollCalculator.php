<?php

namespace App\Services\Payroll;

use App\Models\EmployeeSalaryStructure;
use App\Models\EmployeeSalaryStructureItem;
use RuntimeException;

class PayrollCalculator
{
    /**
     * Calculate all active salary components for one salary structure.
     *
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     gross_earnings: float,
     *     total_deductions: float,
     *     net_pay: float
     * }
     */
    public function calculate(EmployeeSalaryStructure $salaryStructure): array
    {
        $salaryStructure->loadMissing([
            'items.salaryComponent',
            'items.percentageOfComponent',
        ]);

        $items = $salaryStructure->items
            ->where('is_active', true)
            ->sortBy('sort_order')
            ->values();

        if ($items->isEmpty()) {
            throw new RuntimeException(
                'The salary structure does not contain any active salary components.'
            );
        }

        $calculatedAmounts = [];
        $calculatedItems = [];

        /*
        |--------------------------------------------------------------------------
        | Pass 1 - Fixed components
        |--------------------------------------------------------------------------
        |
        | Fixed components are calculated first so percentage-based components
        | such as PF = 12% of BASIC can use their calculated base amount.
        |
        */
        foreach ($items as $item) {
            if (!$item->salaryComponent) {
                throw new RuntimeException(
                    'A salary structure item is missing its salary component.'
                );
            }

            if (!$item->isFixed()) {
                continue;
            }

            $amount = $item->calculateAmount();

            $calculatedAmounts[$item->salary_component_id] = $amount;

            $calculatedItems[$item->id] = $this->makeCalculatedItem(
                $item,
                $amount,
                null
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Pass 2 - Percentage components
        |--------------------------------------------------------------------------
        |
        | Percentage components must reference another component. At present
        | the salary structure uses examples such as PF based on BASIC.
        |
        */
        foreach ($items as $item) {
            if (!$item->isPercentageBased()) {
                continue;
            }

            $baseComponentId = $item->percentage_of_component_id;

            if (!$baseComponentId) {
                throw new RuntimeException(
                    "Percentage component {$item->salaryComponent->code} does not have a base component."
                );
            }

            if (!array_key_exists($baseComponentId, $calculatedAmounts)) {
                throw new RuntimeException(
                    "Unable to calculate {$item->salaryComponent->code}: its base component has not been calculated."
                );
            }

            $baseAmount = (float) $calculatedAmounts[$baseComponentId];

            $amount = $item->calculateAmount($baseAmount);

            $calculatedAmounts[$item->salary_component_id] = $amount;

            $calculatedItems[$item->id] = $this->makeCalculatedItem(
                $item,
                $amount,
                $baseAmount
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Reject unsupported calculation types
        |--------------------------------------------------------------------------
        */
        foreach ($items as $item) {
            if (
                !$item->isFixed()
                && !$item->isPercentageBased()
            ) {
                throw new RuntimeException(
                    "Unsupported calculation type '{$item->calculation_type}' for {$item->salaryComponent->code}."
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Preserve salary structure ordering
        |--------------------------------------------------------------------------
        */
        $resultItems = [];

        foreach ($items as $item) {
            if (!isset($calculatedItems[$item->id])) {
                continue;
            }

            $resultItems[] = $calculatedItems[$item->id];
        }

        $grossEarnings = 0.00;
        $totalDeductions = 0.00;

        foreach ($resultItems as $resultItem) {
            if ($resultItem['type'] === 'earning') {
                $grossEarnings += $resultItem['amount'];
            }

            if ($resultItem['type'] === 'deduction') {
                $totalDeductions += $resultItem['amount'];
            }
        }

        $grossEarnings = round($grossEarnings, 2);
        $totalDeductions = round($totalDeductions, 2);
        $netPay = round($grossEarnings - $totalDeductions, 2);

        return [
            'items' => $resultItems,
            'gross_earnings' => $grossEarnings,
            'total_deductions' => $totalDeductions,
            'net_pay' => $netPay,
        ];
    }

    /**
     * Build a payroll-ready snapshot of one salary component.
     */
    private function makeCalculatedItem(
        EmployeeSalaryStructureItem $item,
        float $amount,
        ?float $baseAmount
    ): array {
        $salaryComponent = $item->salaryComponent;

        return [
            'salary_component_id' => $salaryComponent->id,

            'component_code' => $salaryComponent->code,
            'component_name' => $salaryComponent->name,
            'type' => $salaryComponent->type,

            'calculation_type' => $item->calculation_type,

            'base_amount' => $baseAmount !== null
                ? round($baseAmount, 2)
                : null,

            'percentage' => $item->isPercentageBased()
                ? (float) ($item->percentage ?? 0)
                : null,

            'amount' => round($amount, 2),

            'is_taxable' => (bool) $salaryComponent->is_taxable,
            'is_statutory' => (bool) $salaryComponent->is_statutory,
            'is_recurring' => (bool) $salaryComponent->is_recurring,

            'is_prorated' => false,
            'proration_factor' => null,

            'sort_order' => (int) $item->sort_order,

            'remarks' => $item->remarks,
        ];
    }
}