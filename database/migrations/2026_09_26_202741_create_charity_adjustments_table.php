<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'charity_adjustments',
            function (Blueprint $table) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Patient
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId('patient_id')
                    ->constrained()
                    ->restrictOnDelete();


                /*
                |--------------------------------------------------------------------------
                | OPD / Regular Invoice
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId('invoice_id')
                    ->nullable()
                    ->constrained()
                    ->restrictOnDelete();

                $table
                    ->foreignId('encounter_id')
                    ->nullable()
                    ->constrained()
                    ->restrictOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Inpatient Billing
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId('admission_id')
                    ->nullable()
                    ->constrained()
                    ->restrictOnDelete();

                $table
                    ->foreignId('ip_billing_account_id')
                    ->nullable()
                    ->constrained('ip_billing_accounts')
                    ->restrictOnDelete();


                /*
                |--------------------------------------------------------------------------
                | Adjustment
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'adjustment_type',
                        30
                    )
                    ->default('charity');

                $table
                    ->decimal(
                        'requested_amount',
                        12,
                        2
                    );

                $table
                    ->decimal(
                        'approved_amount',
                        12,
                        2
                    )
                    ->nullable();


                /*
                |--------------------------------------------------------------------------
                | Reason / Documentation
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'reason',
                        255
                    );

                $table
                    ->text('remarks')
                    ->nullable();


                /*
                |--------------------------------------------------------------------------
                | Workflow
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'status',
                        30
                    )
                    ->default('pending');

                $table
                    ->foreignId('requested_by')
                    ->constrained('users')
                    ->restrictOnDelete();

                $table
                    ->foreignId('approved_by')
                    ->nullable()
                    ->constrained('users')
                    ->restrictOnDelete();

                $table
                    ->timestamp('requested_at')
                    ->nullable();

                $table
                    ->timestamp('approved_at')
                    ->nullable();

                $table
                    ->timestamp('applied_at')
                    ->nullable();


                /*
                |--------------------------------------------------------------------------
                | Cancellation
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId('cancelled_by')
                    ->nullable()
                    ->constrained('users')
                    ->restrictOnDelete();

                $table
                    ->timestamp('cancelled_at')
                    ->nullable();

                $table
                    ->text('cancellation_reason')
                    ->nullable();


                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | Indexes
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'patient_id',
                        'status',
                    ]
                );

                $table->index(
                    [
                        'invoice_id',
                        'status',
                    ]
                );

                $table->index(
                    [
                        'ip_billing_account_id',
                        'status',
                    ],
                    'charity_ip_account_status_index'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'charity_adjustments'
        );
    }
};