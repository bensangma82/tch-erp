<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'service_orders',
            function (Blueprint $table) {

                $table->foreignId('admission_id')
                    ->nullable()
                    ->after('encounter_id')
                    ->constrained('admissions')
                    ->nullOnDelete();

                $table->index('admission_id');
            }
        );
    }


    public function down(): void
    {
        Schema::table(
            'service_orders',
            function (Blueprint $table) {

                $table->dropForeign([
                    'admission_id',
                ]);

                $table->dropIndex([
                    'admission_id',
                ]);

                $table->dropColumn(
                    'admission_id'
                );
            }
        );
    }
};