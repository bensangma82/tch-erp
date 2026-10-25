<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('investigation_refund_settlement_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cancellation_id')->constrained('investigation_cancellation_requests')->restrictOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('settlement_method', 20);
            $table->string('settlement_reference', 100);
            $table->text('evidence_description');
            $table->string('status', 30)->default('submitted');
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->foreignId('refund_id')->nullable()->constrained('investigation_cancellation_refunds')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['settlement_method', 'settlement_reference'], 'irsc_unique_external_reference');
            $table->unique('refund_id', 'irsc_unique_refund');
            $table->index(['status', 'created_at']);
        });
    }
    public function down(): void {
        // Financial audit data must not be destroyed by routine rollback.
        if (Schema::hasTable('investigation_refund_settlement_checks') &&
            \Illuminate\Support\Facades\DB::table('investigation_refund_settlement_checks')->exists()) {
            throw new \RuntimeException('Cannot drop refund settlement audit records.');
        }
        Schema::dropIfExists('investigation_refund_settlement_checks');
    }
};
