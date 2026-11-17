<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_request_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('administrative_request_id')->constrained('administrative_requests')->cascadeOnDelete();
            $table->string('event', 20);
            // Snapshot IDs/names retain attribution after user account changes.
            $table->unsignedBigInteger('previous_assignee_id')->nullable();
            $table->string('previous_assignee_name')->nullable();
            $table->unsignedBigInteger('assigned_to_id');
            $table->string('assigned_to_name');
            $table->unsignedBigInteger('actor_id');
            $table->string('actor_name');
            $table->text('reason');
            $table->string('execution_category', 50);
            $table->date('required_by')->nullable();
            $table->text('execution_remarks')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_request_assignments');
    }
};
