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
            'leave_requests',
            function (Blueprint $table) {

                $table->id();

                $table->string(
                    'request_no',
                    40
                )->unique();

                $table->foreignId('employee_id')
                    ->constrained('employees')
                    ->restrictOnDelete();

                $table->foreignId('leave_type_id')
                    ->constrained('leave_types')
                    ->restrictOnDelete();

                $table->date('start_date');

                $table->date('end_date');

                $table->decimal(
                    'total_days',
                    6,
                    1
                );

                $table->text('reason')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Status values
                |--------------------------------------------------------------------------
                |
                | pending
                | approved
                | rejected
                | cancelled
                |
                */
                $table->string(
                    'status',
                    20
                )->default('pending');

                $table->timestamp('applied_at')
                    ->nullable();

                $table->foreignId('approved_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('approved_at')
                    ->nullable();

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

                $table->index(
                    [
                        'employee_id',
                        'status',
                    ],
                    'leave_requests_employee_status_idx'
                );

                $table->index(
                    [
                        'start_date',
                        'end_date',
                    ],
                    'leave_requests_date_range_idx'
                );

                $table->index(
                    [
                        'leave_type_id',
                        'status',
                    ],
                    'leave_requests_type_status_idx'
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
            'leave_requests'
        );
    }
};
