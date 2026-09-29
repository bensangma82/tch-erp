<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_billing_mhis_adjustments', function (Blueprint $table) {

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

            $table->decimal('amount', 12, 2);

            $table->dateTime('adjustment_date');

            $table->text('reason');

            $table->text('remarks')
                ->nullable();

            $table->string('status')
                ->default('active');

            $table->foreignId('applied_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('cancelled_at')
                ->nullable();

            $table->text('cancellation_reason')
                ->nullable();

            $table->timestamps();

            $table->index('ip_billing_account_id');
            $table->index('admission_id');
            $table->index('patient_id');
            $table->index('adjustment_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_billing_mhis_adjustments');
    }
};