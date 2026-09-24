<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Payroll Period
            |--------------------------------------------------------------------------
            */

            $table->unsignedSmallInteger('year');

            $table->unsignedTinyInteger('month');

            $table->date('period_start');

            $table->date('period_end');

            /*
            |--------------------------------------------------------------------------
            | Payroll Identification
            |--------------------------------------------------------------------------
            */

            $table->string('payroll_no', 50)
                ->unique();

            $table->string('description', 150)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            |
            | draft
            | processing
            | calculated
            | approved
            | paid
            | locked
            | cancelled
            |
            */

            $table->string('status', 30)
                ->default('draft');

            /*
            |--------------------------------------------------------------------------
            | Payroll Totals
            |--------------------------------------------------------------------------
            |
            | These are summary values for the complete payroll run.
            |
            */

            $table->unsignedInteger('employee_count')
                ->default(0);

            $table->decimal('total_earnings', 14, 2)
                ->default(0);

            $table->decimal('total_deductions', 14, 2)
                ->default(0);

            $table->decimal('total_net_pay', 14, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Calculation
            |--------------------------------------------------------------------------
            */

            $table->timestamp('calculated_at')
                ->nullable();

            $table->foreignId('calculated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Approval
            |--------------------------------------------------------------------------
            */

            $table->timestamp('approved_at')
                ->nullable();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            $table->date('payment_date')
                ->nullable();

            $table->timestamp('paid_at')
                ->nullable();

            $table->foreignId('paid_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Locking
            |--------------------------------------------------------------------------
            |
            | Once locked, payroll entries should no longer be recalculated
            | or edited.
            |
            */

            $table->timestamp('locked_at')
                ->nullable();

            $table->foreignId('locked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Notes
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
                ['year', 'month'],
                'payroll_runs_year_month_unique'
            );

            $table->index(
                ['status', 'year', 'month'],
                'payroll_runs_status_period_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};