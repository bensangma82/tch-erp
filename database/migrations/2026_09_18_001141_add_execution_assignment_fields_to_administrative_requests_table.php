<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administrative_requests', function (Blueprint $table) {

            $table->string(
                'execution_category',
                50
            )->nullable()->after('assigned_to');

            $table->string(
                'assigned_role',
                100
            )->nullable()->after('execution_category');

            $table->index('execution_category');
            $table->index('assigned_role');
        });
    }

    public function down(): void
    {
        Schema::table('administrative_requests', function (Blueprint $table) {

            $table->dropIndex([
                'execution_category'
            ]);

            $table->dropIndex([
                'assigned_role'
            ]);

            $table->dropColumn([
                'execution_category',
                'assigned_role',
            ]);
        });
    }
};