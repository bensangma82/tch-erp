<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->string('default_specimen_type', 100)
                ->nullable()
                ->after('requires_sample');

            $table->string('sample_container', 150)
                ->nullable()
                ->after('default_specimen_type');
        });

        Schema::table('service_order_items', function (Blueprint $table) {
            $table->string('specimen_type', 100)
                ->nullable()
                ->after('requires_sample');

            $table->string('sample_container', 150)
                ->nullable()
                ->after('specimen_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_order_items', function (Blueprint $table) {
            $table->dropColumn([
                'specimen_type',
                'sample_container',
            ]);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'default_specimen_type',
                'sample_container',
            ]);
        });
    }
};