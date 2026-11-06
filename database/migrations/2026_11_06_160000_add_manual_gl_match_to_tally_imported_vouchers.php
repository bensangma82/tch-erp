<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('tally_imported_vouchers', function (Blueprint $table) {
            $table->foreignId('journal_entry_id')->nullable()->unique()
                ->constrained('journal_entries')->restrictOnDelete();
        });
    }
    public function down(): void {
        if (DB::table('tally_imported_vouchers')->whereNotNull('journal_entry_id')->exists()) {
            throw new RuntimeException('Refusing to delete existing manual GL reconciliation links.');
        }
        Schema::table('tally_imported_vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_entry_id');
        });
    }
};
