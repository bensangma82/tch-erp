<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mhis_registration_id_audits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ip_billing_mhis_claim_id')
                ->constrained('ip_billing_mhis_claims')
                ->restrictOnDelete();

            $table->string('old_registration_id')->nullable();
            $table->string('new_registration_id');

            $table->foreignId('changed_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['ip_billing_mhis_claim_id', 'created_at'],
                'mhis_reg_audit_claim_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mhis_registration_id_audits');
    }
};
