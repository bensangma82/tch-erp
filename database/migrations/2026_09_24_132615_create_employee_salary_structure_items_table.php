<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'employee_salary_structure_items',
            function (Blueprint $table) {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Salary Structure
                |--------------------------------------------------------------------------
                */

                $table->foreignId('employee_salary_structure_id')
                    ->constrained('employee_salary_structures')
                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Salary Component
                |--------------------------------------------------------------------------
                */

                $table->foreignId('salary_component_id')
                    ->constrained('salary_components')
                    ->restrictOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Calculation
                |--------------------------------------------------------------------------
                |
                | Normally inherited from the salary component:
                |
                | fixed
                | percentage
                |
                | These fields are stored here so each employee can have
                | a different value/rate for the same component.
                |
                */

                $table->string('calculation_type', 30)
                    ->default('fixed');

                $table->decimal('amount', 12, 2)
                    ->nullable();

                $table->decimal('percentage', 8, 4)
                    ->nullable();

                $table->foreignId('percentage_of_component_id')
                    ->nullable()
                    ->constrained('salary_components')
                    ->nullOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Optional Limits
                |--------------------------------------------------------------------------
                |
                | Useful for components whose calculated value may be subject
                | to a ceiling or minimum.
                |
                */

                $table->decimal('minimum_amount', 12, 2)
                    ->nullable();

                $table->decimal('maximum_amount', 12, 2)
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Status / Ordering
                |--------------------------------------------------------------------------
                */

                $table->boolean('is_active')
                    ->default(true);

                $table->unsignedInteger('sort_order')
                    ->default(0);

                /*
                |--------------------------------------------------------------------------
                | Notes
                |--------------------------------------------------------------------------
                */

                $table->text('remarks')
                    ->nullable();

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | Constraints / Indexes
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'employee_salary_structure_id',
                        'salary_component_id',
                    ],
                    'employee_salary_structure_component_unique'
                );

                $table->index(
                    [
                        'employee_salary_structure_id',
                        'is_active',
                    ],
                    'employee_salary_structure_items_active_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'employee_salary_structure_items'
        );
    }
};