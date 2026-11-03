<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mhis_opening_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mhis_opening_receivable_id')->unique()
                ->constrained('mhis_opening_receivables')->restrictOnDelete();
            $table->string('trial_balance_reference');
            $table->string('tally_company');
            $table->decimal('tally_gl_1210_closing_balance', 18, 2);
            $table->boolean('gl_1210_carry_forward_confirmed')->default(false);
            $table->string('supporting_evidence_reference');
            $table->string('status')->default('draft');
            $table->foreignId('prepared_by')->constrained('users');
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('mhis_opening_reconciliations');
    }
};
