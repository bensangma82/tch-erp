<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_heads', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)
                ->unique();

            $table->string('name', 150);

            /*
             * income  = Revenue / receipts
             * expense = Expenditure
             */
            $table->string('head_type', 20);

            /*
             * Optional grouping for reports.
             *
             * Examples:
             * Clinical Services
             * Diagnostics
             * Pharmacy
             * Administrative
             * Utilities
             * Salaries & HR
             * Maintenance
             */
            $table->string('category', 100)
                ->nullable();

            /*
             * Optional parent head for hierarchical reporting.
             *
             * Example:
             * Diagnostics
             *   - Laboratory
             *   - Radiology
             *   - ECG
             *   - Echo
             *   - Endoscopy
             */
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('finance_heads')
                ->nullOnDelete();

            $table->boolean('is_active')
                ->default(true);

            $table->text('remarks')
                ->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(
                ['head_type', 'is_active'],
                'finance_heads_type_active_index'
            );

            $table->index(
                ['category', 'is_active'],
                'finance_heads_category_active_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_heads');
    }
};