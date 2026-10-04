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
        Schema::table('diagnostic_samples', function (Blueprint $table) {
            $table->dropUnique([
                'sample_no',
            ]);

            $table->index(
                'sample_no',
                'diagnostic_samples_sample_no_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diagnostic_samples', function (Blueprint $table) {
            $table->dropIndex(
                'diagnostic_samples_sample_no_index'
            );

            $table->unique(
                'sample_no'
            );
        });
    }
};