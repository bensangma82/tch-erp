<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'pharmacy_supplier_credits',
            function (Blueprint $table) {

                $table->unique(
                    'source_payable_id',
                    'pharmacy_supplier_credits_source_payable_unique'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::table(
            'pharmacy_supplier_credits',
            function (Blueprint $table) {

                $table->dropUnique(
                    'pharmacy_supplier_credits_source_payable_unique'
                );
            }
        );
    }
};