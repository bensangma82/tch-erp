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
                $table->foreignId('voided_by')
                    ->nullable();

                $table->timestamp('voided_at')
                    ->nullable();

                $table->text('void_reason')
                    ->nullable();

                $table->string('void_evidence_reference', 150)
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
                    'voided_by',
                    'voided_at',
                    'void_reason',
                    'void_evidence_reference',
                ]);
            }
        );
    }
};
