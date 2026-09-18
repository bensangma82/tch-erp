<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_stock_audit_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pharmacy_stock_audit_id')
                ->constrained('pharmacy_stock_audits')
                ->cascadeOnDelete();

            $table->foreignId('pharmacy_stock_batch_id')
                ->constrained('pharmacy_stock_batches')
                ->restrictOnDelete();

            $table->foreignId('medicine_id')
                ->constrained('medicines')
                ->restrictOnDelete();

            /*
             * Snapshot fields.
             * These preserve what was counted even if master data changes later.
             */
            $table->string('medicine_code')->nullable();
            $table->string('medicine_name');
            $table->string('brand_name')->nullable();
            $table->string('strength')->nullable();
            $table->string('unit')->nullable();

            $table->string('batch_number');
            $table->date('expiry_date')->nullable();

            /*
             * System quantity captured when audit is created.
             */
            $table->integer('system_quantity');

            /*
             * Physical quantity entered by staff.
             * Nullable until counted.
             */
            $table->integer('counted_quantity')->nullable();

            /*
             * counted_quantity - system_quantity
             */
            $table->integer('variance_quantity')->nullable();

            /*
             * Used for valuation of variance.
             */
            $table->decimal('purchase_price', 12, 2)->default(0);

            $table->decimal('variance_value', 14, 2)->nullable();

            /*
             * shortage
             * excess
             * counting_error
             * breakage
             * expired
             * missing
             * documentation_error
             * other
             */
            $table->string('variance_reason')->nullable();

            $table->text('remarks')->nullable();

            /*
             * Set when approved variance is finally posted
             * to the stock ledger.
             */
            $table->boolean('is_posted')->default(false);

            $table->foreignId('pharmacy_stock_movement_id')
                ->nullable()
                ->constrained('pharmacy_stock_movements')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(
                [
                    'pharmacy_stock_audit_id',
                    'pharmacy_stock_batch_id',
                ],
                'pharmacy_stock_audit_batch_unique'
            );

            $table->index('medicine_id');
            $table->index('variance_quantity');
            $table->index('is_posted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_stock_audit_items');
    }
};