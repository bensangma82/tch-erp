<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hospital_expenses', function (Blueprint $table) {
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->restrictOnDelete();
        });

        Schema::table('pharmacy_suppliers', function (Blueprint $table) {
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_suppliers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
        });

        Schema::table('hospital_expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
        });
    }
};
