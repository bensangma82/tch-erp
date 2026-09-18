<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_supplier_credits', function (Blueprint $table) {
            $table->id();

            $table->string('credit_no')->unique();

            $table->foreignId('pharmacy_supplier_id')
                ->constrained('pharmacy_suppliers')
                ->restrictOnDelete();

            $table->foreignId('source_payable_id')
                ->nullable()
                ->constrained(
                    'pharmacy_supplier_payables'
                )
                ->nullOnDelete();

            $table->foreignId('source_grn_id')
                ->nullable()
                ->constrained('pharmacy_grns')
                ->nullOnDelete();

            $table->date('credit_date');

            $table->decimal(
                'original_credit_amount',
                14,
                2
            )->default(0);

            $table->decimal(
                'utilised_amount',
                14,
                2
            )->default(0);

            $table->decimal(
                'available_amount',
                14,
                2
            )->default(0);

            $table->string('status')
                ->default('available');

            $table->string(
                'source_type'
            )->default('overpayment_after_return');

            $table->text('remarks')
                ->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index(
                'pharmacy_supplier_id'
            );

            $table->index(
                'credit_date'
            );

            $table->index(
                'status'
            );

            $table->index(
                'source_payable_id'
            );
        });
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'pharmacy_supplier_credits'
        );
    }
};