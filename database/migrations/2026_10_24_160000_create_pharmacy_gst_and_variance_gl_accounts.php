<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $accounts = [
            ['2301', 'Input CGST Receivable', 'asset', 'Current Assets', 'debit', 'Recoverable input CGST only where input tax credit is legally available.'],
            ['2302', 'Input SGST Receivable', 'asset', 'Current Assets', 'Recoverable input SGST only where input tax credit is legally available.'],
            ['2303', 'Input IGST Receivable', 'asset', 'Current Assets', 'Recoverable input IGST only where input tax credit is legally available.'],
            ['2311', 'Output CGST Payable', 'liability', 'Current Liabilities', 'Output CGST on taxable Pharmacy supplies.'],
            ['2312', 'Output SGST Payable', 'liability', 'Current Liabilities', 'Output SGST on taxable Pharmacy supplies.'],
            ['2313', 'Output IGST Payable', 'liability', 'Current Liabilities', 'Output IGST on taxable Pharmacy supplies.'],
            ['5108', 'Pharmacy Purchase Price Variance', 'expense', 'Clinical Operating Expenses', 'Difference between supplier credit-note value and inventory carrying value on purchase returns.'],
        ];

        foreach ($accounts as [$code, $name, $type, $group, $description]) {
            // Derive the normal balance from the account class.
            // Assets and expenses normally carry debit balances; liabilities
            // and income normally carry credit balances.
            $normalBalance = in_array($type, ['asset', 'expense'], true)
                ? 'debit'
                : 'credit';
            $existing = DB::table('general_ledger_accounts')->where('account_code', $code)->first();
            if ($existing) {
                if ($existing->account_name !== $name || $existing->account_type !== $type) {
                    throw new RuntimeException("GL account code {$code} already exists with a different name/type; resolve the chart-of-accounts conflict before migrating.");
                }
                continue;
            }

            DB::table('general_ledger_accounts')->insert([
                'account_code' => $code,
                'account_name' => $name,
                'account_type' => $type,
                'account_group' => $group,
                'normal_balance' => $normalBalance,
                'is_active' => true,
                'allow_posting' => true,
                // Tally mappings deliberately remain blank until the accountant
                // maps each ERP ledger to the actual existing Tally ledger.
                'tally_ledger_name' => null,
                'tally_group_name' => null,
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Do not delete chart-of-accounts rows during rollback: they may already
        // be mapped to Tally or referenced by immutable posted journals.
    }
};
