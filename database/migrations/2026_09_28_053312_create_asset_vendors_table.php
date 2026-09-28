<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_vendors', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)->unique();
            $table->string('name', 150);

            $table->string('contact_person', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('alternate_phone', 30)->nullable();
            $table->string('email', 150)->nullable();

            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pin_code', 20)->nullable();

            $table->string('gstin', 30)->nullable();
            $table->string('pan_no', 30)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Vendor Capability
            |--------------------------------------------------------------------------
            */

            $table->boolean('provides_sales')->default(true);
            $table->boolean('provides_service')->default(false);
            $table->boolean('provides_amc_cmc')->default(false);
            $table->boolean('provides_calibration')->default(false);

            $table->text('remarks')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_vendors');
    }
};