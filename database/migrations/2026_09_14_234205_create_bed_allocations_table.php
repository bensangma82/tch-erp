<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bed_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('admission_id')
                ->constrained('admissions')
                ->cascadeOnDelete();

            $table->foreignId('bed_id')
                ->constrained('beds')
                ->restrictOnDelete();

            $table->timestamp('allocated_at');

            $table->timestamp('released_at')
                ->nullable();

            $table->string('status', 30)
                ->default('active');

            $table->string('allocation_type', 50)
                ->default('admission');

            $table->text('remarks')
                ->nullable();

            $table->foreignId('allocated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('released_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('status');
            $table->index('allocated_at');
            $table->index('released_at');

            $table->index(
                ['admission_id', 'status'],
                'bed_allocations_admission_status_index'
            );

            $table->index(
                ['bed_id', 'status'],
                'bed_allocations_bed_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bed_allocations');
    }
};