<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tally_ledger_mappings', function (Blueprint $table) {
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->restrictOnDelete();

            $table->string('tally_company', 200)
                ->nullable();

            $table->unique(
                ['supplier_id', 'tally_company'],
                'tally_supplier_company_unique'
            );
        });

        $this->addChecks();
    }

    /*
     * PostgreSQL integrity constraints.
     * Supplier mappings require an explicit company.
     */
    private function addChecks(): void
    {
        \Illuminate\Support\Facades\DB::statement("
            ALTER TABLE tally_ledger_mappings
            ADD CONSTRAINT tally_mapping_target_check
            CHECK (
                (CASE WHEN finance_head_id IS NOT NULL THEN 1 ELSE 0 END)
              + (CASE WHEN finance_account_id IS NOT NULL THEN 1 ELSE 0 END)
              + (CASE WHEN supplier_id IS NOT NULL THEN 1 ELSE 0 END)
              = 1
            )
        ");

        \Illuminate\Support\Facades\DB::statement("
            ALTER TABLE tally_ledger_mappings
            ADD CONSTRAINT tally_supplier_company_check
            CHECK (
                supplier_id IS NULL
                OR NULLIF(BTRIM(tally_company), '') IS NOT NULL
            )
        ");
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE tally_ledger_mappings DROP CONSTRAINT IF EXISTS tally_supplier_company_check'
        );
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE tally_ledger_mappings DROP CONSTRAINT IF EXISTS tally_mapping_target_check'
        );

        Schema::table('tally_ledger_mappings', function (Blueprint $table) {
            $table->dropUnique('tally_supplier_company_unique');
            $table->dropColumn('tally_company');
            $table->dropConstrainedForeignId('supplier_id');
        });
    }
};
