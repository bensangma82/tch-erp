<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_grns', function (Blueprint $table) {
            $table->id();

            $table->string('grn_no')->unique();

            $table->foreignId('pharmacy_purchase_order_id')
                ->constrained('pharmacy_purchase_orders')
                ->restrictOnDelete();

            $table->foreignId('pharmacy_supplier_id')
                ->constrained('pharmacy_suppliers')
                ->restrictOnDelete();

            $table->date('grn_date');

            $table->string('supplier_invoice_no')->nullable();
            $table->date('supplier_invoice_date')->nullable();

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

            $table->index('grn_date');
            $table->index('pharmacy_purchase_order_id');
            $table->index('pharmacy_supplier_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_grns');
    }
};