<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_results', function (Blueprint $table) {
            $table->jsonb('structured_data')->nullable()->after('impression');
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_results', function (Blueprint $table) {
            $table->dropColumn('structured_data');
        });
    }
};