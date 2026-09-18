<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'administrative_request_documents',
            function (Blueprint $table) {
                $table->string('storage_disk', 50)
                    ->default('local')
                    ->after('file_path');

                $table->index('storage_disk');
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Existing documents
        |--------------------------------------------------------------------------
        |
        | Documents uploaded before this migration were stored on the
        | public disk. Preserve that information so they remain accessible.
        |
        */

        DB::table('administrative_request_documents')
            ->update([
                'storage_disk' => 'public',
            ]);
    }

    public function down(): void
    {
        Schema::table(
            'administrative_request_documents',
            function (Blueprint $table) {
                $table->dropIndex([
                    'storage_disk',
                ]);

                $table->dropColumn(
                    'storage_disk'
                );
            }
        );
    }
};