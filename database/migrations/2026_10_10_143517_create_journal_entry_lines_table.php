<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('journal_entry_id')
                ->constrained('journal_entries')
                ->restrictOnDelete();

            $table->unsignedInteger('line_no');

            $table->foreignId('general_ledger_account_id')
                ->constrained('general_ledger_accounts')
                ->restrictOnDelete();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->restrictOnDelete();

            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);

            $table->string('reference_no', 100)->nullable();
            $table->text('description')->nullable();

            $table->timestamps();

            $table->unique(
                ['journal_entry_id', 'line_no'],
                'journal_entry_line_number_unique'
            );

            $table->index('general_ledger_account_id');
            $table->index('department_id');
        });

        /*
        |--------------------------------------------------------------------------
        | PostgreSQL Accounting Constraints
        |--------------------------------------------------------------------------
        | Each line must have exactly one positive debit or credit.
        | Negative amounts and zero-value lines are prohibited.
        */

        DB::statement("
            ALTER TABLE journal_entry_lines
            ADD CONSTRAINT journal_line_debit_credit_check
            CHECK (
                (debit > 0 AND credit = 0)
                OR
                (credit > 0 AND debit = 0)
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};