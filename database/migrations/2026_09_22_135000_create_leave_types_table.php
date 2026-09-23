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
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)
                ->unique();

            $table->string('name', 120);

            $table->decimal(
                'default_annual_entitlement',
                5,
                1
            )->default(0);

            $table->boolean('is_paid')
                ->default(true);

            $table->boolean('allow_carry_forward')
                ->default(false);

            $table->decimal(
                'max_carry_forward',
                5,
                1
            )->default(0);

            $table->boolean('requires_approval')
                ->default(true);

            $table->boolean('is_active')
                ->default(true);

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->text('description')
                ->nullable();

            $table->timestamps();

            $table->index([
                'is_active',
                'sort_order',
            ]);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
