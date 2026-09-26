<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'staff_medical_benefit_transactions',
            function (Blueprint $table) {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Benefit Account
                |--------------------------------------------------------------------------
                */

                $table->foreignId('benefit_account_id')
                    ->constrained(
                        'staff_medical_benefit_accounts'
                    )
                    ->restrictOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Beneficiary
                |--------------------------------------------------------------------------
                |
                | beneficiary_type:
                | employee  = employee's ₹50,000 pool
                | dependent = shared ₹25,000 dependent-family pool
                |
                */

                $table->string(
                    'beneficiary_type',
                    20
                );

                $table->foreignId('dependent_id')
                    ->nullable()
                    ->constrained('employee_dependents')
                    ->restrictOnDelete();

                $table->foreignId('patient_id')
                    ->constrained('patients')
                    ->restrictOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Transaction
                |--------------------------------------------------------------------------
                |
                | utilization = consumes entitlement
                | reversal    = restores entitlement
                | adjustment  = authorized manual correction
                |
                */

                $table->string(
                    'transaction_type',
                    30
                );

                $table->date('transaction_date');

                $table->decimal(
                    'amount',
                    12,
                    2
                );

                /*
                |--------------------------------------------------------------------------
                | Source
                |--------------------------------------------------------------------------
                |
                | Examples:
                | invoice
                | ip_billing
                | pharmacy_sale
                | manual
                |
                | source_type + source_id allow the benefit ledger to link
                | to different ERP billing modules without creating a
                | separate benefit table for each module.
                |
                */

                $table->string(
                    'source_type',
                    50
                );

                $table->unsignedBigInteger(
                    'source_id'
                )->nullable();

                $table->string(
                    'source_reference',
                    150
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | MHIS Coordination
                |--------------------------------------------------------------------------
                |
                | These are snapshots for audit/reporting. MHIS itself remains
                | managed by the existing IP Billing MHIS claim system.
                |
                */

                $table->decimal(
                    'gross_bill_amount',
                    12,
                    2
                )->nullable();

                $table->decimal(
                    'mhis_approved_amount',
                    12,
                    2
                )->nullable();

                $table->decimal(
                    'residual_before_benefit',
                    12,
                    2
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Reversal Link
                |--------------------------------------------------------------------------
                */

                $table->foreignId(
                    'reverses_transaction_id'
                )
                    ->nullable()
                    ->constrained(
                        'staff_medical_benefit_transactions'
                    )
                    ->restrictOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Status / Reason
                |--------------------------------------------------------------------------
                */

                $table->string('status', 20)
                    ->default('active');

                $table->string(
                    'reason',
                    255
                )->nullable();

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
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'benefit_account_id',
                        'beneficiary_type',
                        'status',
                    ],
                    'staff_med_benefit_tx_account_pool_index'
                );

                $table->index(
                    [
                        'source_type',
                        'source_id',
                    ],
                    'staff_med_benefit_tx_source_index'
                );

                $table->index(
                    [
                        'patient_id',
                        'transaction_date',
                    ],
                    'staff_med_benefit_tx_patient_date_index'
                );

                $table->index(
                    'reverses_transaction_id',
                    'staff_med_benefit_tx_reversal_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'staff_medical_benefit_transactions'
        );
    }
};