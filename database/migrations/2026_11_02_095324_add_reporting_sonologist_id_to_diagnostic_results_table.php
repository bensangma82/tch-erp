<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_results', function (Blueprint $table) {
            $table->foreignId('reporting_sonologist_id')
                ->nullable()
                ->constrained('sonologists')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_results', function (Blueprint $table) {
            $table->dropForeign(['reporting_sonologist_id']);
            $table->dropColumn('reporting_sonologist_id');
        });
    }
};
