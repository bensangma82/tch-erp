<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('basic_pay_rates', function (Blueprint $table) {
            $table->id();
            $table->string('employee_category');
            $table->string('pay_grade', 60);
            $table->string('grade_key', 60);
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->unique(['employee_category', 'grade_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('basic_pay_rates');
    }
};
