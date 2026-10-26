<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tally_journal_export_attempts', function (Blueprint $table) {
            $table->foreignId('rejection_resolved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('rejection_resolved_at')->nullable();
            $table->text('rejection_resolution_evidence')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('tally_journal_export_attempts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rejection_resolved_by');
            $table->dropColumn(['rejection_resolved_at', 'rejection_resolution_evidence']);
        });
    }
};
