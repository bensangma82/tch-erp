<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)->unique();

            $table->string('name', 150);

            /*
             * cash = Cash in hand / cash counter
             * bank = Bank account
             */
            $table->string('account_type', 30);

            $table->string('bank_name', 150)
                ->nullable();

            $table->string('account_number', 100)
                ->nullable();

            $table->string('branch_name', 150)
                ->nullable();

            $table->string('ifsc_code', 30)
                ->nullable();

            /*
             * Opening balance when the account
             * is first introduced into the ERP.
             */
            $table->decimal('opening_balance', 15, 2)
                ->default(0);

            $table->date('opening_balance_date')
                ->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->text('remarks')
                ->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(
                ['account_type', 'is_active'],
                'finance_accounts_type_active_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_accounts');
    }
};