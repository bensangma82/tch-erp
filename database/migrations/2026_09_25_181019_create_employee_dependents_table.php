<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_dependents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->foreignId('patient_id')
                ->nullable()
                ->constrained('patients')
                ->nullOnDelete();

            $table->string('relationship', 50);

            $table->date('eligible_from')
                ->nullable();

            $table->date('eligible_until')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->text('remarks')
                ->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(
                ['employee_id', 'patient_id'],
                'employee_dependents_employee_patient_unique'
            );

            $table->index(
                ['employee_id', 'is_active'],
                'employee_dependents_employee_active_index'
            );

            $table->index(
                'patient_id',
                'employee_dependents_patient_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_dependents');
    }
};