<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administrative_requests', function (Blueprint $table) {
            $table->date('required_by')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('administrative_requests', function (Blueprint $table) {
            $table->dropIndex(['required_by']);
            $table->dropColumn('required_by');
        });
    }
};
