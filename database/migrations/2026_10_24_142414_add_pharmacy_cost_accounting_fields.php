<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_stock_batches', function (Blueprint $table) {
            $table->decimal('purchase_price', 14, 4)
                ->default(0)
                ->change();
        });

        Schema::table('pharmacy_sale_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 14, 4)
                ->nullable();

            $table->decimal('total_cost', 16, 4)
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_sale_items', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'total_cost']);
        });

        Schema::table('pharmacy_stock_batches', function (Blueprint $table) {
            $table->decimal('purchase_price', 14, 2)
                ->default(0)
                ->change();
        });
    }
};
