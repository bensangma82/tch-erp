<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {

            $table->id();

            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->cascadeOnDelete();

            $table->foreignId('service_order_item_id')
                ->nullable()
                ->constrained('service_order_items')
                ->nullOnDelete();

            $table->foreignId('service_id')
                ->nullable()
                ->constrained('services')
                ->nullOnDelete();

            $table->string('code')
                ->nullable();

            $table->string('description');

            $table->unsignedInteger('quantity')
                ->default(1);

            $table->decimal('unit_price', 10, 2);

            $table->decimal('discount', 10, 2)
                ->default(0);

            $table->decimal('amount', 10, 2);

            $table->timestamps();

            $table->index('invoice_id');
            $table->index('service_order_item_id');
            $table->index('service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};