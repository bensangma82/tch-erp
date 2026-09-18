<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'pharmacy_stock_transfer_items',
            function (Blueprint $table) {

                $table->id();

                $table->foreignId(
                    'pharmacy_stock_transfer_id'
                )
                    ->constrained(
                        'pharmacy_stock_transfers'
                    )
                    ->cascadeOnDelete();

                $table->foreignId(
                    'pharmacy_stock_batch_id'
                )
                    ->constrained(
                        'pharmacy_stock_batches'
                    )
                    ->restrictOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Snapshot fields
                |--------------------------------------------------------------------------
                |
                | Preserve what was transferred even if medicine master data
                | changes later.
                |
                */

                $table->string(
                    'medicine_code',
                    100
                )->nullable();

                $table->string(
                    'medicine_name',
                    255
                );

                $table->string(
                    'brand_name',
                    255
                )->nullable();

                $table->string(
                    'strength',
                    100
                )->nullable();

                $table->string(
                    'unit',
                    100
                )->nullable();

                $table->string(
                    'batch_number',
                    100
                );

                $table->date(
                    'expiry_date'
                )->nullable();

                /*
                |--------------------------------------------------------------------------
                | Quantities
                |--------------------------------------------------------------------------
                */

                $table->integer(
                    'quantity_requested'
                )->default(0);

                $table->integer(
                    'quantity_issued'
                )->default(0);

                $table->integer(
                    'quantity_received'
                )->default(0);

                $table->text(
                    'remarks'
                )->nullable();

                $table->timestamps();

                $table->unique(
                    [
                        'pharmacy_stock_transfer_id',
                        'pharmacy_stock_batch_id',
                    ],
                    'pharmacy_transfer_batch_unique'
                );

                $table->index(
                    'pharmacy_stock_transfer_id'
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
            'pharmacy_stock_transfer_items'
        );
    }
};