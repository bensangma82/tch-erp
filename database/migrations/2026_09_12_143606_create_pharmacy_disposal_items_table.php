<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_disposal_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pharmacy_disposal_id')
                ->constrained('pharmacy_disposals')
                ->cascadeOnDelete();

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

            $table->string('batch_number');

            $table->date('expiry_date')->nullable();

            $table->integer('quantity');

            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('stock_value', 12, 2)->default(0);

            $table->string('reason');

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('pharmacy_disposal_id');
            $table->index('medicine_id');
            $table->index('pharmacy_stock_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_disposal_items');
    }
};