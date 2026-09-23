<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_shifts', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)->unique();
            $table->string('name', 100);

            $table->time('start_time');
            $table->time('end_time');

            $table->unsignedSmallInteger('grace_minutes')->default(0);

            $table->unsignedSmallInteger('minimum_work_minutes')->nullable();

            $table->boolean('crosses_midnight')->default(false);

            $table->boolean('is_active')->default(true);

            $table->unsignedInteger('sort_order')->default(0);

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_shifts');
    }
};