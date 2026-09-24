<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_entry_items', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Payroll Entry
            |--------------------------------------------------------------------------
            */

            $table->foreignId('payroll_entry_id')
                ->constrained('payroll_entries')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Source Component
            |--------------------------------------------------------------------------
            |
            | Nullable so historical payroll remains valid even if a salary
            | component is later removed from the master.
            |
            */

            $table->foreignId('salary_component_id')
                ->nullable()
                ->constrained('salary_components')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Component Snapshot
            |--------------------------------------------------------------------------
            */

            $table->string('component_code', 50);

            $table->string('component_name', 150);

            /*
            |--------------------------------------------------------------------------
            | Type
            |--------------------------------------------------------------------------
            |
            | earning
            | deduction
            |
            */

            $table->string('type', 20);

            /*
            |--------------------------------------------------------------------------
            | Calculation Snapshot
            |--------------------------------------------------------------------------
            |
            | fixed
            | percentage
            | adjustment
            |
            */

            $table->string('calculation_type', 30)
                ->default('fixed');

            $table->decimal('base_amount', 12, 2)
                ->nullable();

            $table->decimal('percentage', 8, 4)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Payroll Amount
            |--------------------------------------------------------------------------
            |
            | Final amount applied to this employee for this payroll period.
            |
            */

            $table->decimal('amount', 12, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Behaviour Snapshot
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_taxable')
                ->default(false);

            $table->boolean('is_statutory')
                ->default(false);

            $table->boolean('is_recurring')
                ->default(true);

            /*
            |--------------------------------------------------------------------------
            | Proration
            |--------------------------------------------------------------------------
            |
            | Indicates whether this component was reduced because of
            | payable days / loss of pay.
            |
            */

            $table->boolean('is_prorated')
                ->default(false);

            $table->decimal('proration_factor', 10, 6)
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Ordering / Notes
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('sort_order')
                ->default(0);

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
                    'payroll_entry_id',
                    'component_code',
                ],
                'payroll_entry_component_unique'
            );

            $table->index(
                [
                    'payroll_entry_id',
                    'type',
                ],
                'payroll_entry_items_entry_type_idx'
            );

            $table->index(
                [
                    'component_code',
                    'type',
                ],
                'payroll_entry_items_component_type_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_entry_items');
    }
};