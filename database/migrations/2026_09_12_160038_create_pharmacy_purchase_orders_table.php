<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_purchase_orders', function (Blueprint $table) {
            $table->id();

            $table->string('po_no')->unique();

            $table->foreignId('pharmacy_supplier_id')
                ->constrained('pharmacy_suppliers')
                ->restrictOnDelete();

            $table->date('po_date');

            $table->date('expected_delivery_date')->nullable();

            $table->string('status')->default('draft');

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

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->timestamps();

            $table->index('po_date');
            $table->index('status');
            $table->index('pharmacy_supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_purchase_orders');
    }
};