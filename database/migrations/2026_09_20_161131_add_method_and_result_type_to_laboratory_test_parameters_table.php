<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laboratory_test_parameters', function (Blueprint $table) {
            $table->string('method', 255)
                ->nullable()
                ->after('unit');

            $table->string('result_type', 50)
                ->default('numeric')
                ->after('method');

            $table->index([
                'service_id',
                'result_type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('laboratory_test_parameters', function (Blueprint $table) {
            $table->dropIndex([
                'service_id',
                'result_type',
            ]);

            $table->dropColumn([
                'method',
                'result_type',
            ]);
        });
    }
};
