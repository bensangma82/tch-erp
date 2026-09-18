<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_purchase_returns', function (Blueprint $table) {
            $table->id();

            $table->string('return_no')->unique();

            $table->foreignId('pharmacy_supplier_id')
                ->constrained('pharmacy_suppliers')
                ->restrictOnDelete();

            $table->date('return_date');

            $table->string('supplier_credit_note_no')->nullable();
            $table->date('supplier_credit_note_date')->nullable();

            $table->string('reason')->nullable();

            $table->string('status')->default('completed');

            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('taxable_amount', 14, 2)->default(0);
            $table->decimal('cgst_amount', 14, 2)->default(0);
            $table->decimal('sgst_amount', 14, 2)->default(0);
            $table->decimal('igst_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index('return_date');
            $table->index('pharmacy_supplier_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_purchase_returns');
    }
};