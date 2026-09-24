<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_adjustments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payroll_run_id')
                ->constrained('payroll_runs')
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Adjustment Classification
            |--------------------------------------------------------------------------
            |
            | earning:
            |   Bonus, arrears, overtime, incentive, additional allowance, etc.
            |
            | deduction:
            |   Recovery, penalty, advance recovery, other deduction, etc.
            |
            | lop:
            |   Loss of Pay. Amount will represent the calculated LOP deduction
            |   and lop_days records the number of unpaid days.
            |
            */

            $table->string('type', 20);

            $table->string('code', 50);

            $table->string('name', 150);

            /*
            |--------------------------------------------------------------------------
            | Monetary / LOP Values
            |--------------------------------------------------------------------------
            */

            $table->decimal('amount', 12, 2)->default(0);

            $table->decimal('lop_days', 8, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Optional Notes
            |--------------------------------------------------------------------------
            */

            $table->text('remarks')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Control Fields
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_active')->default(true);

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

            $table->index([
                'payroll_run_id',
                'employee_id',
            ]);

            $table->index([
                'payroll_run_id',
                'type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_adjustments');
    }
};