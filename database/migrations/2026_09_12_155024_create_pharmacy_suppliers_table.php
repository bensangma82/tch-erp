<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_suppliers', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();

            $table->string('name');

            $table->string('contact_person')->nullable();

            $table->string('phone', 30)->nullable();

            $table->string('alternate_phone', 30)->nullable();

            $table->string('email')->nullable();

            $table->text('address')->nullable();

            $table->string('city')->nullable();

            $table->string('district')->nullable();

            $table->string('state')->nullable();

            $table->string('pin_code', 10)->nullable();

            $table->string('gstin', 20)->nullable();

            $table->string('drug_license_no')->nullable();

            $table->string('pan_no', 20)->nullable();

            $table->unsignedInteger('credit_days')->default(0);

            $table->boolean('is_active')->default(true);

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('name');
            $table->index('is_active');
            $table->index('gstin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_suppliers');
    }
};