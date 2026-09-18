<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ward_id')
                ->constrained('wards')
                ->restrictOnDelete();

            $table->string('bed_number', 50);

            $table->string('bed_type', 50)
                ->default('general');

            $table->string('status', 30)
                ->default('available');

            $table->boolean('is_active')
                ->default(true);

            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            $table->unique(
                ['ward_id', 'bed_number'],
                'beds_ward_number_unique'
            );

            $table->index('status');
            $table->index('bed_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beds');
    }
};