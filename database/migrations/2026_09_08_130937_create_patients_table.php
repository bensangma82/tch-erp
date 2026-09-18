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
        Schema::create('patients', function (Blueprint $table) {
    $table->id();

    $table->string('uhid')->unique();

    $table->string('title')->nullable();

    $table->string('first_name');
    $table->string('middle_name')->nullable();
    $table->string('last_name')->nullable();

    $table->date('date_of_birth')->nullable();
    $table->unsignedSmallInteger('age')->nullable();

    $table->string('sex', 20);

    $table->string('phone', 20)->nullable();
    $table->string('alternate_phone', 20)->nullable();

    $table->string('email')->nullable();

    $table->text('address')->nullable();

    $table->string('locality')->nullable();
    $table->string('district')->nullable();
    $table->string('state')->nullable();
    $table->string('pin_code', 10)->nullable();

    $table->string('blood_group', 10)->nullable();

    $table->string('emergency_contact_name')->nullable();
    $table->string('emergency_contact_phone', 20)->nullable();
    $table->string('emergency_contact_relation')->nullable();

    $table->string('abha_number')->nullable();
    $table->string('mhis_number')->nullable();

    $table->text('known_allergies')->nullable();

    $table->boolean('is_active')->default(true);

    $table->timestamps();

    $table->softDeletes();

    $table->index('phone');
    $table->index(['first_name', 'last_name']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
