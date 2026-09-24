<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Identification
            |--------------------------------------------------------------------------
            */

            $table->string('code', 50)->unique();

            $table->string('name', 150);

            /*
            |--------------------------------------------------------------------------
            | Component Type
            |--------------------------------------------------------------------------
            |
            | earning
            | deduction
            |
            */

            $table->string('type', 20);

            /*
            |--------------------------------------------------------------------------
            | Calculation Method
            |--------------------------------------------------------------------------
            |
            | fixed
            | percentage
            |
            | Percentage components may later reference another component
            | such as BASIC.
            |
            */

            $table->string('calculation_type', 30)
                ->default('fixed');

            $table->foreignId('percentage_of_component_id')
                ->nullable()
                ->constrained('salary_components')
                ->nullOnDelete();

            $table->decimal('default_percentage', 8, 4)
                ->nullable();

            $table->decimal('default_amount', 12, 2)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Payroll Behaviour
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_taxable')
                ->default(false);

            $table->boolean('affects_gross')
                ->default(true);

            $table->boolean('is_statutory')
                ->default(false);

            $table->boolean('is_recurring')
                ->default(true);

            /*
            |--------------------------------------------------------------------------
            | Display / Status
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->boolean('is_active')
                ->default(true);

            $table->text('description')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(
                ['type', 'is_active'],
                'salary_components_type_active_idx'
            );

            $table->index(
                ['is_statutory', 'is_active'],
                'salary_components_statutory_active_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_components');
    }
};