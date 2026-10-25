<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('investigation_cancellation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->foreignId('invoice_item_id')->constrained('invoice_items')->restrictOnDelete();
            $table->foreignId('service_order_item_id')->nullable()->constrained('service_order_items')->restrictOnDelete();
            $table->string('clinical_status_at_request', 30);
            $table->boolean('sample_collected_at_request')->default(false);
            $table->boolean('requires_ms_approval');
            $table->decimal('requested_amount', 12, 2);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('reason');
            $table->text('decision_reason')->nullable();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
            $table->index(['invoice_id', 'status']);
            $table->index(['invoice_item_id', 'status']);
        });

        Schema::create('investigation_cancellation_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigation_cancellation_request_id')
                ->constrained('investigation_cancellation_requests')->restrictOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('refund_mode', 30);
            $table->string('reference_no', 100)->nullable();
            $table->string('status', 30)->default('pending');
            $table->foreignId('processed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['investigation_cancellation_request_id', 'payment_id'], 'investigation_refund_payment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigation_cancellation_refunds');
        Schema::dropIfExists('investigation_cancellation_requests');
    }
};
