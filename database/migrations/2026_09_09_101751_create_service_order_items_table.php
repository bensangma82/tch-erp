<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_order_items', function (Blueprint $table) {

            $table->id();

            $table->foreignId('service_order_id')
                ->constrained('service_orders')
                ->cascadeOnDelete();

            $table->foreignId('service_id')
                ->constrained('services')
                ->restrictOnDelete();

            $table->string('service_code');

            $table->string('service_name');

            $table->string('category');

            $table->unsignedInteger('quantity')
                ->default(1);

            $table->decimal('unit_price', 10, 2);

            $table->decimal('amount', 10, 2);

            $table->string('status')
                ->default('ordered');

            $table->boolean('requires_sample')
                ->default(false);

            $table->boolean('requires_report')
                ->default(true);

            $table->timestamps();

            $table->index('service_order_id');
            $table->index('service_id');
            $table->index('category');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_order_items');
    }
};