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
        Schema::create('invoices', function (Blueprint $table) {

            $table->id();

            $table->string('invoice_no')
                ->unique();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->restrictOnDelete();

            $table->foreignId('encounter_id')
                ->nullable()
                ->constrained('encounters')
                ->nullOnDelete();

            $table->date('invoice_date');

            $table->string('invoice_type')
                ->default('OPD');

            $table->decimal('subtotal', 10, 2)
                ->default(0);

            $table->decimal('discount', 10, 2)
                ->default(0);

            $table->decimal('total_amount', 10, 2)
                ->default(0);

            $table->decimal('paid_amount', 10, 2)
                ->default(0);

            $table->decimal('balance_amount', 10, 2)
                ->default(0);

            $table->string('status')
                ->default('unpaid');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('patient_id');
            $table->index('encounter_id');
            $table->index('invoice_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};