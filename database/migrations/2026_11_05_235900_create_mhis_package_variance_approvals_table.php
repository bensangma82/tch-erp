<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mhis_package_variance_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ip_billing_mhis_claim_id')->unique()->constrained('ip_billing_mhis_claims')->restrictOnDelete();
            $table->foreignId('ip_billing_account_id')->constrained('ip_billing_accounts')->restrictOnDelete();
            $table->decimal('bill_amount', 15, 2);
            $table->decimal('approved_amount', 15, 2);
            $table->decimal('variance_amount', 15, 2);
            $table->string('treatment', 40);
            $table->string('supporting_reference', 255);
            $table->text('reason');
            $table->text('supporting_evidence');
            $table->boolean('contractual_nonrecoverable')->default(false);
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mhis_package_variance_approvals');
    }
};
