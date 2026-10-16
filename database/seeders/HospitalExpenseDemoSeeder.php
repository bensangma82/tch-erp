<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\FinanceHead;
use App\Models\GeneralLedgerAccount;
use App\Models\HospitalExpense;
use App\Services\Finance\HospitalExpenseNumberService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HospitalExpenseDemoSeeder extends Seeder
{
    public function run(): void
    {
        $date = '2026-10-01';

        $expenses = [
            [
                'invoice' => 'TEST-EXP-002',
                'description' => 'TEST DATA — Laboratory reagents consumed',
                'supplier' => 'TEST LAB SUPPLIER',
                'classification' => 'clinical',
                'head' => 'EXP-LAB',
                'gl' => '5103',
                'amount' => 2500,
                'allocations' => [
                    'Laboratory' => 100,
                ],
            ],
            [
                'invoice' => 'TEST-EXP-003',
                'description' => 'TEST DATA — Electricity expenses',
                'supplier' => 'TEST ELECTRICITY SUPPLIER',
                'classification' => 'shared',
                'head' => 'EXP-ELECTRICITY',
                'gl' => '6102',
                'amount' => 5000,
                'allocations' => [
                    'Intensive Care Unit' => 40,
                    'Dialysis' => 30,
                    'Laboratory' => 20,
                    'Administration' => 10,
                ],
            ],
            [
                'invoice' => 'TEST-EXP-004',
                'description' => 'TEST DATA — Office stationery',
                'supplier' => 'TEST OFFICE SUPPLIER',
                'classification' => 'non_clinical',
                'head' => 'EXP-OFFICE',
                'gl' => '6107',
                'amount' => 800,
                'allocations' => [
                    'Administration' => 100,
                ],
            ],
            [
                'invoice' => 'TEST-EXP-005',
                'description' => 'TEST DATA — Building maintenance',
                'supplier' => 'TEST MAINTENANCE SUPPLIER',
                'classification' => 'non_clinical',
                'head' => 'EXP-MAINTENANCE',
                'gl' => '6104',
                'amount' => 3000,
                'allocations' => [
                    'Administration' => 100,
                ],
            ],
        ];

        /*
        |--------------------------------------------------------------------------
        | Preflight: Validate All References
        |--------------------------------------------------------------------------
        |
        | Resolve dependencies before creating any expense.
        |
        */

        $prepared = [];

        foreach ($expenses as $row) {
            $head = FinanceHead::query()
                ->where('code', $row['head'])
                ->where('head_type', 'expense')
                ->where('is_active', true)
                ->first();

            $gl = GeneralLedgerAccount::query()
                ->where('account_code', $row['gl'])
                ->where('account_type', 'expense')
                ->where('is_active', true)
                ->where('allow_posting', true)
                ->first();

            if (! $head || ! $gl) {
                throw new RuntimeException(
                    'Missing or inactive head/GL: '
                    . $row['head'] . ' / ' . $row['gl']
                );
            }

            if (
                $gl->finance_head_id !== null
                && (int) $gl->finance_head_id !== (int) $head->id
            ) {
                throw new RuntimeException(
                    'Finance Head and GL mismatch: ' . $row['gl']
                );
            }

            $allocations = [];

            foreach ($row['allocations'] as $name => $percentage) {
                $department = Department::query()
                    ->where('name', $name)
                    ->where('is_active', true)
                    ->first();

                if (! $department) {
                    throw new RuntimeException(
                        'Department missing or inactive: ' . $name
                    );
                }

                $allocations[] = [
                    'department_id' => $department->id,
                    'percentage' => $percentage,
                ];
            }

            if (array_sum($row['allocations']) !== 100) {
                throw new RuntimeException(
                    'Allocations must total 100%: ' . $row['invoice']
                );
            }

            $prepared[] = [
                'data' => $row,
                'head_id' => $head->id,
                'gl_id' => $gl->id,
                'allocations' => $allocations,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Insert Drafts Atomically
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use ($prepared, $date) {
            $numberService = app(
                HospitalExpenseNumberService::class
            );

            foreach ($prepared as $item) {
                $row = $item['data'];

                $existing = HospitalExpense::query()
                    ->where('invoice_no', $row['invoice'])
                    ->where('description', $row['description'])
                    ->first();

                if ($existing) {
                    $this->command?->info(
                        'Already exists: ' . $row['invoice']
                    );
                    continue;
                }

                $expense = new HospitalExpense();

                $expense->fill([
                    'expense_no' => $numberService->generate($date),
                    'finance_head_id' => $item['head_id'],
                    'general_ledger_account_id' => $item['gl_id'],
                    'expense_classification' => $row['classification'],
                    'expense_type' => 'operating',
                    'expense_date' => $date,
                    'accounting_date' => $date,
                    'supplier_name' => $row['supplier'],
                    'invoice_no' => $row['invoice'],
                    'invoice_date' => $date,
                    'description' => $row['description'],
                    'subtotal' => $row['amount'],
                    'tax_amount' => 0,
                    'total_amount' => $row['amount'],
                    'remarks' => 'DEMO DATA — For ERP/Tally testing only.',
                ]);

                $expense->status = 'draft';
                $expense->save();

                $allocatedPaise = 0;
                $totalPaise = $row['amount'] * 100;
                $lastIndex = count($item['allocations']) - 1;

                foreach ($item['allocations'] as $index => $allocation) {
                    $amountPaise = $index === $lastIndex
                        ? $totalPaise - $allocatedPaise
                        : (int) round(
                            $totalPaise * $allocation['percentage'] / 100
                        );

                    $allocatedPaise += $amountPaise;

                    $expense->allocations()->create([
                        'department_id' => $allocation['department_id'],
                        'percentage' => $allocation['percentage'],
                        'allocated_amount' => number_format(
                            $amountPaise / 100,
                            2,
                            '.',
                            ''
                        ),
                    ]);
                }

                $this->command?->info(
                    'Created: ' . $expense->expense_no
                    . ' — ' . $row['description']
                );
            }
        });
    }
}
