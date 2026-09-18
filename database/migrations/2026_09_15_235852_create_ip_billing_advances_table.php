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
        Schema::create('ip_billing_advances', function (Blueprint $table) {

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

            $table->string('receipt_no')
                ->unique();

            $table->dateTime('payment_date');

            $table->decimal('amount', 12, 2);

            $table->string('payment_mode');

            $table->string('transaction_reference')
                ->nullable();

            $table->text('remarks')
                ->nullable();

            $table->string('status')
                ->default('active');

            $table->foreignId('received_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('cancelled_at')
                ->nullable();

            $table->timestamps();

            $table->index('ip_billing_account_id');
            $table->index('admission_id');
            $table->index('patient_id');
            $table->index('payment_date');
            $table->index('payment_mode');
            $table->index('status');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_billing_advances');
    }
};