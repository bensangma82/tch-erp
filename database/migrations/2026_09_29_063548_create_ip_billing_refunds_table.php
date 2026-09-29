<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_billing_refunds', function (Blueprint $table) {

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

            $table->string('refund_no')
                ->unique();

            $table->dateTime('refund_date');

            $table->decimal('amount', 12, 2);

            $table->string('payment_mode');

            $table->string('transaction_reference')
                ->nullable();

            $table->text('reason')
                ->nullable();

            $table->text('remarks')
                ->nullable();

            $table->string('status')
                ->default('active');

            $table->foreignId('refunded_by')
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
            $table->index('refund_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_billing_refunds');
    }
};