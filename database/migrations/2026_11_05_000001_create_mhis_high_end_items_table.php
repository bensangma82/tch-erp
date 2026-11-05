<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mhis_high_end_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('source_excel_row')->unique();
            $table->string('procedure_code', 255)->index();
            $table->string('item_name', 1000);
            $table->string('specialty', 255)->nullable();
            $table->decimal('reference_price', 12, 2)->nullable();
            $table->text('source_price_text')->nullable();
            $table->string('opd_ipd', 100)->nullable();
            $table->string('hospital_type', 255)->nullable();
            $table->decimal('maximum_multiplier', 8, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mhis_high_end_items');
    }
};
