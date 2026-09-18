<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $centralStore = DB::table(
            'pharmacy_stock_locations'
        )
            ->where(
                'code',
                'CENTRAL_STORE'
            )
            ->first();


        if (! $centralStore) {
            throw new RuntimeException(
                'Central Store stock location does not exist.'
            );
        }


        $batches =
            DB::table(
                'pharmacy_stock_batches'
            )
                ->select([
                    'id',
                    'quantity_available',
                    'reorder_level',
                ])
                ->get();


        foreach ($batches as $batch) {

            DB::table(
                'pharmacy_stock_location_balances'
            )
                ->updateOrInsert(
                    [
                        'pharmacy_stock_location_id' =>
                            $centralStore->id,

                        'pharmacy_stock_batch_id' =>
                            $batch->id,
                    ],
                    [
                        'quantity_available' =>
                            (int) $batch->quantity_available,

                        'reorder_level' =>
                            (int) $batch->reorder_level,

                        'created_at' =>
                            now(),

                        'updated_at' =>
                            now(),
                    ]
                );
        }
    }


    public function down(): void
    {
        $centralStoreId =
            DB::table(
                'pharmacy_stock_locations'
            )
                ->where(
                    'code',
                    'CENTRAL_STORE'
                )
                ->value('id');


        if ($centralStoreId) {

            DB::table(
                'pharmacy_stock_location_balances'
            )
                ->where(
                    'pharmacy_stock_location_id',
                    $centralStoreId
                )
                ->delete();
        }
    }
};