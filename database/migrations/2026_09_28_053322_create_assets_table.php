<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Identification
            |--------------------------------------------------------------------------
            */

            $table->string('asset_code', 50)->unique();
            $table->string('asset_name', 200);

            $table->foreignId('asset_category_id')
                ->constrained('asset_categories')
                ->restrictOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Assignment
            |--------------------------------------------------------------------------
            */

            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->foreignId('custodian_employee_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete();

            $table->foreignId('asset_vendor_id')
                ->nullable()
                ->constrained('asset_vendors')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Equipment Details
            |--------------------------------------------------------------------------
            */

            $table->string('manufacturer', 150)->nullable();
            $table->string('model', 150)->nullable();
            $table->string('serial_number', 150)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Purchase Details
            |--------------------------------------------------------------------------
            */

            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 14, 2)->nullable();

            $table->string('invoice_number', 100)->nullable();
            $table->string('purchase_order_number', 100)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Installation / Commissioning
            |--------------------------------------------------------------------------
            */

            $table->date('installation_date')->nullable();
            $table->date('commissioning_date')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Warranty
            |--------------------------------------------------------------------------
            */

            $table->date('warranty_start_date')->nullable();
            $table->date('warranty_end_date')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Location
            |--------------------------------------------------------------------------
            */

            $table->string('location', 200)->nullable();

            /*
            |--------------------------------------------------------------------------
            | Asset Status
            |--------------------------------------------------------------------------
            |
            | active
            | under_maintenance
            | out_of_service
            | condemned
            | disposed
            | lost
            |
            */

            $table->string('status', 30)->default('active');

            /*
            |--------------------------------------------------------------------------
            | Criticality
            |--------------------------------------------------------------------------
            |
            | low
            | medium
            | high
            | critical
            |
            */

            $table->string('criticality', 20)->default('medium');

            /*
            |--------------------------------------------------------------------------
            | Maintenance Requirements
            |--------------------------------------------------------------------------
            */

            $table->boolean('requires_preventive_maintenance')
                ->default(false);

            $table->boolean('requires_calibration')
                ->default(false);

            /*
            |--------------------------------------------------------------------------
            | Useful Life
            |--------------------------------------------------------------------------
            */

            $table->unsignedSmallInteger('useful_life_years')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Miscellaneous
            |--------------------------------------------------------------------------
            */

            $table->text('remarks')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index('asset_name');
            $table->index('serial_number');
            $table->index('status');
            $table->index('criticality');
            $table->index('department_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};