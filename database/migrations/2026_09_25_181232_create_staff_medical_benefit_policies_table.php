<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'staff_medical_benefit_policies',
            function (Blueprint $table) {
                $table->id();

                $table->string('name', 150);

                $table->date('financial_year_start');
                $table->date('financial_year_end');

                $table->decimal(
                    'employee_annual_limit',
                    12,
                    2
                )->default(50000);

                $table->decimal(
                    'dependent_family_annual_limit',
                    12,
                    2
                )->default(25000);

                $table->boolean('carry_forward')
                    ->default(false);

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
                    [
                        'financial_year_start',
                        'financial_year_end',
                    ],
                    'staff_med_benefit_policy_fy_unique'
                );

                $table->index(
                    'is_active',
                    'staff_med_benefit_policy_active_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'staff_medical_benefit_policies'
        );
    }
};