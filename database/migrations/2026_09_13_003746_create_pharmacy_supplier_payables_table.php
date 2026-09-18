<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_supplier_payables', function (Blueprint $table) {
            $table->id();

            $table->string('payable_no')->unique();

            $table->foreignId('pharmacy_supplier_id')
                ->constrained('pharmacy_suppliers')
                ->restrictOnDelete();

            $table->foreignId('pharmacy_grn_id')
                ->nullable()
                ->unique()
                ->constrained('pharmacy_grns')
                ->nullOnDelete();

            $table->string('supplier_invoice_no')->nullable();
            $table->date('supplier_invoice_date')->nullable();

            $table->date('payable_date');
            $table->date('due_date')->nullable();

            $table->decimal('original_amount', 14, 2)->default(0);

            $table->decimal('return_adjustment', 14, 2)->default(0);

            $table->decimal('other_adjustment', 14, 2)->default(0);

            $table->decimal('paid_amount', 14, 2)->default(0);

            $table->decimal('outstanding_amount', 14, 2)->default(0);

            $table->string('status')->default('unpaid');

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index('payable_date');
            $table->index('due_date');
            $table->index('status');
            $table->index('pharmacy_supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_supplier_payables');
    }
};