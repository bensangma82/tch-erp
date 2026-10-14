<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_vouchers', function (Blueprint $table) {
            $table->string('source_type', 100)->nullable();
            $table->string('source_id', 100)->nullable();
            $table->string('source_event', 50)->nullable();

            $table->index(
                ['source_type', 'source_id', 'source_event'],
                'finance_vouchers_source_identity_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('finance_vouchers', function (Blueprint $table) {
            $table->dropIndex('finance_vouchers_source_identity_index');

            $table->dropColumn([
                'source_type',
                'source_id',
                'source_event',
            ]);
        });
    }
};
