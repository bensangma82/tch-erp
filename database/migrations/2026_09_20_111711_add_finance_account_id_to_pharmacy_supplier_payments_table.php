<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pharmacy_supplier_payments', function (Blueprint $table) {
            $table->foreignId('finance_account_id')
                ->nullable()
                ->after('pharmacy_supplier_id')
                ->constrained('finance_accounts')
                ->nullOnDelete();

            $table->index('finance_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pharmacy_supplier_payments', function (Blueprint $table) {
            $table->dropForeign(['finance_account_id']);
            $table->dropColumn('finance_account_id');
        });
    }
};