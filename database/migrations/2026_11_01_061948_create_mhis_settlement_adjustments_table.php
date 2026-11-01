<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mhis_settlement_adjustments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ip_billing_mhis_claim_id')
                ->constrained('ip_billing_mhis_claims')
                ->restrictOnDelete();

            $table->string('adjustment_reference', 100)->unique();
            $table->date('adjustment_date');

            $table->decimal('tds_amount', 15, 2)->default(0);
            $table->decimal('accepted_deduction_amount', 15, 2)->default(0);

            $table->string('settlement_reference', 150)->nullable();
            $table->text('reason')->nullable();
            $table->text('supporting_evidence')->nullable();

            $table->string('status', 30)->default('draft');

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();

            $table->timestamps();

            $table->index(['ip_billing_mhis_claim_id', 'status']);
        });

        \Illuminate\Support\Facades\DB::statement("
            ALTER TABLE mhis_settlement_adjustments
            ADD CONSTRAINT mhis_adjustment_amount_check
            CHECK (
                tds_amount >= 0
                AND accepted_deduction_amount >= 0
                AND (tds_amount + accepted_deduction_amount) > 0
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('mhis_settlement_adjustments');
    }
};
