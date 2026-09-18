<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_orders', function (Blueprint $table) {

            $table->id();

            $table->string('order_no')
                ->unique();

            $table->foreignId('patient_id')
                ->constrained('patients')
                ->cascadeOnDelete();

            $table->foreignId('encounter_id')
                ->constrained('encounters')
                ->cascadeOnDelete();

            $table->dateTime('ordered_at');

            $table->string('status')
                ->default('draft');

            $table->text('remarks')
                ->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('patient_id');
            $table->index('encounter_id');
            $table->index('status');
            $table->index('ordered_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_orders');
    }
};