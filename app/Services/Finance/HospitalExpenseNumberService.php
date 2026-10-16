<?php

namespace App\Services\Finance;

use App\Models\HospitalExpense;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use LogicException;

class HospitalExpenseNumberService
{
    /**
     * Generate a hospital expense reference.
     *
     * Example: HE-20261015-00001
     *
     * Must be called inside a database transaction.
     */
    public function generate(string $expenseDate): string
    {
        if (DB::transactionLevel() < 1) {
            throw new LogicException(
                'Expense numbering requires an active database transaction.'
            );
        }

        $date = Carbon::parse($expenseDate)->format('Ymd');

        /*
        |--------------------------------------------------------------------------
        | PostgreSQL Advisory Lock
        |--------------------------------------------------------------------------
        |
        | Serializes expense numbering for the same date.
        | Lock is released automatically at transaction completion.
        |
        */

        DB::select(
            'SELECT pg_advisory_xact_lock(hashtext(?))',
            ["hospital-expense-HE-{$date}"]
        );

        $prefix = "HE-{$date}-";

        $lastExpense = HospitalExpense::query()
            ->where('expense_no', 'like', $prefix . '%')
            ->orderByDesc('expense_no')
            ->first();

        $sequence = $lastExpense
            ? (int) substr($lastExpense->expense_no, -5) + 1
            : 1;

        return sprintf(
            '%s%05d',
            $prefix,
            $sequence
        );
    }
}
