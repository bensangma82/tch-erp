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
        Schema::create('bed_tariffs', function (Blueprint $table) {

            $table->id();

            $table->foreignId('ward_id')
                ->constrained('wards')
                ->cascadeOnDelete();

            $table->string('bed_type');

            $table->decimal('rate_per_day', 12, 2);

            $table->date('effective_from');

            $table->date('effective_to')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->text('remarks')
                ->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('ward_id');
            $table->index('bed_type');
            $table->index('effective_from');
            $table->index('effective_to');
            $table->index('is_active');

            $table->index([
                'ward_id',
                'bed_type',
                'is_active',
            ]);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bed_tariffs');
    }
};