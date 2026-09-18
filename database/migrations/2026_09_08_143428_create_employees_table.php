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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            $table->string('employee_code')->unique();

            $table->string('title')->nullable();

            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();

            $table->string('designation')->nullable();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->string('employee_type')->default('permanent');

            $table->string('professional_registration_no')->nullable();

            $table->string('qualification')->nullable();

            $table->string('speciality')->nullable();

            $table->string('phone', 20)->nullable();

            $table->string('email')->nullable();

            $table->date('date_of_joining')->nullable();

            $table->boolean('is_doctor')->default(false);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->softDeletes();

            $table->index('employee_code');

            $table->index([
                'first_name',
                'last_name',
            ]);

            $table->index('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};