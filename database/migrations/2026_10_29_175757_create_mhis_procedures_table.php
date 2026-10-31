<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mhis_procedures', function (Blueprint $table) {
            $table->id();

            $table->string('procedure_code', 50)->unique();
            $table->string('specialty', 255)->nullable();
            $table->string('medical_surgical', 50)->nullable();

            $table->text('package_name')->nullable();
            $table->text('procedure_name')->nullable();

            $table->decimal('procedure_price', 12, 2)->nullable();

            $table->string('average_length_of_stay', 100)->nullable();
            $table->text('stratification_options')->nullable();
            $table->text('stratification_rate')->nullable();
            $table->text('implants')->nullable();

            $table->text('preauth_documents')->nullable();
            $table->text('claim_documents')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('specialty');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mhis_procedures');
    }
};
