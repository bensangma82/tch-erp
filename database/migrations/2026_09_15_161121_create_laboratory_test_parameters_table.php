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
        Schema::create('laboratory_test_parameters', function (Blueprint $table) {

            $table->id();

            $table->foreignId('service_id')
                ->constrained('services')
                ->cascadeOnDelete();

            $table->string('parameter_name');

            $table->string('unit')->nullable();

            $table->string('reference_range')->nullable();

            $table->decimal('low_value', 12, 4)->nullable();

            $table->decimal('high_value', 12, 4)->nullable();

            $table->decimal('critical_low', 12, 4)->nullable();

            $table->decimal('critical_high', 12, 4)->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index([
                'service_id',
                'is_active',
            ]);

            $table->index([
                'service_id',
                'sort_order',
            ]);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laboratory_test_parameters');
    }
};