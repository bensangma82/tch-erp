<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {

            $table->dropForeign([
                'consultant_id'
            ]);

        });

        Schema::table('admissions', function (Blueprint $table) {

            $table
                ->foreign('consultant_id')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();

        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {

            $table->dropForeign([
                'consultant_id'
            ]);

        });

        Schema::table('admissions', function (Blueprint $table) {

            $table
                ->foreign('consultant_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

        });
    }
};