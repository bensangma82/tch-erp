<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'pharmacy_stock_locations',
            function (Blueprint $table) {

                $table->id();

                $table
                    ->string('code', 50)
                    ->unique();

                $table
                    ->string('name', 150);

                $table
                    ->string('location_type', 50)
                    ->default('store');

                $table
                    ->boolean('is_active')
                    ->default(true);

                $table
                    ->text('remarks')
                    ->nullable();

                $table->timestamps();

                $table->index(
                    'location_type'
                );

                $table->index(
                    'is_active'
                );
            }
        );


        /*
        |--------------------------------------------------------------------------
        | Mandatory Default Stock Location
        |--------------------------------------------------------------------------
        |
        | Later migrations initialise existing pharmacy stock into
        | CENTRAL_STORE. Therefore this master record must exist as part
        | of the schema migration itself and must not depend on a seeder.
        |
        */

        DB::table(
            'pharmacy_stock_locations'
        )->insert([

            'code' =>
                'CENTRAL_STORE',

            'name' =>
                'Central Store',

            'location_type' =>
                'central_store',

            'is_active' =>
                true,

            'remarks' =>
                'Default central pharmacy store',

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ]);
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'pharmacy_stock_locations'
        );
    }
};