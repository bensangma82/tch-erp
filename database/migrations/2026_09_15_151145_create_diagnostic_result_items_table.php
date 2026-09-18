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
        Schema::create('diagnostic_result_items', function (Blueprint $table) {

            $table->id();

            $table->foreignId('diagnostic_result_id')
                ->constrained('diagnostic_results')
                ->cascadeOnDelete();

            $table->string('parameter_name');

            $table->string('result_value')->nullable();

            $table->string('unit')->nullable();

            $table->string('reference_range')->nullable();

            $table->string('flag')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('diagnostic_result_id');
            $table->index('flag');
            $table->index('sort_order');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnostic_result_items');
    }
};