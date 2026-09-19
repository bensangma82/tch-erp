<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_grn_items', function (Blueprint $table) {

            // Number of purchase packs received
            $table->integer('purchase_qty')
                ->default(0)
                ->after('expiry_date');

            // Free / bonus packs supplied
            $table->integer('bonus_qty')
                ->default(0)
                ->after('purchase_qty');

            // Number of smallest dispensable units in one pack
            $table->integer('units_per_pack')
                ->default(1)
                ->after('bonus_qty');

            // Total stock received in base units:
            // (purchase_qty + bonus_qty) * units_per_pack
            $table->integer('received_units')
                ->default(0)
                ->after('units_per_pack');
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_grn_items', function (Blueprint $table) {
            $table->dropColumn([
                'purchase_qty',
                'bonus_qty',
                'units_per_pack',
                'received_units',
            ]);
        });
    }
};