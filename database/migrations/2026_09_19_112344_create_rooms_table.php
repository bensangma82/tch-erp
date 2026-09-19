<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ward_id')
                ->constrained('wards')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('code', 50);
            $table->string('name', 100);

            $table->string('room_type', 50)
                ->default('cabin');

            $table->string('floor', 50)
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            $table->unique(
                ['ward_id', 'code'],
                'rooms_ward_code_unique'
            );

            $table->index(
                ['ward_id', 'is_active'],
                'rooms_ward_active_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};