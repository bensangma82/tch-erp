<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ip_billing_mhis_claims', function (Blueprint $table) {
            $table->string('mhis_registration_id')
                ->nullable();

            $table->index(
                'mhis_registration_id',
                'ip_mhis_claims_registration_id_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('ip_billing_mhis_claims', function (Blueprint $table) {
            $table->dropIndex('ip_mhis_claims_registration_id_idx');
            $table->dropColumn('mhis_registration_id');
        });
    }
};
