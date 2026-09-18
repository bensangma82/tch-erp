<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_sales', function (Blueprint $table) {
            $table->id();

            $table->string('sale_no')->unique();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->restrictOnDelete();

            $table->foreignId('encounter_id')
                ->nullable()
                ->constrained('encounters')
                ->nullOnDelete();

            $table->timestamp('sale_at');

            $table->decimal('subtotal', 12, 2)->default(0);

            $table->decimal('discount', 12, 2)->default(0);

            $table->decimal('total_amount', 12, 2)->default(0);

            $table->decimal('paid_amount', 12, 2)->default(0);

            $table->decimal('balance_amount', 12, 2)->default(0);

            $table->string('payment_mode', 50)->nullable();

            $table->string('transaction_reference')->nullable();

            $table->string('status', 50)->default('completed');

            $table->text('remarks')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('sale_at');
            $table->index('status');
            $table->index('payment_mode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_sales');
    }
};