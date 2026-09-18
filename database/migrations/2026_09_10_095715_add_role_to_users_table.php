<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Add Role Column
        |--------------------------------------------------------------------------
        |
        | Future users default to reception.
        |
        */

        Schema::table('users', function (Blueprint $table) {

            $table->string('role', 50)
                ->default('reception');

            $table->index('role');

        });


        /*
        |--------------------------------------------------------------------------
        | Existing Users
        |--------------------------------------------------------------------------
        |
        | Existing ERP users are made administrators so that the current
        | administrator does not lose access after this migration.
        |
        */

        DB::table('users')->update([
            'role' => 'admin',
        ]);
    }


    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropIndex(['role']);

            $table->dropColumn('role');

        });
    }
};