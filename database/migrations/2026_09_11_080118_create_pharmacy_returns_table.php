<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_returns', function (Blueprint $table) {
            $table->id();

            $table->string('return_no')->unique();

            $table->foreignId('pharmacy_sale_id')
                ->constrained('pharmacy_sales')
                ->restrictOnDelete();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->restrictOnDelete();

            $table->foreignId('encounter_id')
                ->nullable()
                ->constrained('encounters')
                ->nullOnDelete();

            $table->dateTime('returned_at');

            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->decimal('taxable_amount', 12, 2)->default(0);
            $table->decimal('cgst_amount', 12, 2)->default(0);
            $table->decimal('sgst_amount', 12, 2)->default(0);
            $table->decimal('igst_amount', 12, 2)->default(0);
            $table->decimal('refund_amount', 12, 2)->default(0);

            $table->string('refund_mode')->nullable();
            $table->string('transaction_reference')->nullable();

            $table->string('reason');
            $table->text('remarks')->nullable();

            $table->string('status')->default('completed');

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index('returned_at');
            $table->index('status');
            $table->index('pharmacy_sale_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_returns');
    }
};