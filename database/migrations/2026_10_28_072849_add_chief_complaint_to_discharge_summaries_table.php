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
        Schema::table('discharge_summaries', function (Blueprint $table) {
            $table->text('chief_complaint')->nullable();
            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discharge_summaries', function (Blueprint $table) {
            $table->dropColumn('chief_complaint');
            //
        });
    }
};
