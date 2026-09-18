<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emergency_visits', function (Blueprint $table) {
            $table
                ->foreign('admission_id')
                ->references('id')
                ->on('admissions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('emergency_visits', function (Blueprint $table) {
            $table->dropForeign([
                'admission_id'
            ]);
        });
    }
};