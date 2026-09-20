<?php

namespace Database\Seeders;

use App\Models\FinanceAccount;
use App\Models\FinanceHead;
use Illuminate\Database\Seeder;

class FinanceMasterSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | FINANCE ACCOUNTS
        |--------------------------------------------------------------------------
        |
        | These represent WHERE money is held.
        | Opening balances are intentionally left at zero.
        |
        */

        $accounts = [
            [
                'code' => 'CASH-MAIN',
                'name' => 'Main Cash',
                'account_type' => 'cash',
            ],
            [
                'code' => 'CASH-PHARM',
                'name' => 'Pharmacy Cash',
                'account_type' => 'cash',
            ],
        ];

        foreach ($accounts as $account) {
            FinanceAccount::updateOrCreate(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'account_type' => $account['account_type'],
                    'opening_balance' => 0,
                    'is_active' => true,
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | INCOME HEADS
        |--------------------------------------------------------------------------
        */

        $incomeHeads = [
            [
                'code' => 'INC-OPD',
                'name' => 'OPD / Consultation',
                'category' => 'Clinical Services',
            ],
            [
                'code' => 'INC-IPD',
                'name' => 'Inpatient Services',
                'category' => 'Clinical Services',
            ],
            [
                'code' => 'INC-DIALYSIS',
                'name' => 'Dialysis',
                'category' => 'Clinical Services',
            ],
            [
                'code' => 'INC-PROCEDURE',
                'name' => 'Procedures',
                'category' => 'Clinical Services',
            ],

            [
                'code' => 'INC-LAB',
                'name' => 'Laboratory',
                'category' => 'Diagnostics',
            ],
            [
                'code' => 'INC-RAD',
                'name' => 'Radiology / Imaging',
                'category' => 'Diagnostics',
            ],
            [
                'code' => 'INC-ECG',
                'name' => 'ECG',
                'category' => 'Diagnostics',
            ],
            [
                'code' => 'INC-ECHO',
                'name' => 'Echo',
                'category' => 'Diagnostics',
            ],
            [
                'code' => 'INC-ENDO',
                'name' => 'Endoscopy',
                'category' => 'Diagnostics',
            ],

            [
                'code' => 'INC-PHARM',
                'name' => 'Pharmacy Sales',
                'category' => 'Pharmacy',
            ],

            [
                'code' => 'INC-MHIS',
                'name' => 'MHIS Receipts',
                'category' => 'Government Schemes',
            ],

            [
                'code' => 'INC-DONATION',
                'name' => 'Donations',
                'category' => 'Other Income',
            ],
            [
                'code' => 'INC-OTHER',
                'name' => 'Other Income',
                'category' => 'Other Income',
            ],
        ];

        foreach ($incomeHeads as $head) {
            FinanceHead::updateOrCreate(
                ['code' => $head['code']],
                [
                    'name' => $head['name'],
                    'head_type' => 'income',
                    'category' => $head['category'],
                    'is_active' => true,
                ]
            );
        }


        /*
        |--------------------------------------------------------------------------
        | EXPENSE HEADS
        |--------------------------------------------------------------------------
        */

        $expenseHeads = [
            [
                'code' => 'EXP-SALARY',
                'name' => 'Salary & Wages',
                'category' => 'Salaries & HR',
            ],
            [
                'code' => 'EXP-STAFF',
                'name' => 'Staff Benefits',
                'category' => 'Salaries & HR',
            ],

            [
                'code' => 'EXP-MEDICINE',
                'name' => 'Medicines',
                'category' => 'Medical Supplies',
            ],
            [
                'code' => 'EXP-CONSUMABLE',
                'name' => 'Medical Consumables',
                'category' => 'Medical Supplies',
            ],
            [
                'code' => 'EXP-LAB',
                'name' => 'Laboratory Supplies',
                'category' => 'Medical Supplies',
            ],

            [
                'code' => 'EXP-ELECTRICITY',
                'name' => 'Electricity',
                'category' => 'Utilities',
            ],
            [
                'code' => 'EXP-WATER',
                'name' => 'Water',
                'category' => 'Utilities',
            ],
            [
                'code' => 'EXP-INTERNET',
                'name' => 'Internet & Telephone',
                'category' => 'Utilities',
            ],
            [
                'code' => 'EXP-FUEL',
                'name' => 'Fuel',
                'category' => 'Utilities',
            ],

            [
                'code' => 'EXP-MAINTENANCE',
                'name' => 'Repairs & Maintenance',
                'category' => 'Maintenance',
            ],
            [
                'code' => 'EXP-EQUIPMENT',
                'name' => 'Medical Equipment',
                'category' => 'Capital / Equipment',
            ],
            [
                'code' => 'EXP-FURNITURE',
                'name' => 'Furniture & Fixtures',
                'category' => 'Capital / Equipment',
            ],

            [
                'code' => 'EXP-OFFICE',
                'name' => 'Office & Stationery',
                'category' => 'Administrative',
            ],
            [
                'code' => 'EXP-FOOD',
                'name' => 'Food & Kitchen',
                'category' => 'Administrative',
            ],
            [
                'code' => 'EXP-TRANSPORT',
                'name' => 'Transport & Travel',
                'category' => 'Administrative',
            ],
            [
                'code' => 'EXP-PROFESSIONAL',
                'name' => 'Professional Fees',
                'category' => 'Administrative',
            ],
            [
                'code' => 'EXP-OTHER',
                'name' => 'Other Expenses',
                'category' => 'Other Expenses',
            ],
        ];

        foreach ($expenseHeads as $head) {
            FinanceHead::updateOrCreate(
                ['code' => $head['code']],
                [
                    'name' => $head['name'],
                    'head_type' => 'expense',
                    'category' => $head['category'],
                    'is_active' => true,
                ]
            );
        }
    }
}