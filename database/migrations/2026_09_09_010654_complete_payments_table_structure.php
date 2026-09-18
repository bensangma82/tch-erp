<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Invoice
            |--------------------------------------------------------------------------
            */

            $table->foreignId('invoice_id')
                ->nullable()
                ->after('receipt_no')
                ->constrained('invoices')
                ->nullOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Patient
            |--------------------------------------------------------------------------
            */

            $table->foreignId('patient_id')
                ->nullable()
                ->after('invoice_id')
                ->constrained('patients')
                ->nullOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Payment information
            |--------------------------------------------------------------------------
            */

            $table->dateTime('payment_date')
                ->nullable()
                ->after('encounter_id');

            $table->decimal('amount', 10, 2)
                ->default(0)
                ->after('payment_date');

            $table->string('payment_mode')
                ->nullable()
                ->after('amount');

            $table->string('transaction_reference')
                ->nullable()
                ->after('payment_mode');

            $table->string('remarks')
                ->nullable()
                ->after('transaction_reference');


            /*
            |--------------------------------------------------------------------------
            | Reception / Cashier
            |--------------------------------------------------------------------------
            */

            $table->foreignId('received_by')
                ->nullable()
                ->after('remarks')
                ->constrained('users')
                ->nullOnDelete();


            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('patient_id');
            $table->index('invoice_id');
            $table->index('payment_date');
            $table->index('payment_mode');
        });
    }


    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {

            /*
            |--------------------------------------------------------------------------
            | Drop foreign keys
            |--------------------------------------------------------------------------
            */

            $table->dropForeign([
                'invoice_id'
            ]);

            $table->dropForeign([
                'patient_id'
            ]);

            $table->dropForeign([
                'received_by'
            ]);


            /*
            |--------------------------------------------------------------------------
            | Drop indexes
            |--------------------------------------------------------------------------
            */

            $table->dropIndex([
                'invoice_id'
            ]);

            $table->dropIndex([
                'patient_id'
            ]);

            $table->dropIndex([
                'payment_date'
            ]);

            $table->dropIndex([
                'payment_mode'
            ]);


            /*
            |--------------------------------------------------------------------------
            | Drop columns
            |--------------------------------------------------------------------------
            */

            $table->dropColumn([
                'invoice_id',
                'patient_id',
                'payment_date',
                'amount',
                'payment_mode',
                'transaction_reference',
                'remarks',
                'received_by',
            ]);
        });
    }
};