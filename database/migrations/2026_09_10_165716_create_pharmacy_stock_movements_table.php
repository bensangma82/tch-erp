<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_stock_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pharmacy_stock_batch_id')
                ->constrained('pharmacy_stock_batches')
                ->cascadeOnDelete();

            $table->string('movement_type', 50);

            $table->integer('quantity');

            $table->integer('balance_after');

            $table->string('reference_type', 100)->nullable();

            $table->unsignedBigInteger('reference_id')->nullable();

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('movement_at')->useCurrent();

            $table->timestamps();

            $table->index('movement_type');
            $table->index('movement_at');
            $table->index([
                'reference_type',
                'reference_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_stock_movements');
    }
};