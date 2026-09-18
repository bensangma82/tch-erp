<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'pharmacy_stock_audits',
            function (Blueprint $table) {

                $table
                    ->foreignId('pharmacy_stock_location_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('pharmacy_stock_locations')
                    ->restrictOnDelete();

                $table->index(
                    [
                        'pharmacy_stock_location_id',
                        'status',
                    ],
                    'pharmacy_stock_audits_location_status_index'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::table(
            'pharmacy_stock_audits',
            function (Blueprint $table) {

                $table->dropIndex(
                    'pharmacy_stock_audits_location_status_index'
                );

                $table->dropConstrainedForeignId(
                    'pharmacy_stock_location_id'
                );
            }
        );
    }
};