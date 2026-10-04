<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tally_exports', function (Blueprint $table) {
            $table->uuid('remote_id')
                ->nullable()
                ->unique()
                ->after('export_reference');

            $table->string('tally_voucher_id', 100)
                ->nullable()
                ->after('remote_id');

            $table->text('tally_response')
                ->nullable()
                ->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('tally_exports', function (Blueprint $table) {
            $table->dropUnique([
                'remote_id',
            ]);

            $table->dropColumn([
                'remote_id',
                'tally_voucher_id',
                'tally_response',
            ]);
        });
    }
};
