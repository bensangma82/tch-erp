<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {

            // How the medicine is normally purchased
            // Examples: Strip, Box, Bottle, Vial
            $table->string('purchase_pack')
                ->nullable()
                ->after('unit');

            // Number of base/dispensing units in one purchase pack
            // Example: 15 tablets per strip
            $table->integer('units_per_pack')
                ->default(1)
                ->after('purchase_pack');

            // Default supplier purchase rate for one pack
            $table->decimal('default_purchase_price', 12, 2)
                ->default(0)
                ->after('units_per_pack');

            // Maximum Retail Price for one complete pack
            $table->decimal('mrp_per_pack', 12, 2)
                ->default(0)
                ->after('default_purchase_price');
        });
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropColumn([
                'purchase_pack',
                'units_per_pack',
                'default_purchase_price',
                'mrp_per_pack',
            ]);
        });
    }
};