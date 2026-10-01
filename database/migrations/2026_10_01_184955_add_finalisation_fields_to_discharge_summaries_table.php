<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discharge_summaries', function (Blueprint $table) {

            $table->string('status', 20)
                ->default('draft')
                ->after('updated_by');

            $table->foreignId('finalised_by')
                ->nullable()
                ->after('status')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('finalised_at')
                ->nullable()
                ->after('finalised_by');

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('discharge_summaries', function (Blueprint $table) {

            $table->dropForeign([
                'finalised_by',
            ]);

            $table->dropIndex([
                'status',
            ]);

            $table->dropColumn([
                'status',
                'finalised_by',
                'finalised_at',
            ]);
        });
    }
};