<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {

            $table->timestamp('closed_at')
                ->nullable()
                ->after('discharged_at');

            $table->text('closure_notes')
                ->nullable()
                ->after('closed_at');

            $table->string('referral_destination', 255)
                ->nullable()
                ->after('closure_notes');

            $table->foreignId('closed_by')
                ->nullable()
                ->after('referral_destination')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {

            $table->dropForeign([
                'closed_by',
            ]);

            $table->dropColumn([
                'closed_at',
                'closure_notes',
                'referral_destination',
                'closed_by',
            ]);
        });
    }
};