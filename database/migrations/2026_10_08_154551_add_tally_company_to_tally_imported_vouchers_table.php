<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tally_imported_vouchers', function (Blueprint $table) {
            $table->string('tally_company', 255)
                ->nullable()
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('tally_imported_vouchers', function (Blueprint $table) {
            $table->dropIndex([
                'tally_company',
            ]);

            $table->dropColumn('tally_company');
        });
    }
};