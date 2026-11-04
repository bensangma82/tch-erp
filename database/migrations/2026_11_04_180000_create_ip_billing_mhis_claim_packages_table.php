<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ip_billing_mhis_claim_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ip_billing_mhis_claim_id')
                ->constrained('ip_billing_mhis_claims')->cascadeOnDelete();
            $table->foreignId('mhis_procedure_id')
                ->nullable()->constrained('mhis_procedures')->nullOnDelete();
            $table->string('procedure_code', 255);
            $table->string('procedure_name', 500);
            $table->decimal('reference_tariff', 12, 2)->nullable();
            $table->unsignedInteger('sort_order');
            $table->timestamps();
            $table->unique(['ip_billing_mhis_claim_id', 'sort_order'], 'mhis_claim_package_order_unique');
            $table->index(['ip_billing_mhis_claim_id', 'mhis_procedure_id'], 'mhis_claim_package_master_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ip_billing_mhis_claim_packages');
    }
};
