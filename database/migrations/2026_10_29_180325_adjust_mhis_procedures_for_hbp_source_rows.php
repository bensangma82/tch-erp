<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mhis_procedures', function (Blueprint $table) {
            $table->dropUnique(
                'mhis_procedures_procedure_code_unique'
            );

            $table->unsignedInteger('source_row_number')
                ->nullable()
                ->unique();

            $table->unsignedInteger('source_serial_number')
                ->nullable();

            $table->string('source_price_text', 100)
                ->nullable();

            $table->index(
                'procedure_code',
                'mhis_procedures_code_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('mhis_procedures', function (Blueprint $table) {
            $table->dropIndex('mhis_procedures_code_index');
            $table->dropUnique(
                'mhis_procedures_source_row_number_unique'
            );

            $table->dropColumn([
                'source_row_number',
                'source_serial_number',
                'source_price_text',
            ]);

            $table->unique('procedure_code');
        });
    }
};
