<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_heads', function (Blueprint $table) {
            $table->string('cost_behavior', 20)
                ->nullable()
                ->after('category');

            $table->boolean('include_in_break_even')
                ->default(true)
                ->after('cost_behavior');
        });
    }

    public function down(): void
    {
        Schema::table('finance_heads', function (Blueprint $table) {
            $table->dropColumn([
                'cost_behavior',
                'include_in_break_even',
            ]);
        });
    }
};