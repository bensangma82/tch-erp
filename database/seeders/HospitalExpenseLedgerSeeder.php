<?php

namespace Database\Seeders;

use App\Models\FinanceHead;
use App\Models\GeneralLedgerAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HospitalExpenseLedgerSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Hospital Expense Chart of Accounts
        |--------------------------------------------------------------------------
        |
        | Creates missing accounts only.
        | Existing GL accounts and Tally mappings are preserved.
        | No accounting journals are created.
        |
        */

        $accounts = [

            // ASSETS
            ['1300', 'Medicine Inventory', 'asset', 'Inventory Assets', null],
            ['1310', 'Medical Supplies Inventory', 'asset', 'Inventory Assets', null],
            ['1320', 'Laboratory Supplies Inventory', 'asset', 'Inventory Assets', null],
            ['1500', 'Medical Equipment', 'asset', 'Fixed Assets', null],
            ['1510', 'Furniture and Fixtures', 'asset', 'Fixed Assets', null],

            // LIABILITIES
            ['2200', 'Accounts Payable', 'liability', 'Current Liabilities', null],
            ['2210', 'Salaries Payable', 'liability', 'Current Liabilities', null],
            ['2220', 'Accrued Expenses Payable', 'liability', 'Current Liabilities', null],

            // CLINICAL EXPENSES
            ['5101', 'Medicines Consumed / Pharmacy COGS', 'expense', 'Clinical Operating Expenses', 'EXP-MEDICINE'],
            ['5102', 'Medical and Surgical Consumables', 'expense', 'Clinical Operating Expenses', 'EXP-CONSUMABLE'],
            ['5103', 'Laboratory Reagents and Consumables', 'expense', 'Clinical Operating Expenses', 'EXP-LAB'],
            ['5104', 'Dialysis Consumables', 'expense', 'Clinical Operating Expenses', 'EXP-CONSUMABLE'],
            ['5105', 'Oxygen and Medical Gases', 'expense', 'Clinical Operating Expenses', 'EXP-CONSUMABLE'],
            ['5106', 'Clinical Equipment Maintenance', 'expense', 'Clinical Operating Expenses', 'EXP-MAINTENANCE'],
            ['5107', 'Biomedical Waste Management', 'expense', 'Clinical Operating Expenses', 'EXP-OTHER'],

            // CLINICAL PAYROLL
            ['5201', 'Doctors Salaries and Remuneration', 'expense', 'Clinical Personnel Expenses', 'EXP-SALARY'],
            ['5202', 'Nursing Salaries and Allowances', 'expense', 'Clinical Personnel Expenses', 'EXP-SALARY'],
            ['5203', 'Allied Health Staff Salaries', 'expense', 'Clinical Personnel Expenses', 'EXP-SALARY'],

            // NON-CLINICAL OPERATING EXPENSES
            ['6101', 'Administrative Salaries', 'expense', 'Non-Clinical Personnel Expenses', 'EXP-SALARY'],
            ['6102', 'Electricity Expenses', 'expense', 'Shared Operating Expenses', 'EXP-ELECTRICITY'],
            ['6103', 'Water and Utilities', 'expense', 'Shared Operating Expenses', 'EXP-WATER'],
            ['6104', 'Building Repairs and Maintenance', 'expense', 'Non-Clinical Operating Expenses', 'EXP-MAINTENANCE'],
            ['6105', 'Housekeeping and Sanitation', 'expense', 'Non-Clinical Operating Expenses', 'EXP-OTHER'],
            ['6106', 'IT and Software Expenses', 'expense', 'Non-Clinical Operating Expenses', 'EXP-INTERNET'],
            ['6107', 'Office and Administrative Expenses', 'expense', 'Non-Clinical Operating Expenses', 'EXP-OFFICE'],
            ['6108', 'Security Expenses', 'expense', 'Non-Clinical Operating Expenses', 'EXP-OTHER'],
            ['6109', 'Audit and Professional Fees', 'expense', 'Non-Clinical Operating Expenses', 'EXP-PROFESSIONAL'],
            ['6110', 'Food and Kitchen Expenses', 'expense', 'Non-Clinical Operating Expenses', 'EXP-FOOD'],
            ['6111', 'Fuel Expenses', 'expense', 'Shared Operating Expenses', 'EXP-FUEL'],
            ['6112', 'Transport and Travel Expenses', 'expense', 'Shared Operating Expenses', 'EXP-TRANSPORT'],
            ['6113', 'Staff Benefits Expense', 'expense', 'Shared Personnel Expenses', 'EXP-STAFF'],

            // DEPRECIATION
            ['6201', 'Depreciation - Clinical Assets', 'expense', 'Depreciation Expenses', 'EXP-EQUIPMENT'],
            ['6202', 'Depreciation - Non-Clinical Assets', 'expense', 'Depreciation Expenses', 'EXP-FURNITURE'],
        ];

        DB::transaction(function () use ($accounts) {

            /*
            |--------------------------------------------------------------------------
            | Validate All Required Finance Heads
            |--------------------------------------------------------------------------
            */

            $headCodes = collect($accounts)
                ->pluck(4)
                ->filter()
                ->unique()
                ->values();

            $heads = FinanceHead::query()
                ->whereIn('code', $headCodes)
                ->get()
                ->keyBy('code');

            foreach ($headCodes as $code) {
                if (! $heads->has($code)) {
                    throw new RuntimeException(
                        "Required Finance Head {$code} is missing."
                    );
                }

                if (
                    $heads[$code]->head_type !== 'expense'
                    || ! $heads[$code]->is_active
                ) {
                    throw new RuntimeException(
                        "Finance Head {$code} must be an active expense head."
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Create Missing GL Accounts
            |--------------------------------------------------------------------------
            */

            foreach ($accounts as [
                $code,
                $name,
                $type,
                $group,
                $financeHeadCode
            ]) {
                $normalBalance = in_array(
                    $type,
                    ['asset', 'expense'],
                    true
                ) ? 'debit' : 'credit';

                GeneralLedgerAccount::firstOrCreate(
                    ['account_code' => $code],
                    [
                        'account_name' => $name,
                        'account_type' => $type,
                        'account_group' => $group,
                        'normal_balance' => $normalBalance,
                        'finance_head_id' => $financeHeadCode
                            ? $heads[$financeHeadCode]->id
                            : null,
                        'tally_ledger_name' => null,
                        'tally_group_name' => match ($type) {
                            'asset' => $group === 'Fixed Assets'
                                ? 'Fixed Assets'
                                : 'Stock-in-Hand',
                            'liability' => 'Current Liabilities',
                            'expense' => 'Indirect Expenses',
                            default => null,
                        },
                        'is_active' => true,
                        'allow_posting' => true,
                    ]
                );
            }
        });
    }
}
