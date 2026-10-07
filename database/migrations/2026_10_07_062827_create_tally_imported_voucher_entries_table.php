<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tally_imported_voucher_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tally_imported_voucher_id')
                ->constrained('tally_imported_vouchers')
                ->cascadeOnDelete();

            /*
             * Preserve the ledger name exactly as returned by Tally.
             */
            $table->string('ledger_name', 255);

            /*
             * Preserve Tally's signed amount:
             *
             * negative = debit
             * positive = credit
             *
             * Do not reverse the sign while importing. Reporting layers can
             * transform it according to their accounting presentation needs.
             */
            $table->decimal('amount', 15, 2);

            /*
             * Tally ISDEEMEDPOSITIVE.
             *
             * nullable because some Tally responses may omit the value.
             */
            $table->boolean('is_deemed_positive')
                ->nullable();

            /*
             * Sequence preserves the order in which ledger entries were
             * returned inside the original Tally voucher.
             */
            $table->unsignedInteger('line_no');

            $table->timestamps();

            $table->unique(
                [
                    'tally_imported_voucher_id',
                    'line_no',
                ],
                'tally_imported_voucher_entries_line_unique'
            );

            $table->index(
                [
                    'ledger_name',
                    'tally_imported_voucher_id',
                ],
                'tally_imported_voucher_entries_ledger_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tally_imported_voucher_entries');
    }
};