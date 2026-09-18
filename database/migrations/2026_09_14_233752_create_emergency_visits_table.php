<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_visits', function (Blueprint $table) {
            $table->id();

            $table->string('emergency_no', 50)->unique();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->restrictOnDelete();

            $table->timestamp('arrival_at');

            $table->string('arrival_mode', 50)
                ->nullable();

            $table->string('brought_by', 150)
                ->nullable();

            $table->text('chief_complaint')
                ->nullable();

            $table->string('status', 50)
                ->default('registered');

            $table->foreignId('doctor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('disposition', 50)
                ->nullable();

            $table->timestamp('disposition_at')
                ->nullable();

            $table->unsignedBigInteger('admission_id')
                ->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('arrival_at');
            $table->index('status');
            $table->index('disposition');
            $table->index('doctor_id');
            $table->index('admission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_visits');
    }
};