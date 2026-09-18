<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administrative_requests', function (Blueprint $table) {
            $table->string('higher_approval_reference', 255)
                ->nullable()
                ->after('higher_authority');

            $table->index('higher_approval_reference');
        });
    }

    public function down(): void
    {
        Schema::table('administrative_requests', function (Blueprint $table) {
            $table->dropIndex([
                'higher_approval_reference',
            ]);

            $table->dropColumn(
                'higher_approval_reference'
            );
        });
    }
};