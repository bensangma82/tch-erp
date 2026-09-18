<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_disposals', function (Blueprint $table) {
            $table->id();

            $table->string('disposal_no')->unique();

            $table->dateTime('disposed_at');

            $table->string('reason');

            $table->text('remarks')->nullable();

            $table->string('status')->default('completed');

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamps();

            $table->index('disposed_at');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_disposals');
    }
};