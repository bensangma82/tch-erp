<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_return_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pharmacy_return_id')
                ->constrained('pharmacy_returns')
                ->cascadeOnDelete();

            $table->foreignId('pharmacy_sale_item_id')
                ->constrained('pharmacy_sale_items')
                ->restrictOnDelete();

            $table->foreignId('medicine_id')
                ->constrained('medicines')
                ->restrictOnDelete();

            $table->foreignId('pharmacy_stock_batch_id')
                ->constrained('pharmacy_stock_batches')
                ->restrictOnDelete();

            $table->string('medicine_code')->nullable();
            $table->string('medicine_name');
            $table->string('brand_name')->nullable();
            $table->string('strength')->nullable();
            $table->string('unit')->nullable();

            $table->string('hsn_code')->nullable();

            $table->string('batch_number');

            $table->integer('quantity');

            $table->decimal('unit_price', 12, 2)->default(0);

            $table->decimal('gst_percent', 5, 2)->default(0);

            $table->decimal('gross_amount', 12, 2)->default(0);
            $table->decimal('taxable_amount', 12, 2)->default(0);
            $table->decimal('cgst_amount', 12, 2)->default(0);
            $table->decimal('sgst_amount', 12, 2)->default(0);
            $table->decimal('igst_amount', 12, 2)->default(0);

            $table->decimal('refund_amount', 12, 2)->default(0);

            $table->timestamps();

            $table->index('pharmacy_return_id');
            $table->index('pharmacy_sale_item_id');
            $table->index('medicine_id');
            $table->index('pharmacy_stock_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_return_items');
    }
};