<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_sale_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pharmacy_sale_id')
                ->constrained('pharmacy_sales')
                ->cascadeOnDelete();

            $table->foreignId('medicine_id')
                ->constrained('medicines')
                ->restrictOnDelete();

            $table->foreignId('pharmacy_stock_batch_id')
                ->constrained('pharmacy_stock_batches')
                ->restrictOnDelete();

            $table->string('medicine_code', 50);

            $table->string('medicine_name');

            $table->string('brand_name')->nullable();

            $table->string('strength')->nullable();

            $table->string('unit')->nullable();

            $table->string('batch_number');

            $table->integer('quantity');

            $table->decimal('unit_price', 12, 2);

            $table->decimal('amount', 12, 2);

            $table->timestamps();

            $table->index('medicine_id');
            $table->index('pharmacy_stock_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_sale_items');
    }
};