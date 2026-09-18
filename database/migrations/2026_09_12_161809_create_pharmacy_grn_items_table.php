<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_grn_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pharmacy_grn_id')
                ->constrained('pharmacy_grns')
                ->cascadeOnDelete();

            $table->foreignId('pharmacy_purchase_order_item_id')
                ->constrained('pharmacy_purchase_order_items')
                ->restrictOnDelete();

            $table->foreignId('medicine_id')
                ->constrained('medicines')
                ->restrictOnDelete();

            $table->foreignId('pharmacy_stock_batch_id')
                ->nullable()
                ->constrained('pharmacy_stock_batches')
                ->nullOnDelete();

            $table->string('medicine_code')->nullable();
            $table->string('medicine_name');
            $table->string('brand_name')->nullable();
            $table->string('strength')->nullable();
            $table->string('unit')->nullable();

            $table->string('batch_number');

            $table->date('expiry_date')->nullable();

            $table->integer('quantity_received');

            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);

            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);

            $table->decimal('gst_percent', 5, 2)->default(0);

            $table->decimal('taxable_amount', 14, 2)->default(0);
            $table->decimal('cgst_amount', 14, 2)->default(0);
            $table->decimal('sgst_amount', 14, 2)->default(0);
            $table->decimal('igst_amount', 14, 2)->default(0);

            $table->decimal('line_total', 14, 2)->default(0);

            $table->timestamps();

            $table->index('pharmacy_grn_id');
            $table->index('pharmacy_purchase_order_item_id');
            $table->index('medicine_id');
            $table->index('batch_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_grn_items');
    }
};