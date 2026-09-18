<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->string('hsn_code', 20)
                ->nullable()
                ->after('manufacturer');

            $table->decimal('gst_percent', 5, 2)
                ->default(0)
                ->after('hsn_code');

            $table->index('hsn_code');
            $table->index('gst_percent');
        });


        Schema::table('pharmacy_sale_items', function (Blueprint $table) {
            $table->decimal('gst_percent', 5, 2)
                ->default(0)
                ->after('unit_price');

            $table->decimal('taxable_amount', 12, 2)
                ->default(0)
                ->after('gst_percent');

            $table->decimal('cgst_amount', 12, 2)
                ->default(0)
                ->after('taxable_amount');

            $table->decimal('sgst_amount', 12, 2)
                ->default(0)
                ->after('cgst_amount');

            $table->decimal('igst_amount', 12, 2)
                ->default(0)
                ->after('sgst_amount');
        });


        Schema::table('pharmacy_sales', function (Blueprint $table) {
            $table->decimal('taxable_amount', 12, 2)
                ->default(0)
                ->after('discount');

            $table->decimal('cgst_amount', 12, 2)
                ->default(0)
                ->after('taxable_amount');

            $table->decimal('sgst_amount', 12, 2)
                ->default(0)
                ->after('cgst_amount');

            $table->decimal('igst_amount', 12, 2)
                ->default(0)
                ->after('sgst_amount');
        });
    }


    public function down(): void
    {
        Schema::table('pharmacy_sales', function (Blueprint $table) {
            $table->dropColumn([
                'taxable_amount',
                'cgst_amount',
                'sgst_amount',
                'igst_amount',
            ]);
        });


        Schema::table('pharmacy_sale_items', function (Blueprint $table) {
            $table->dropColumn([
                'gst_percent',
                'taxable_amount',
                'cgst_amount',
                'sgst_amount',
                'igst_amount',
            ]);
        });


        Schema::table('medicines', function (Blueprint $table) {
            $table->dropIndex([
                'hsn_code',
            ]);

            $table->dropIndex([
                'gst_percent',
            ]);

            $table->dropColumn([
                'hsn_code',
                'gst_percent',
            ]);
        });
    }
};