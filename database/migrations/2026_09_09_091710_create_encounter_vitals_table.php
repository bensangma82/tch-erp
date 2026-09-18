<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encounter_vitals', function (Blueprint $table) {

            $table->id();

            $table->foreignId('encounter_id')
                ->constrained('encounters')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Blood Pressure
            |--------------------------------------------------------------------------
            */

            $table->unsignedSmallInteger('systolic_bp')
                ->nullable();

            $table->unsignedSmallInteger('diastolic_bp')
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Basic Vitals
            |--------------------------------------------------------------------------
            */

            $table->unsignedSmallInteger('pulse_rate')
                ->nullable();

            $table->unsignedSmallInteger('respiratory_rate')
                ->nullable();

            $table->decimal('temperature', 4, 1)
                ->nullable();

            $table->unsignedSmallInteger('spo2')
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Anthropometry
            |--------------------------------------------------------------------------
            */

            $table->decimal('weight_kg', 6, 2)
                ->nullable();

            $table->decimal('height_cm', 6, 2)
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Optional bedside tests
            |--------------------------------------------------------------------------
            */

            $table->decimal('blood_glucose', 7, 2)
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Nursing Notes
            |--------------------------------------------------------------------------
            */

            $table->text('notes')
                ->nullable();


            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('recorded_at')
                ->nullable();

            $table->timestamps();


            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('encounter_id');
            $table->index('recorded_at');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('encounter_vitals');
    }
};