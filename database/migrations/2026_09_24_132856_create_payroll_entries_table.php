<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_entries', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Payroll / Employee
            |--------------------------------------------------------------------------
            */

            $table->foreignId('payroll_run_id')
                ->constrained('payroll_runs')
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->restrictOnDelete();

            $table->foreignId('employee_salary_structure_id')
                ->nullable()
                ->constrained('employee_salary_structures')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Employee Snapshot
            |--------------------------------------------------------------------------
            |
            | Historical payroll must not change when the employee's current
            | HR record is later modified.
            |
            */

            $table->string('employee_code', 50);

            $table->string('employee_name', 200);

            $table->string('designation', 150)
                ->nullable();

            $table->string('department_name', 150)
                ->nullable();

            $table->string('employee_type', 50)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Attendance / Payable Days
            |--------------------------------------------------------------------------
            */

            $table->decimal('calendar_days', 6, 2)
                ->default(0);

            $table->decimal('payable_days', 6, 2)
                ->default(0);

            $table->decimal('lop_days', 6, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Salary Totals
            |--------------------------------------------------------------------------
            */

            $table->decimal('gross_earnings', 12, 2)
                ->default(0);

            $table->decimal('total_deductions', 12, 2)
                ->default(0);

            $table->decimal('net_pay', 12, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | LOP / Proration
            |--------------------------------------------------------------------------
            */

            $table->decimal('lop_deduction', 12, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Adjustments
            |--------------------------------------------------------------------------
            |
            | These summary fields support one-time payroll adjustments.
            | Detailed adjustment records can be added later.
            |
            */

            $table->decimal('additional_earnings', 12, 2)
                ->default(0);

            $table->decimal('additional_deductions', 12, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            |
            | draft
            | calculated
            | approved
            | paid
            | cancelled
            |
            */

            $table->string('status', 30)
                ->default('draft');

            /*
            |--------------------------------------------------------------------------
            | Payment Information
            |--------------------------------------------------------------------------
            */

            $table->string('payment_mode', 30)
                ->nullable();

            $table->string('payment_reference', 150)
                ->nullable();

            $table->date('payment_date')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Remarks
            |--------------------------------------------------------------------------
            */

            $table->text('remarks')
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
            | Constraints / Indexes
            |--------------------------------------------------------------------------
            */

            $table->unique(
                [
                    'payroll_run_id',
                    'employee_id',
                ],
                'payroll_entries_run_employee_unique'
            );

            $table->index(
                [
                    'employee_id',
                    'status',
                ],
                'payroll_entries_employee_status_idx'
            );

            $table->index(
                [
                    'payroll_run_id',
                    'status',
                ],
                'payroll_entries_run_status_idx'
            );

            $table->index(
                'employee_code',
                'payroll_entries_employee_code_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_entries');
    }
};