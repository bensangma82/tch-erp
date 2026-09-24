<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salary_structures', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Employee
            |--------------------------------------------------------------------------
            */

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Effective Period
            |--------------------------------------------------------------------------
            */

            $table->date('effective_from');

            $table->date('effective_to')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Salary Reference
            |--------------------------------------------------------------------------
            |
            | This is the agreed/reference monthly salary for the structure.
            | Component totals will be maintained separately.
            |
            */

            $table->decimal('monthly_salary', 12, 2)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            |
            | draft
            | active
            | superseded
            | cancelled
            |
            */

            $table->string('status', 30)
                ->default('draft');

            /*
            |--------------------------------------------------------------------------
            | Notes
            |--------------------------------------------------------------------------
            */

            $table->text('remarks')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Approval
            |--------------------------------------------------------------------------
            */

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')
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
                ['employee_id', 'status'],
                'employee_salary_structures_employee_status_idx'
            );

            $table->index(
                ['employee_id', 'effective_from', 'effective_to'],
                'employee_salary_structures_effective_idx'
            );

            $table->index(
                ['status', 'effective_from'],
                'employee_salary_structures_status_effective_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_structures');
    }
};