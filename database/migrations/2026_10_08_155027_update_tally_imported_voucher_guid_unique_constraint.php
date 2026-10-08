<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tally_imported_vouchers', function (Blueprint $table) {
            $table->dropUnique(
                'tally_imported_vouchers_guid_unique'
            );

            $table->unique(
                ['tally_company', 'guid'],
                'tally_imported_vouchers_company_guid_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('tally_imported_vouchers', function (Blueprint $table) {
            $table->dropUnique(
                'tally_imported_vouchers_company_guid_unique'
            );

            $table->unique(
                'guid',
                'tally_imported_vouchers_guid_unique'
            );
        });
    }
};