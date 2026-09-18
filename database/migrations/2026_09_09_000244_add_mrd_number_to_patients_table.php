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
        Schema::table('patients', function (Blueprint $table) {

            $table->string('mrd_number')
                ->nullable()
                ->unique()
                ->after('uhid');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {

            $table->dropUnique([
                'mrd_number'
            ]);

            $table->dropColumn(
                'mrd_number'
            );

        });
    }
};