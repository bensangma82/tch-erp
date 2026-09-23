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
            'employee_contracts',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId('employee_id')
                    ->constrained('employees')
                    ->restrictOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Contract Identification
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'contract_no',
                    50
                )->unique();

                $table->string(
                    'contract_type',
                    50
                )->default('contractual');

                $table->string(
                    'designation',
                    150
                )->nullable();

                $table->foreignId('department_id')
                    ->nullable()
                    ->constrained('departments')
                    ->nullOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Contract Period
                |--------------------------------------------------------------------------
                */

                $table->date(
                    'start_date'
                );

                $table->date(
                    'end_date'
                );

                $table->date(
                    'renewal_due_date'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                |
                | Suggested values:
                | active
                | expired
                | renewed
                | terminated
                | cancelled
                |
                */

                $table->string(
                    'status',
                    30
                )->default('active');


                /*
                |--------------------------------------------------------------------------
                | Renewal / Previous Contract Link
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'previous_contract_id'
                )
                    ->nullable()
                    ->constrained(
                        'employee_contracts'
                    )
                    ->nullOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Contract Details
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'reference_no',
                    100
                )->nullable();

                $table->decimal(
                    'monthly_remuneration',
                    12,
                    2
                )->nullable();

                $table->text(
                    'terms_summary'
                )->nullable();

                $table->text(
                    'remarks'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Closure / Termination
                |--------------------------------------------------------------------------
                */

                $table->date(
                    'terminated_on'
                )->nullable();

                $table->text(
                    'termination_reason'
                )->nullable();


                /*
                |--------------------------------------------------------------------------
                | Audit Fields
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'created_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId(
                    'updated_by'
                )
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
                    [
                        'employee_id',
                        'status',
                    ],
                    'employee_contract_employee_status_idx'
                );

                $table->index(
                    [
                        'end_date',
                        'status',
                    ],
                    'employee_contract_end_status_idx'
                );

                $table->index(
                    'renewal_due_date',
                    'employee_contract_renewal_due_idx'
                );

                $table->index(
                    'department_id',
                    'employee_contract_department_idx'
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
            'employee_contracts'
        );
    }
};
