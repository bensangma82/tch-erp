<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tally_imported_vouchers', function (Blueprint $table) {
            $table->id();

            /*
             * Durable identifiers supplied by Tally.
             *
             * GUID identifies the Tally voucher itself.
             * REMOTEID is especially important for vouchers originally
             * exported from this ERP because it can be matched directly
             * against tally_exports.remote_id.
             */
            $table->string('guid', 150)
                ->nullable()
                ->unique();

            $table->uuid('remote_id')
                ->nullable()
                ->index();

            /*
             * Original Tally voucher information.
             */
            $table->date('voucher_date')
                ->nullable();

            $table->string('voucher_type', 100)
                ->nullable();

            $table->string('voucher_number', 100)
                ->nullable();

            $table->string('party_ledger', 255)
                ->nullable();

            $table->text('narration')
                ->nullable();

            /*
             * Reconciliation link.
             *
             * This does not convert the imported Tally voucher into an ERP
             * finance voucher. It merely records a confirmed or proposed
             * relationship between the two systems.
             */
            $table->foreignId('finance_voucher_id')
                ->nullable()
                ->constrained('finance_vouchers')
                ->nullOnDelete();

            /*
             * matched
             * tally_only
             * difference
             * pending
             *
             * ERP-only vouchers are identified from finance_vouchers that
             * have no corresponding imported Tally voucher, so they do not
             * require a row in this table.
             */
            $table->string('reconciliation_status', 30)
                ->default('pending');

            /*
             * exact_remote_id
             * exact_identifier
             * secondary_match
             * manual
             */
            $table->string('match_method', 50)
                ->nullable();

            /*
             * Confidence is intentionally explicit so future secondary
             * matching never masquerades as an exact identifier match.
             */
            $table->decimal('match_confidence', 5, 2)
                ->nullable();

            $table->text('reconciliation_notes')
                ->nullable();

            /*
             * Import audit information.
             */
            $table->timestamp('first_seen_at')
                ->nullable();

            $table->timestamp('last_seen_at')
                ->nullable();

            $table->timestamps();

            $table->index(
                ['reconciliation_status', 'voucher_date'],
                'tally_imported_vouchers_status_date_index'
            );

            $table->index(
                ['voucher_date', 'voucher_type'],
                'tally_imported_vouchers_date_type_index'
            );

            $table->index(
                ['finance_voucher_id', 'reconciliation_status'],
                'tally_imported_vouchers_finance_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tally_imported_vouchers');
    }
};