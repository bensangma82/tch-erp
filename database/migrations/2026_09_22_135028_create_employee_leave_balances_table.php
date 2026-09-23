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
        Schema::create(
            'employee_leave_balances',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId('employee_id')
                    ->constrained('employees')
                    ->restrictOnDelete();

                $table->foreignId('leave_type_id')
                    ->constrained('leave_types')
                    ->restrictOnDelete();

                $table->unsignedSmallInteger(
                    'leave_year'
                );

                $table->decimal(
                    'opening_balance',
                    6,
                    1
                )->default(0);

                $table->decimal(
                    'entitlement',
                    6,
                    1
                )->default(0);

                $table->decimal(
                    'adjustment',
                    6,
                    1
                )->default(0);

                $table->text('remarks')
                    ->nullable();

                $table->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamps();

                $table->unique(
                    [
                        'employee_id',
                        'leave_type_id',
                        'leave_year',
                    ],
                    'employee_leave_balance_unique'
                );

                $table->index(
                    [
                        'leave_year',
                        'leave_type_id',
                    ],
                    'employee_leave_balance_year_type_idx'
                );
            }
        );
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'employee_leave_balances'
        );
    }
};
