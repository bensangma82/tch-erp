<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_results', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Investigation
            |--------------------------------------------------------------------------
            |
            | One result record belongs to one ServiceOrderItem.
            |
            */
            $table->foreignId('service_order_item_id')
                ->unique()
                ->constrained('service_order_items')
                ->cascadeOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Result / Report
            |--------------------------------------------------------------------------
            */

            // Useful for laboratory results or general text results.
            $table->text('result_text')->nullable();

            // Primarily useful for imaging reports.
            $table->text('findings')->nullable();

            // Primarily useful for imaging reports.
            $table->text('impression')->nullable();

            // Optional PDF / scanned / generated report later.
            $table->string('attachment_path')->nullable();


            /*
            |--------------------------------------------------------------------------
            | Result Status
            |--------------------------------------------------------------------------
            |
            | draft    = being entered
            | final    = completed result
            | verified = verified by authorized staff/doctor later
            |
            */
            $table->string('status')
                ->default('draft');


            /*
            |--------------------------------------------------------------------------
            | Result Entry
            |--------------------------------------------------------------------------
            */

            $table->foreignId('entered_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('entered_at')->nullable();


            /*
            |--------------------------------------------------------------------------
            | Verification
            |--------------------------------------------------------------------------
            */

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('verified_at')->nullable();


            $table->timestamps();

            $table->index('status');
            $table->index('entered_at');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('diagnostic_results');
    }
};