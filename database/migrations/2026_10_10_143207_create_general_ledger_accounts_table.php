<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('general_ledger_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('account_code', 30)->unique();
            $table->string('account_name', 150);

            $table->string('account_type', 20);
            $table->string('account_group', 100)->nullable();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('general_ledger_accounts')
                ->restrictOnDelete();

            $table->foreignId('finance_head_id')
                ->nullable()
                ->constrained('finance_heads')
                ->nullOnDelete();

            $table->foreignId('finance_account_id')
                ->nullable()
                ->constrained('finance_accounts')
                ->nullOnDelete();

            $table->string('tally_ledger_name', 200)->nullable();
            $table->string('tally_group_name', 150)->nullable();

            $table->string('normal_balance', 10);

            $table->boolean('is_active')->default(true);
            $table->boolean('allow_posting')->default(true);

            $table->text('description')->nullable();

            $table->timestamps();

            $table->index('account_type');
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('general_ledger_accounts');
    }
};