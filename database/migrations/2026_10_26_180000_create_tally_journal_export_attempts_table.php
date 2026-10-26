<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tally_journal_export_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tally_export_id')
                ->constrained('tally_exports')
                ->restrictOnDelete();

            $table->unsignedInteger('attempt_number');

            $table->string('status', 30);
            $table->uuid('remote_id')->nullable();
            $table->string('export_reference', 100)->nullable();

            $table->date('tally_accounting_date')->nullable();

            $table->longText('tally_response')->nullable();
            $table->text('error_message')->nullable();

            $table->foreignId('recovery_authorized_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('recovery_authorized_at')->nullable();
            $table->text('recovery_reason')->nullable();
            $table->text('reconciliation_evidence')->nullable();

            $table->timestamp('transmitted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['tally_export_id', 'attempt_number'],
                'tally_journal_attempt_unique'
            );

            $table->index(['tally_export_id', 'status']);
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('tally_journal_export_attempts')) {
            if (\Illuminate\Support\Facades\DB::table(
                'tally_journal_export_attempts'
            )->exists()) {
                throw new \RuntimeException(
                    'Cannot roll back: Tally export audit records exist.'
                );
            }
        }

        Schema::dropIfExists('tally_journal_export_attempts');
    }
};
