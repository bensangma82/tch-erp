<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'investigation_refund_settlement_checks',
            function (Blueprint $table) {

                // Stage 1: Refund initiation
                $table->foreignId('initiated_by')->nullable();
                $table->timestamp('initiated_at')->nullable();

                // Stage 2: Actual disbursement
                $table->foreignId('disbursed_by')->nullable();
                $table->timestamp('disbursed_at')->nullable();

                // Independent disbursement evidence
                $table->string('disbursement_reference', 100)
                    ->nullable();

                $table->text('disbursement_evidence')
                    ->nullable();
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'investigation_refund_settlement_checks',
            function (Blueprint $table) {
                $table->dropColumn([
                    'initiated_by',
                    'initiated_at',
                    'disbursed_by',
                    'disbursed_at',
                    'disbursement_reference',
                    'disbursement_evidence',
                ]);
            }
        );
    }
};
