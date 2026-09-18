<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {

            $table->string('admission_no', 50)
                ->unique()
                ->after('id');

            $table->foreignId('patient_id')
                ->after('admission_no')
                ->constrained('patients')
                ->restrictOnDelete();

            $table->string('source_type', 50)
                ->default('emergency')
                ->after('patient_id');

            $table->unsignedBigInteger('source_id')
                ->nullable()
                ->after('source_type');

            $table->timestamp('admitted_at')
                ->after('source_id');

            $table->foreignId('department_id')
                ->nullable()
                ->after('admitted_at')
                ->constrained('departments')
                ->nullOnDelete();

            $table->foreignId('consultant_id')
                ->nullable()
                ->after('department_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('admission_type', 50)
                ->default('emergency')
                ->after('consultant_id');

            $table->text('admission_reason')
                ->nullable()
                ->after('admission_type');

            $table->text('provisional_diagnosis')
                ->nullable()
                ->after('admission_reason');

            $table->foreignId('bed_id')
                ->nullable()
                ->after('provisional_diagnosis')
                ->constrained('beds')
                ->nullOnDelete();

            $table->string('status', 50)
                ->default('admitted')
                ->after('bed_id');

            $table->timestamp('discharged_at')
                ->nullable()
                ->after('status');

            $table->foreignId('created_by')
                ->nullable()
                ->after('discharged_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('source_type');
            $table->index('source_id');
            $table->index('admitted_at');
            $table->index('status');
            $table->index('consultant_id');
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {

            $table->dropForeign(['patient_id']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['consultant_id']);
            $table->dropForeign(['bed_id']);
            $table->dropForeign(['created_by']);

            $table->dropIndex(['source_type']);
            $table->dropIndex(['source_id']);
            $table->dropIndex(['admitted_at']);
            $table->dropIndex(['status']);
            $table->dropIndex(['consultant_id']);

            $table->dropColumn([
                'admission_no',
                'patient_id',
                'source_type',
                'source_id',
                'admitted_at',
                'department_id',
                'consultant_id',
                'admission_type',
                'admission_reason',
                'provisional_diagnosis',
                'bed_id',
                'status',
                'discharged_at',
                'created_by',
            ]);
        });
    }
};