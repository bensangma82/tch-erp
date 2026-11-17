<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mhis_settlement_import_rows', function (Blueprint $table) {
            $table->foreignId('posted_receipt_id')->nullable()->unique()->constrained('ip_billing_mhis_receipts')->restrictOnDelete();
            $table->foreignId('posted_journal_id')->nullable()->unique()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('bank_verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('bank_verified_at')->nullable();
            $table->timestamp('posted_at')->nullable();
        });
        DB::table('permissions')->insertOrIgnore([
            'name' => 'finance.mhis-settlement-import.post',
            'label' => 'Post bank-verified MHIS imported settlements',
            'module' => 'finance',
            'description' => 'Create audited MHIS receipts and accounting journals from verified imported settlements.',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        // Existing administrator override applies; no automatic role grants.
    }

    public function down(): void
    {
        if (DB::table('mhis_settlement_import_rows')->whereNotNull('posted_receipt_id')->exists()) {
            throw new RuntimeException('Cannot remove posting audit fields after settlements have been posted.');
        }
        Schema::table('mhis_settlement_import_rows', function (Blueprint $table) {
            $table->dropConstrainedForeignId('posted_receipt_id');
            $table->dropConstrainedForeignId('posted_journal_id');
            $table->dropConstrainedForeignId('posted_by');
            $table->dropConstrainedForeignId('bank_verified_by');
            $table->dropColumn(['bank_verified_at', 'posted_at']);
        });
    }
};
