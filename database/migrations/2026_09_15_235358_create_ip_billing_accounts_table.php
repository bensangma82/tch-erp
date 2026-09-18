<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ip_billing_accounts', function (Blueprint $table) {

            $table->id();

            $table->foreignId('admission_id')
                ->unique()
                ->constrained('admissions')
                ->cascadeOnDelete();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnDelete();

            $table->string('account_no')
                ->unique();

            $table->dateTime('opened_at');

            $table->string('status')
                ->default('open');

            $table->decimal('subtotal', 12, 2)
                ->default(0);

            $table->decimal('discount_amount', 12, 2)
                ->default(0);

            $table->decimal('net_amount', 12, 2)
                ->default(0);

            $table->decimal('advance_amount', 12, 2)
                ->default(0);

            $table->decimal('paid_amount', 12, 2)
                ->default(0);

            $table->decimal('balance_amount', 12, 2)
                ->default(0);

            $table->dateTime('finalized_at')
                ->nullable();

            $table->foreignId('finalized_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            $table->index('patient_id');
            $table->index('status');
            $table->index('opened_at');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_billing_accounts');
    }
};