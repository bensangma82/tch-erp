<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)->unique();
            $table->string('name', 150)->unique();

            $table->text('description')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Classification
            |--------------------------------------------------------------------------
            |
            | biomedical  - Medical/clinical equipment
            | it          - Computer/network/electronic equipment
            | electrical  - Electrical equipment
            | furniture   - Furniture and fixtures
            | vehicle     - Hospital vehicles
            | building    - Building/infrastructure assets
            | general     - Other hospital assets
            |
            */
            $table->string('asset_class', 30)->default('general');

            /*
            |--------------------------------------------------------------------------
            | Maintenance Defaults
            |--------------------------------------------------------------------------
            */

            $table->boolean('requires_preventive_maintenance')
                ->default(false);

            $table->boolean('requires_calibration')
                ->default(false);

            /*
            |--------------------------------------------------------------------------
            | Accounting / Useful Life
            |--------------------------------------------------------------------------
            */

            $table->unsignedSmallInteger('default_useful_life_years')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('asset_class');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_categories');
    }
};