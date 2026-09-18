<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_billing_mhis_claims', function (Blueprint $table) {

            $table->id();

            $table->foreignId('ip_billing_account_id')
                ->constrained('ip_billing_accounts')
                ->cascadeOnDelete();

            $table->foreignId('admission_id')
                ->constrained('admissions')
                ->cascadeOnDelete();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnDelete();

            $table->string('claim_no')->nullable();

            $table->string('authorization_no')->nullable();

            $table->string('package_code')->nullable();

            $table->string('package_name')->nullable();

            $table->decimal('claim_amount', 12, 2)
                ->default(0);

            $table->decimal('approved_amount', 12, 2)
                ->default(0);

            $table->decimal('settlement_amount', 12, 2)
                ->default(0);

            $table->string('status')
                ->default('pending');

            $table->date('approval_date')
                ->nullable();

            $table->date('settlement_date')
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


            $table->index('ip_billing_account_id');

            $table->index('admission_id');

            $table->index('patient_id');

            $table->index('claim_no');

            $table->index('authorization_no');

            $table->index('status');

        });
    }


    public function down(): void
    {
        Schema::dropIfExists('ip_billing_mhis_claims');
    }
};