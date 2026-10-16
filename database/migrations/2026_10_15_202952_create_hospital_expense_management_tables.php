
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Hospital Expenses
        |--------------------------------------------------------------------------
        */

        Schema::create('hospital_expenses', function (Blueprint $table) {
            $table->id();

            $table->string('expense_no', 50)->unique();

            $table->foreignId('finance_head_id')
                ->constrained('finance_heads')
                ->restrictOnDelete();

            $table->foreignId('general_ledger_account_id')
                ->constrained('general_ledger_accounts')
                ->restrictOnDelete();

            $table->string('expense_classification', 30);
            // clinical, non_clinical, shared

            $table->string('expense_type', 30)
                ->default('operating');
            // operating, inventory, capital

            $table->date('expense_date');
            $table->date('accounting_date');

            $table->string('supplier_name', 255)->nullable();
            $table->string('invoice_no', 100)->nullable();
            $table->date('invoice_date')->nullable();

            $table->string('description', 500);

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);

            $table->string('status', 30)->default('draft');
            // draft, submitted, approved, posted, cancelled

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->foreignId('posted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('posted_at')->nullable();

            $table->foreignId('journal_entry_id')
                ->nullable()
                ->unique()
                ->constrained('journal_entries')
                ->restrictOnDelete();

            $table->string('source_type', 100)->nullable();
            $table->string('source_id', 100)->nullable();
            $table->string('source_event', 100)->nullable();

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index([
                'accounting_date',
                'status',
            ], 'hospital_expenses_accounting_status_idx');

            $table->index([
                'expense_classification',
                'expense_date',
            ], 'hospital_expenses_classification_date_idx');

            $table->index([
                'source_type',
                'source_id',
                'source_event',
            ], 'hospital_expenses_source_idx');
        });

        /*
        |--------------------------------------------------------------------------
        | Department Expense Allocations
        |--------------------------------------------------------------------------
        |
        | Each expense may be allocated to multiple departments.
        | Allocation totals must be checked by the application
        | before approval and journal posting.
        |
        */

        Schema::create('hospital_expense_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hospital_expense_id')
                ->constrained('hospital_expenses')
                ->cascadeOnDelete();

            $table->foreignId('department_id')
                ->constrained('departments')
                ->restrictOnDelete();

            $table->decimal('percentage', 7, 4);

            $table->decimal('allocated_amount', 15, 2);

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->unique([
                'hospital_expense_id',
                'department_id',
            ], 'hospital_expense_department_unique');
        });

        /*
        |--------------------------------------------------------------------------
        | Hospital Expense Payments
        |--------------------------------------------------------------------------
        |
        | Payment records settle an existing recognized expense.
        | They must not generate another expense debit.
        |
        */

        Schema::create('hospital_expense_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hospital_expense_id')
                ->constrained('hospital_expenses')
                ->restrictOnDelete();

            $table->foreignId('finance_voucher_id')
                ->nullable()
                ->unique()
                ->constrained('finance_vouchers')
                ->restrictOnDelete();

            $table->date('payment_date');

            $table->decimal('amount', 15, 2);

            $table->string('payment_mode', 50);

            $table->string('reference_no', 100)->nullable();

            $table->string('status', 30)->default('recorded');
            // recorded, posted, cancelled

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index([
                'hospital_expense_id',
                'status',
            ], 'hospital_expense_payments_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospital_expense_payments');
        Schema::dropIfExists('hospital_expense_allocations');
        Schema::dropIfExists('hospital_expenses');
    }
};
