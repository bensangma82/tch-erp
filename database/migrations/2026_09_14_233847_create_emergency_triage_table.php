<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_triage', function (Blueprint $table) {
            $table->id();

            $table->foreignId('emergency_visit_id')
                ->constrained('emergency_visits')
                ->cascadeOnDelete();

            $table->decimal('temperature', 4, 1)
                ->nullable();

            $table->unsignedSmallInteger('pulse')
                ->nullable();

            $table->unsignedSmallInteger('respiratory_rate')
                ->nullable();

            $table->unsignedSmallInteger('blood_pressure_systolic')
                ->nullable();

            $table->unsignedSmallInteger('blood_pressure_diastolic')
                ->nullable();

            $table->unsignedSmallInteger('spo2')
                ->nullable();

            $table->unsignedTinyInteger('gcs')
                ->nullable();

            $table->unsignedTinyInteger('pain_score')
                ->nullable();

            $table->string('triage_category', 50)
                ->nullable();

            $table->string('oxygen_support', 100)
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('recorded_at')
                ->nullable();

            $table->timestamps();

            $table->index('triage_category');
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_triage');
    }
};