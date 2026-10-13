<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_vouchers', function (Blueprint $table) {
            $table->string('origin', 30)
                ->nullable();

            $table->index(
                'origin',
                'finance_vouchers_origin_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('finance_vouchers', function (Blueprint $table) {
            $table->dropIndex('finance_vouchers_origin_index');
            $table->dropColumn('origin');
        });
    }
};