<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ip_billing_advances', function (Blueprint $table) {
            $table->string('source_type')
                ->nullable()
                ->after('payment_mode');

            $table->unsignedBigInteger('source_id')
                ->nullable()
                ->after('source_type');

            $table->index(
                ['source_type', 'source_id'],
                'ip_billing_advances_source_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('ip_billing_advances', function (Blueprint $table) {
            $table->dropIndex(
                'ip_billing_advances_source_index'
            );

            $table->dropColumn([
                'source_type',
                'source_id',
            ]);
        });
    }
};