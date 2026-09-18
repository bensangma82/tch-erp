<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {

            $table->id();

            $table->string('code')
                ->unique();

            $table->string('name');

            /*
            |--------------------------------------------------------------------------
            | Service Category
            |--------------------------------------------------------------------------
            |
            | laboratory
            | radiology
            | procedure
            | consultation
            | other
            |
            */

            $table->string('category');

            /*
            |--------------------------------------------------------------------------
            | Optional Department
            |--------------------------------------------------------------------------
            */

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Pricing
            |--------------------------------------------------------------------------
            */

            $table->decimal('price', 10, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | Flags
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_active')
                ->default(true);

            $table->boolean('requires_sample')
                ->default(false);

            $table->boolean('requires_report')
                ->default(true);

            /*
            |--------------------------------------------------------------------------
            | Display / Notes
            |--------------------------------------------------------------------------
            */

            $table->string('unit')
                ->nullable();

            $table->text('description')
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('category');
            $table->index('department_id');
            $table->index('is_active');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};