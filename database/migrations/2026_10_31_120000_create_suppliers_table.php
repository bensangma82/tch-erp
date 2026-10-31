<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            $table->string('supplier_code', 50)->unique();
            $table->string('supplier_name', 200);

            $table->string('gstin', 15)->nullable();
            $table->string('pan_no', 10)->nullable();

            $table->string('contact_person', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();

            $table->text('address')->nullable();

            $table->boolean('is_active')->default(true);
            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('supplier_name');
            $table->index('gstin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
