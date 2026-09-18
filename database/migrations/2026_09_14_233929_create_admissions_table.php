<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();

            $table->string('admission_no', 50)
                ->unique();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->restrictOnDelete();

            $table->string('source_type', 50)
                ->default('emergency');

            $table->unsignedBigInteger('source_id')
                ->nullable();

            $table->timestamp('admitted_at');

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->foreignId('consultant_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('admission_type', 50)
                ->default('emergency');

            $table->text('admission_reason')
                ->nullable();

            $table->text('provisional_diagnosis')
                ->nullable();

            $table->foreignId('bed_id')
                ->nullable()
                ->constrained('beds')
                ->nullOnDelete();

            $table->string('status', 50)
                ->default('admitted');

            $table->timestamp('discharged_at')
                ->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('source_type');
            $table->index('source_id');
            $table->index('admitted_at');
            $table->index('status');
            $table->index('consultant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};