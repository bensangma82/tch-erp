<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discharge_summaries', function (Blueprint $table) {

            $table->id();

            $table->foreignId('admission_id')
                ->unique()
                ->constrained('admissions')
                ->cascadeOnDelete();

            $table->text('final_diagnosis')
                ->nullable();

            $table->text('hospital_course')
                ->nullable();

            $table->text('procedures_performed')
                ->nullable();

            $table->text('important_investigations')
                ->nullable();

            $table->text('treatment_given')
                ->nullable();

            $table->text('condition_at_discharge')
                ->nullable();

            $table->text('discharge_medications')
                ->nullable();

            $table->text('follow_up_advice')
                ->nullable();

            $table->text('diet_advice')
                ->nullable();

            $table->text('warning_signs')
                ->nullable();

            $table->foreignId('prepared_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'discharge_summaries'
        );
    }
};