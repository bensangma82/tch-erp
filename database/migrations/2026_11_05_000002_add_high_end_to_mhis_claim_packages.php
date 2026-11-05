<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('ip_billing_mhis_claim_packages', function (Blueprint $table) {
            $table->string('item_type', 24)->default('regular');
            $table->foreignId('mhis_high_end_item_id')->nullable()
                ->constrained('mhis_high_end_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ip_billing_mhis_claim_packages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mhis_high_end_item_id');
            $table->dropColumn('item_type');
        });
    }
};
