<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('diagnostic_samples', function (Blueprint $table) {

            $table->id();

            $table->foreignId('service_order_item_id')
                ->unique()
                ->constrained('service_order_items')
                ->cascadeOnDelete();

            $table->string('sample_no')->unique();

            $table->string('specimen_type')->nullable();

            $table->timestamp('collected_at')->nullable();

            $table->foreignId('collected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status')->default('pending');

            $table->timestamp('rejected_at')->nullable();

            $table->foreignId('rejected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('rejection_reason')->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('collected_at');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnostic_samples');
    }
};