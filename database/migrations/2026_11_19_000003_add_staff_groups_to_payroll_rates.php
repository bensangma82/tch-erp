<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('basic_pay_rates', function (Blueprint $table) {
            $table->string('staff_group', 100)->default('');
            $table->dropUnique(['employee_category', 'grade_key']);
            $table->unique(['staff_group', 'employee_category', 'grade_key'], 'basic_pay_rate_group_category_grade_unique');
        });

        Schema::table('employee_salary_structures', function (Blueprint $table) {
            $table->string('staff_group', 100)->nullable();
        });
    }

    public function down(): void
    {
        DB::table('basic_pay_rates')->where('staff_group', '<>', '')->delete();

        Schema::table('basic_pay_rates', function (Blueprint $table) {
            $table->dropUnique('basic_pay_rate_group_category_grade_unique');
            $table->unique(['employee_category', 'grade_key']);
            $table->dropColumn('staff_group');
        });

        Schema::table('employee_salary_structures', function (Blueprint $table) {
            $table->dropColumn('staff_group');
        });
    }
};
