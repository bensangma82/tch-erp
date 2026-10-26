<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tally_journal_export_attempts', function (Blueprint $table) {
            $table->uuid('original_remote_id')->nullable();
            $table->string('original_export_reference', 100)->nullable();
            $table->longText('original_tally_response')->nullable();
            $table->string('tally_company')->nullable();
        });
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::table('tally_journal_export_attempts')->exists()) {
            throw new \RuntimeException('Cannot roll back Tally recovery evidence while audit records exist.');
        }
        Schema::table('tally_journal_export_attempts', function (Blueprint $table) {
            $table->dropColumn(['original_remote_id','original_export_reference','original_tally_response','tally_company']);
        });
    }
};
