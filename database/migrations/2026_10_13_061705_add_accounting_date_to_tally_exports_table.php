<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tally_exports', function (Blueprint $table) {
            $table->date('tally_accounting_date')
                ->nullable();

            $table->text('date_override_reason')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tally_exports', function (Blueprint $table) {
            $table->dropColumn([
                'tally_accounting_date',
                'date_override_reason',
            ]);
        });
    }
};
