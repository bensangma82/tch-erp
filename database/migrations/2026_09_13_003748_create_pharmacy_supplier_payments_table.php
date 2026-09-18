<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_supplier_payments', function (Blueprint $table) {
            $table->id();

            $table->string('payment_no')->unique();

            $table->foreignId('pharmacy_supplier_payable_id')
                ->constrained('pharmacy_supplier_payables')
                ->restrictOnDelete();

            $table->foreignId('pharmacy_supplier_id')
                ->constrained('pharmacy_suppliers')
                ->restrictOnDelete();

            $table->date('payment_date');

            $table->decimal('amount', 14, 2);

            $table->string('payment_method');

            $table->string('reference_no')->nullable();

            $table->string('bank_name')->nullable();

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index('payment_date');
            $table->index('payment_method');
            $table->index('pharmacy_supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_supplier_payments');
    }
};