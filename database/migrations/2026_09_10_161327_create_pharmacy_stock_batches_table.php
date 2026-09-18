<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_stock_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('medicine_id')
                ->constrained('medicines')
                ->restrictOnDelete();

            $table->string('batch_number', 100);

            $table->date('expiry_date')
                ->nullable();

            $table->decimal(
                'purchase_price',
                14,
                2
            )->default(0);

            $table->decimal(
                'selling_price',
                14,
                2
            )->default(0);

            $table->integer(
                'quantity_received'
            )->default(0);

            $table->integer(
                'quantity_available'
            )->default(0);

            $table->integer(
                'reorder_level'
            )->default(0);

            $table->date(
                'received_date'
            );

            $table->boolean(
                'is_active'
            )->default(true);

            $table->timestamps();

            $table->unique(
                [
                    'medicine_id',
                    'batch_number',
                ],
                'pharmacy_stock_batches_medicine_batch_unique'
            );

            $table->index(
                'expiry_date'
            );

            $table->index(
                'received_date'
            );

            $table->index(
                'is_active'
            );
        });
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'pharmacy_stock_batches'
        );
    }
};