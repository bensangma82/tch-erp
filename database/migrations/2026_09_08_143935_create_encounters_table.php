<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('encounters', function (Blueprint $table) {

            $table->id();

            /*
             |--------------------------------------------------------------------------
             | Encounter identity
             |--------------------------------------------------------------------------
             */

            $table->string('encounter_no')->unique();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->restrictOnDelete();

            /*
             |--------------------------------------------------------------------------
             | Encounter type
             |--------------------------------------------------------------------------
             |
             | OPD
             | EMERGENCY
             | IPD
             | DIALYSIS
             | DAYCARE
             | PROCEDURE
             |
             */

            $table->string('encounter_type', 30);

            /*
             |--------------------------------------------------------------------------
             | Department and doctor
             |--------------------------------------------------------------------------
             */

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->foreignId('doctor_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            /*
             |--------------------------------------------------------------------------
             | Visit information
             |--------------------------------------------------------------------------
             */

            $table->date('encounter_date');

            $table->time('encounter_time')->nullable();

            $table->string('visit_type', 30)
                ->default('new');

            /*
             | Examples:
             |
             | new
             | follow_up
             | review
             | emergency
             | referral
             */

            $table->unsignedInteger('queue_number')
                ->nullable();

            /*
             |--------------------------------------------------------------------------
             | Referral / reason
             |--------------------------------------------------------------------------
             */

            $table->string('referred_by')->nullable();

            $table->text('reason_for_visit')->nullable();

            /*
             |--------------------------------------------------------------------------
             | Encounter status
             |--------------------------------------------------------------------------
             |
             | registered
             | waiting
             | in_consultation
             | completed
             | cancelled
             | admitted
             |
             */

            $table->string('status', 30)
                ->default('registered');

            /*
             |--------------------------------------------------------------------------
             | Administrative information
             |--------------------------------------------------------------------------
             */

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->softDeletes();

            /*
             |--------------------------------------------------------------------------
             | Indexes
             |--------------------------------------------------------------------------
             */

            $table->index('patient_id');

            $table->index('encounter_date');

            $table->index('encounter_type');

            $table->index('department_id');

            $table->index('doctor_id');

            $table->index([
                'encounter_date',
                'department_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encounters');
    }
};