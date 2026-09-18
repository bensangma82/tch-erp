<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_requests', function (Blueprint $table) {
            $table->id();

            $table->string('request_no')->unique();

            /*
            |--------------------------------------------------------------------------
            | REQUEST CLASSIFICATION
            |--------------------------------------------------------------------------
            */
            $table->string('request_type', 50);
            // purchase
            // recruitment
            // finance
            // contract
            // project
            // maintenance
            // hr
            // other

            $table->string('title');
            $table->text('description')->nullable();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | FINANCIAL INFORMATION
            |--------------------------------------------------------------------------
            */
            $table->decimal('estimated_amount', 15, 2)->nullable();

            $table->decimal('approved_amount', 15, 2)->nullable();

            /*
            |--------------------------------------------------------------------------
            | WORKFLOW
            |--------------------------------------------------------------------------
            */
            $table->string('status', 50)
                ->default('draft');

            // draft
            // submitted
            // verified
            // pending_ms_approval
            // ms_approved
            // ms_rejected
            // higher_approval_required
            // higher_approved
            // execution_in_progress
            // executed
            // closed
            // cancelled

            /*
            |--------------------------------------------------------------------------
            | REQUESTOR
            |--------------------------------------------------------------------------
            */
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('submitted_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | ADMINISTRATOR VERIFICATION
            |--------------------------------------------------------------------------
            */
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('verified_at')->nullable();

            $table->text('verification_remarks')->nullable();

            /*
            |--------------------------------------------------------------------------
            | MEDICAL SUPERINTENDENT DECISION
            |--------------------------------------------------------------------------
            */
            $table->foreignId('ms_decided_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('ms_decided_at')->nullable();

            $table->string('ms_decision', 30)->nullable();
            // approved
            // rejected
            // returned

            $table->text('ms_remarks')->nullable();

            /*
            |--------------------------------------------------------------------------
            | HIGHER AUTHORITY
            |--------------------------------------------------------------------------
            */
            $table->boolean('requires_higher_approval')
                ->default(false);

            $table->string('higher_authority', 100)->nullable();
            // Board
            // CBCNEI
            // Chairman
            // Government
            // Other

            $table->foreignId('higher_approval_recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('higher_approved_at')->nullable();

            $table->text('higher_approval_remarks')->nullable();

            /*
            |--------------------------------------------------------------------------
            | EXECUTION
            |--------------------------------------------------------------------------
            */
            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('execution_started_at')->nullable();

            $table->timestamp('executed_at')->nullable();

            $table->foreignId('executed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('execution_remarks')->nullable();

            /*
            |--------------------------------------------------------------------------
            | CLOSURE
            |--------------------------------------------------------------------------
            */
            $table->timestamp('closed_at')->nullable();

            $table->foreignId('closed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('closure_remarks')->nullable();

            /*
            |--------------------------------------------------------------------------
            | GENERAL
            |--------------------------------------------------------------------------
            */
            $table->string('priority', 20)
                ->default('normal');

            // low
            // normal
            // high
            // urgent

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('request_type');
            $table->index('status');
            $table->index('priority');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_requests');
    }
};