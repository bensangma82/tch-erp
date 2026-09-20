<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ip_billing_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ip_billing_account_id')
                ->constrained('ip_billing_accounts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('admission_id')
                ->constrained('admissions')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('receipt_no')->unique();

            $table->dateTime('payment_date');

            $table->decimal('amount', 12, 2);

            $table->string('payment_mode', 50);

            $table->string('transaction_reference')
                ->nullable();

            $table->text('remarks')
                ->nullable();

            $table->string('status', 20)
                ->default('active');

            $table->foreignId('received_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('cancelled_at')
                ->nullable();

            $table->text('cancellation_reason')
                ->nullable();

            $table->timestamps();

            $table->index([
                'ip_billing_account_id',
                'status',
            ]);

            $table->index([
                'admission_id',
                'status',
            ]);

            $table->index([
                'patient_id',
                'payment_date',
            ]);

            $table->index([
                'payment_date',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_billing_payments');
    }
};