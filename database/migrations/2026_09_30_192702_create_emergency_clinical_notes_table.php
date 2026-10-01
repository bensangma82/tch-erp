<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_clinical_notes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('emergency_visit_id')
                ->constrained('emergency_visits')
                ->cascadeOnDelete();

            $table->text('presenting_complaints')->nullable();
            $table->text('history')->nullable();

            $table->unsignedSmallInteger('blood_pressure_systolic')->nullable();
            $table->unsignedSmallInteger('blood_pressure_diastolic')->nullable();
            $table->unsignedSmallInteger('pulse')->nullable();
            $table->unsignedSmallInteger('respiratory_rate')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->unsignedSmallInteger('spo2')->nullable();
            $table->unsignedTinyInteger('gcs')->nullable();
            $table->unsignedTinyInteger('pain_score')->nullable();
            $table->string('oxygen_support', 100)->nullable();

            $table->text('general_examination')->nullable();
            $table->text('systemic_examination')->nullable();

            $table->text('provisional_diagnosis')->nullable();
            $table->text('investigations')->nullable();
            $table->text('treatment_given')->nullable();

            $table->string('disposition', 50)->nullable();
            $table->text('discharge_advice')->nullable();

            $table->foreignId('doctor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('documented_at')->nullable();

            $table->timestamps();

            $table->unique('emergency_visit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_clinical_notes');
    }
};