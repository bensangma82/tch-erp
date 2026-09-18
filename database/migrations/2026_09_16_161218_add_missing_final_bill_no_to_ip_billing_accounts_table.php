<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasColumn(
                'ip_billing_accounts',
                'final_bill_no'
            )
        ) {
            Schema::table(
                'ip_billing_accounts',
                function (Blueprint $table) {

                    $table->string('final_bill_no')
                        ->nullable()
                        ->unique()
                        ->after('account_no');
                }
            );
        }
    }


    public function down(): void
    {
        if (
            Schema::hasColumn(
                'ip_billing_accounts',
                'final_bill_no'
            )
        ) {
            Schema::table(
                'ip_billing_accounts',
                function (Blueprint $table) {

                    $table->dropUnique([
                        'final_bill_no',
                    ]);

                    $table->dropColumn(
                        'final_bill_no'
                    );
                }
            );
        }
    }
};