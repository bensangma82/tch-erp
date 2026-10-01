<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {

            $table->foreignId('emergency_visit_id')
                ->nullable()
                ->after('encounter_id')
                ->constrained('emergency_visits')
                ->nullOnDelete();

            $table->index('emergency_visit_id');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {

            $table->dropForeign([
                'emergency_visit_id'
            ]);

            $table->dropIndex([
                'emergency_visit_id'
            ]);

            $table->dropColumn(
                'emergency_visit_id'
            );
        });
    }
};