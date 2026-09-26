<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('patient_id')
                ->nullable()
                ->after('employee_code')
                ->constrained('patients')
                ->nullOnDelete();

            $table->unique('patient_id');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['patient_id']);
            $table->dropForeign(['patient_id']);
            $table->dropColumn('patient_id');
        });
    }
};