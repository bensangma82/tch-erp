
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tally_ledger_masters', function (Blueprint $table) {
            $table->id();

            // Source Tally company.
            $table->string('tally_company', 255);

            // Original ledger details from TallyPrime.
            $table->string('ledger_name', 255);
            $table->string('parent_group', 255)->nullable();

            // Last balance retrieved (informational only).
            $table->decimal('closing_balance', 18, 2)->nullable();

            // Synchronization tracking.
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            // Prevent duplicate ledgers within the same company.
            $table->unique(
                ['tally_company', 'ledger_name'],
                'tally_ledger_masters_company_ledger_unique'
            );

            $table->index('parent_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tally_ledger_masters');
    }
};
