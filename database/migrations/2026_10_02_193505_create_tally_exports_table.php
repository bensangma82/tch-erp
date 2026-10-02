<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tally_exports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('finance_voucher_id')
                ->constrained('finance_vouchers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('status', 30)
                ->default('pending');

            $table->string('export_reference', 100)
                ->nullable()
                ->unique();

            $table->timestamp('exported_at')
                ->nullable();

            $table->foreignId('exported_by')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamp('confirmed_at')
                ->nullable();

            $table->text('error_message')
                ->nullable();

            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            $table->index([
                'status',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tally_exports');
    }
};
