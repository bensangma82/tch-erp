<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_vouchers', function (Blueprint $table) {
            $table->foreignId('payroll_run_id')
                ->nullable()
                ->after('finance_account_id')
                ->constrained('payroll_runs')
                ->restrictOnDelete();

            $table->unique(
                ['payroll_run_id', 'finance_account_id'],
                'finance_vouchers_payroll_account_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('finance_vouchers', function (Blueprint $table) {
            $table->dropUnique(
                'finance_vouchers_payroll_account_unique'
            );

            $table->dropForeign([
                'payroll_run_id',
            ]);

            $table->dropColumn('payroll_run_id');
        });
    }
};