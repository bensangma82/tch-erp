<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();

            $table->string('generic_name');

            $table->string('brand_name')->nullable();

            $table->string('strength')->nullable();

            $table->string('dosage_form')->nullable();

            $table->string('manufacturer')->nullable();

            $table->string('unit')->default('tablet');

            $table->decimal('default_selling_price', 12, 2)->default(0);

            $table->boolean('is_active')->default(true);

            $table->text('description')->nullable();

            $table->timestamps();

            $table->index('generic_name');
            $table->index('brand_name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};