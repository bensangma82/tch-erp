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
        Schema::create('ip_billing_charges', function (Blueprint $table) {

            $table->id();

            $table->foreignId('ip_billing_account_id')
                ->constrained('ip_billing_accounts')
                ->cascadeOnDelete();

            $table->foreignId('admission_id')
                ->constrained('admissions')
                ->cascadeOnDelete();

            $table->dateTime('charge_date');

            $table->string('charge_type');

            $table->foreignId('service_id')
                ->nullable()
                ->constrained('services')
                ->nullOnDelete();

            $table->foreignId('service_order_item_id')
                ->nullable()
                ->constrained('service_order_items')
                ->nullOnDelete();

            $table->string('code')
                ->nullable();

            $table->string('description');

            $table->decimal('quantity', 10, 2)
                ->default(1);

            $table->decimal('unit_price', 12, 2)
                ->default(0);

            $table->decimal('discount', 12, 2)
                ->default(0);

            $table->decimal('amount', 12, 2)
                ->default(0);

            $table->string('source_type')
                ->nullable();

            $table->unsignedBigInteger('source_id')
                ->nullable();

            $table->string('status')
                ->default('active');

            $table->text('remarks')
                ->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('cancelled_at')
                ->nullable();

            $table->timestamps();

            $table->index('ip_billing_account_id');
            $table->index('admission_id');
            $table->index('charge_date');
            $table->index('charge_type');
            $table->index('status');

            $table->index([
                'source_type',
                'source_id',
            ]);
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_billing_charges');
    }
};