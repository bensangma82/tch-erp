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
        Schema::table('tally_imported_vouchers', function (Blueprint $table) {
            $table->foreignId('reconciled_by')
                ->nullable()
                ->after('reconciliation_notes')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reconciled_at')
                ->nullable()
                ->after('reconciled_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tally_imported_vouchers', function (Blueprint $table) {
            $table->dropForeign(['reconciled_by']);
            $table->dropColumn([
                'reconciled_by',
                'reconciled_at',
            ]);
        });
    }
};
