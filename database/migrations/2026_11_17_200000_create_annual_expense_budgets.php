<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration {
    public function up(): void
    {
        Schema::create('annual_budget_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('start_year');
            $table->foreignId('general_ledger_account_id')->constrained()->restrictOnDelete();
            $table->string('category', 20);
            $table->decimal('original_amount', 16, 2);
            $table->decimal('current_amount', 16, 2);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['start_year', 'general_ledger_account_id'], 'annual_budget_year_gl_unique');
        });
        Schema::create('annual_budget_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annual_budget_line_id')->constrained()->restrictOnDelete();
            $table->decimal('previous_amount', 16, 2);
            $table->decimal('new_amount', 16, 2);
            $table->text('reason');
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at');
        });
        foreach (['view' => 'View annual budgets and utilisation', 'manage' => 'Set and revise annual budgets'] as $key => $label) {
            DB::table('permissions')->insertOrIgnore([
                'name' => 'finance.budgets.'.$key, 'label' => $label, 'module' => 'Finance',
                'description' => $label, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (DB::table('annual_budget_lines')->exists()) {
            throw new RuntimeException('Budget allocations and revision history exist. Preserve these records.');
        }
        Schema::dropIfExists('annual_budget_revisions');
        Schema::dropIfExists('annual_budget_lines');
        // Preserve permission assignments.
    }
};
