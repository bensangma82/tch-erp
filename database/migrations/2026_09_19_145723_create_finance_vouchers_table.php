<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_vouchers', function (Blueprint $table) {
            $table->id();

            /*
             * Unique human-readable voucher number.
             * Examples:
             * RV-2026-000001
             * PV-2026-000001
             * TV-2026-000001
             * JV-2026-000001
             */
            $table->string('voucher_no', 50)
                ->unique();

            /*
             * receipt  = money received
             * payment  = money paid
             * transfer = movement between cash/bank accounts
             * journal  = accounting adjustment
             */
            $table->string('voucher_type', 20);

            $table->date('voucher_date');

            /*
             * Finance head identifies what the
             * transaction represents.
             *
             * Examples:
             * Laboratory Income
             * Electricity Expense
             * Salary Expense
             */
            $table->foreignId('finance_head_id')
                ->nullable()
                ->constrained('finance_heads')
                ->restrictOnDelete();

            /*
             * Main account affected by the voucher.
             *
             * Examples:
             * Main Cash
             * Pharmacy Cash
             * SBI Current Account
             */
            $table->foreignId('finance_account_id')
                ->nullable()
                ->constrained('finance_accounts')
                ->restrictOnDelete();

            /*
             * Used primarily for transfers.
             *
             * Example:
             * Main Cash -> SBI Current Account
             */
            $table->foreignId('destination_account_id')
                ->nullable()
                ->constrained('finance_accounts')
                ->restrictOnDelete();

            $table->decimal('amount', 15, 2);

            /*
             * cash, upi, card, cheque,
             * bank_transfer, neft, rtgs, etc.
             */
            $table->string('payment_mode', 50)
                ->nullable();

            $table->string('reference_no', 100)
                ->nullable();

            /*
             * Person / organisation involved.
             *
             * Examples:
             * Electricity Department
             * Vendor name
             * Employee name
             * Donor
             */
            $table->string('party_name', 200)
                ->nullable();

            $table->text('narration')
                ->nullable();

            /*
             * draft     = entered but not posted
             * posted    = included in Finance books
             * cancelled = reversed / cancelled
             */
            $table->string('status', 20)
                ->default('draft');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('posted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('posted_at')
                ->nullable();

            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('cancelled_at')
                ->nullable();

            $table->text('cancellation_reason')
                ->nullable();

            $table->timestamps();

            $table->index(
                ['voucher_date', 'voucher_type'],
                'finance_vouchers_date_type_index'
            );

            $table->index(
                ['status', 'voucher_date'],
                'finance_vouchers_status_date_index'
            );

            $table->index(
                ['finance_account_id', 'voucher_date'],
                'finance_vouchers_account_date_index'
            );

            $table->index(
                ['finance_head_id', 'voucher_date'],
                'finance_vouchers_head_date_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_vouchers');
    }
};