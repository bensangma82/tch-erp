<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mhis_registration_id_audits', function (Blueprint $table) {
            $table->string('new_registration_id')
                ->nullable()
                ->change();
        });


    }

    public function down(): void
    {
        // Preserve NULL audit records rather than silently converting them.
        if (DB::table('mhis_registration_id_audits')
            ->whereNull('new_registration_id')
            ->exists()) {
            throw new RuntimeException(
                'Cannot restore NOT NULL constraint while NULL audit values exist.'
            );
        }

        Schema::table('mhis_registration_id_audits', function (Blueprint $table) {
            $table->string('new_registration_id')
                ->nullable(false)
                ->change();
        });
    }
};
