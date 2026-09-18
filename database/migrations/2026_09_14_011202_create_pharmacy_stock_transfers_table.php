<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'pharmacy_stock_transfers',
            function (Blueprint $table) {

                $table->id();

                $table->string(
                    'transfer_no',
                    50
                )->unique();

                $table->date(
                    'transfer_date'
                );

                $table->foreignId(
                    'from_location_id'
                )
                    ->constrained(
                        'pharmacy_stock_locations'
                    )
                    ->restrictOnDelete();

                $table->foreignId(
                    'to_location_id'
                )
                    ->constrained(
                        'pharmacy_stock_locations'
                    )
                    ->restrictOnDelete();

                $table->string(
                    'status',
                    30
                )->default('draft');

                $table->text(
                    'remarks'
                )->nullable();

                $table->foreignId(
                    'created_by'
                )
                    ->constrained('users')
                    ->restrictOnDelete();

                $table->foreignId(
                    'issued_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp(
                    'issued_at'
                )->nullable();

                $table->foreignId(
                    'received_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp(
                    'received_at'
                )->nullable();

                $table->foreignId(
                    'cancelled_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp(
                    'cancelled_at'
                )->nullable();

                $table->text(
                    'cancellation_reason'
                )->nullable();

                $table->timestamps();

                $table->index(
                    'transfer_date'
                );

                $table->index(
                    'status'
                );

                $table->index(
                    'from_location_id'
                );

                $table->index(
                    'to_location_id'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'pharmacy_stock_transfers'
        );
    }
};