<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'pharmacy_stock_location_balances',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId(
                    'pharmacy_stock_location_id'
                )
                    ->constrained(
                        'pharmacy_stock_locations'
                    )
                    ->restrictOnDelete();

                $table->foreignId(
                    'pharmacy_stock_batch_id'
                )
                    ->constrained(
                        'pharmacy_stock_batches'
                    )
                    ->restrictOnDelete();

                $table->integer(
                    'quantity_available'
                )->default(0);

                $table->integer(
                    'reorder_level'
                )->default(0);

                $table->timestamps();

                $table->unique(
                    [
                        'pharmacy_stock_location_id',
                        'pharmacy_stock_batch_id',
                    ],
                    'pharmacy_location_batch_unique'
                );

                $table->index(
                    'pharmacy_stock_location_id'
                );

                $table->index(
                    'pharmacy_stock_batch_id'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'pharmacy_stock_location_balances'
        );
    }
};