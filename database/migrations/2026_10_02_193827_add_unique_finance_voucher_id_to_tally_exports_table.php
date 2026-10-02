<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tally_exports', function (Blueprint $table) {
            $table->unique(
                'finance_voucher_id',
                'tally_exports_finance_voucher_id_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('tally_exports', function (Blueprint $table) {
            $table->dropUnique(
                'tally_exports_finance_voucher_id_unique'
            );
        });
    }
};
