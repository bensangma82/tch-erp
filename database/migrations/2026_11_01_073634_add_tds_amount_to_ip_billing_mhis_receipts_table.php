<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ip_billing_mhis_receipts', function (Blueprint $table) {
            $table->decimal('tds_amount', 15, 2)
                ->default(0);
        });

        DB::statement("
            ALTER TABLE ip_billing_mhis_receipts
            ADD CONSTRAINT mhis_receipts_tds_valid
            CHECK (
                tds_amount >= 0
                AND tds_amount <= amount
            )
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE ip_billing_mhis_receipts
            DROP CONSTRAINT IF EXISTS mhis_receipts_tds_valid
        ");

        Schema::table('ip_billing_mhis_receipts', function (Blueprint $table) {
            $table->dropColumn('tds_amount');
        });
    }
};
