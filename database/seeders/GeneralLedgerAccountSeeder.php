<?php

namespace Database\Seeders;

use App\Models\FinanceAccount;
use App\Models\FinanceHead;
use App\Models\GeneralLedgerAccount;
use Illuminate\Database\Seeder;
use RuntimeException;

class GeneralLedgerAccountSeeder extends Seeder
{
    public function run(): void
    {
        $cash = FinanceAccount::where('code', 'CASH-MAIN')->first();

        $opd = FinanceHead::where('code', 'INC-OPD')->first();

        if (! $cash || ! $opd) {
            throw new RuntimeException(
                'Required finance master records are missing.'
            );
        }

        $accounts = [
            [
                'account_code' => '1100',
                'account_name' => 'Main Cash',
                'account_type' => 'asset',
                'account_group' => 'Cash and Cash Equivalents',
                'normal_balance' => 'debit',
                'finance_account_id' => $cash->id,
                'tally_ledger_name' => 'Main Cash',
                'tally_group_name' => 'Cash-in-Hand',
            ],
                        [
                'account_code' => '1110',
                'account_name' => 'UPI Settlement Clearing',
                'account_type' => 'asset',
                'account_group' => 'Payment Clearing Accounts',
                'normal_balance' => 'debit',
                'tally_ledger_name' => null,
                'tally_group_name' => 'Current Assets',
            ],
            [
                'account_code' => '1120',
                'account_name' => 'Card Settlement Clearing',
                'account_type' => 'asset',
                'account_group' => 'Payment Clearing Accounts',
                'normal_balance' => 'debit',
                'tally_ledger_name' => null,
                'tally_group_name' => 'Current Assets',
            ],
            [
                'account_code' => '1130',
                'account_name' => 'Bank Settlement Account',
                'account_type' => 'asset',
                'account_group' => 'Bank Accounts',
                'normal_balance' => 'debit',
                'tally_ledger_name' => null,
                'tally_group_name' => 'Bank Accounts',
            ],
            [
                'account_code' => '1200',
                'account_name' => 'Patient Receivables',
                'account_type' => 'asset',
                'account_group' => 'Current Assets',
                'normal_balance' => 'debit',
                'tally_ledger_name' => null,
                'tally_group_name' => 'Sundry Debtors',
            ],
            [
                'account_code' => '4101',
                'account_name' => 'OPD Consultation Revenue',
                'account_type' => 'income',
                'account_group' => 'Clinical Revenue',
                'normal_balance' => 'credit',
                'finance_head_id' => $opd->id,
                'tally_ledger_name' => null,
                'tally_group_name' => 'Direct Incomes',
            ],
            [
                'account_code' => '4102',
                'account_name' => 'OPD Registration Revenue',
                'account_type' => 'income',
                'account_group' => 'Clinical Revenue',
                'normal_balance' => 'credit',
                'finance_head_id' => $opd->id,
                'tally_ledger_name' => null,
                'tally_group_name' => 'Direct Incomes',
            ],
            [
    'account_code' => '4201',
    'account_name' => 'Laboratory Revenue',
    'account_type' => 'income',
    'account_group' => 'Diagnostic and Procedure Revenue',
    'normal_balance' => 'credit',
    'finance_head_id' => FinanceHead::where('code', 'INC-LAB')->firstOrFail()->id,
    'tally_ledger_name' => null,
    'tally_group_name' => 'Direct Incomes',
],
[
    'account_code' => '4202',
    'account_name' => 'Radiology / Imaging Revenue',
    'account_type' => 'income',
    'account_group' => 'Diagnostic and Procedure Revenue',
    'normal_balance' => 'credit',
    'finance_head_id' => FinanceHead::where('code', 'INC-RAD')->firstOrFail()->id,
    'tally_ledger_name' => null,
    'tally_group_name' => 'Direct Incomes',
],
[
    'account_code' => '4203',
    'account_name' => 'ECG Revenue',
    'account_type' => 'income',
    'account_group' => 'Diagnostic and Procedure Revenue',
    'normal_balance' => 'credit',
    'finance_head_id' => FinanceHead::where('code', 'INC-ECG')->firstOrFail()->id,
    'tally_ledger_name' => null,
    'tally_group_name' => 'Direct Incomes',
],
[
    'account_code' => '4204',
    'account_name' => 'Echocardiography Revenue',
    'account_type' => 'income',
    'account_group' => 'Diagnostic and Procedure Revenue',
    'normal_balance' => 'credit',
    'finance_head_id' => FinanceHead::where('code', 'INC-ECHO')->firstOrFail()->id,
    'tally_ledger_name' => null,
    'tally_group_name' => 'Direct Incomes',
],
[
    'account_code' => '4205',
    'account_name' => 'Endoscopy Revenue',
    'account_type' => 'income',
    'account_group' => 'Diagnostic and Procedure Revenue',
    'normal_balance' => 'credit',
    'finance_head_id' => FinanceHead::where('code', 'INC-ENDO')->firstOrFail()->id,
    'tally_ledger_name' => null,
    'tally_group_name' => 'Direct Incomes',
],
[
    'account_code' => '4206',
    'account_name' => 'Procedure Revenue',
    'account_type' => 'income',
    'account_group' => 'Diagnostic and Procedure Revenue',
    'normal_balance' => 'credit',
    'finance_head_id' => FinanceHead::where('code', 'INC-PROCEDURE')->firstOrFail()->id,
    'tally_ledger_name' => null,
    'tally_group_name' => 'Direct Incomes',
],
        ];

        foreach ($accounts as $account) {
            GeneralLedgerAccount::updateOrCreate(
                ['account_code' => $account['account_code']],
                array_merge(
                    [
                        'is_active' => true,
                        'allow_posting' => true,
                    ],
                    $account
                )
            );
        }
    }
}