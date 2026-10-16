<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_accounts', function (Blueprint $table) {
            $table->foreignId('general_ledger_account_id')
                ->nullable()
                ->constrained('general_ledger_accounts')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('finance_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId(
                'general_ledger_account_id'
            );
        });
    }
};