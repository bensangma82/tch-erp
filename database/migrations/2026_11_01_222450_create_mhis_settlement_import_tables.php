<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mhis_settlement_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename');
            $table->string('file_sha256', 64)->unique();
            $table->string('status')->default('draft');
            $table->unsignedInteger('total_rows')->default(0);
            $table->decimal('total_approved', 15, 2)->default(0);
            $table->decimal('total_tds', 15, 2)->default(0);
            $table->decimal('total_net', 15, 2)->default(0);
            $table->foreignId('uploaded_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mhis_settlement_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')
                ->constrained('mhis_settlement_import_batches')
                ->cascadeOnDelete();

            $table->unsignedInteger('excel_row_number');
            $table->string('registration_id')->nullable();
            $table->string('beneficiary_name')->nullable();
            $table->string('programme_id')->nullable();
            $table->string('procedure_name')->nullable();

            $table->decimal('initiated_amount', 15, 2)->default(0);
            $table->decimal('approved_amount', 15, 2)->default(0);
            $table->decimal('tds_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);

            $table->string('utr_reference')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('payment_status')->nullable();

            $table->foreignId('matched_claim_id')->nullable()
                ->constrained('ip_billing_mhis_claims')
                ->nullOnDelete();

            $table->string('match_status')->default('unmatched');
            $table->text('match_notes')->nullable();
            $table->jsonb('source_data')->nullable();
            $table->timestamps();

            $table->unique(['batch_id', 'excel_row_number']);
            $table->index('registration_id');
            $table->index('utr_reference');
            $table->index('matched_claim_id');
            $table->index('match_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mhis_settlement_import_rows');
        Schema::dropIfExists('mhis_settlement_import_batches');
    }
};
