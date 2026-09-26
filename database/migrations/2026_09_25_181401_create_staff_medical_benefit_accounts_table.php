<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'staff_medical_benefit_accounts',
            function (Blueprint $table) {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Employee and Policy
                |--------------------------------------------------------------------------
                */

                $table->foreignId('employee_id')
                    ->constrained('employees')
                    ->cascadeOnDelete();

                $table->foreignId('policy_id')
                    ->constrained(
                        'staff_medical_benefit_policies'
                    )
                    ->restrictOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Financial Year
                |--------------------------------------------------------------------------
                */

                $table->date('financial_year_start');
                $table->date('financial_year_end');

                /*
                |--------------------------------------------------------------------------
                | Entitlement Snapshot
                |--------------------------------------------------------------------------
                |
                | These values are copied from the policy when the account is
                | created. This preserves the employee's historical entitlement
                | even if a policy is changed later.
                |
                */

                $table->decimal(
                    'employee_entitlement',
                    12,
                    2
                );

                $table->decimal(
                    'dependent_family_entitlement',
                    12,
                    2
                );

                /*
                |--------------------------------------------------------------------------
                | Eligibility Period
                |--------------------------------------------------------------------------
                |
                | Full annual entitlement is allowed even if eligibility starts
                | during the financial year. These dates control when the
                | benefit may actually be used.
                |
                */

                $table->date('eligible_from');

                $table->date('eligible_until')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                */

                $table->string('status', 30)
                    ->default('active');

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
                        'employee_id',
                        'financial_year_start',
                        'financial_year_end',
                    ],
                    'staff_med_benefit_account_employee_fy_unique'
                );

                $table->index(
                    [
                        'employee_id',
                        'status',
                    ],
                    'staff_med_benefit_account_employee_status_index'
                );

                $table->index(
                    [
                        'financial_year_start',
                        'financial_year_end',
                    ],
                    'staff_med_benefit_account_fy_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'staff_medical_benefit_accounts'
        );
    }
};