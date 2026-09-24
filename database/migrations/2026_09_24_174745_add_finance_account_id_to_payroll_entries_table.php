<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->foreignId('finance_account_id')
                ->nullable()
                ->after('payment_mode')
                ->constrained('finance_accounts')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->dropForeign(['finance_account_id']);
            $table->dropColumn('finance_account_id');
        });
    }
};