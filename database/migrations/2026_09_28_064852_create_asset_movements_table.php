<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_id')
                ->constrained('assets')
                ->cascadeOnDelete();

            $table->date('movement_date');

            /*
            |--------------------------------------------------------------------------
            | Department Movement
            |--------------------------------------------------------------------------
            */

            $table->foreignId('from_department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->foreignId('to_department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Location Movement
            |--------------------------------------------------------------------------
            */

            $table->string('from_location', 200)->nullable();
            $table->string('to_location', 200)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Custodian Movement
            |--------------------------------------------------------------------------
            */

            $table->foreignId('from_custodian_employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->foreignId('to_custodian_employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Movement Type
            |--------------------------------------------------------------------------
            |
            | department_transfer
            | location_transfer
            | custodian_change
            | temporary_transfer
            | return
            | other
            |
            */

            $table->string('movement_type', 40);

            $table->text('reason')->nullable();
            $table->text('remarks')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $table->foreignId('moved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('movement_date');
            $table->index('movement_type');
            $table->index(['asset_id', 'movement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_movements');
    }
};