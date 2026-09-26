<?php

namespace Database\Seeders;

use App\Models\StaffMedicalBenefitPolicy;
use Illuminate\Database\Seeder;

class StaffMedicalBenefitPolicySeeder extends Seeder
{
    public function run(): void
    {
        StaffMedicalBenefitPolicy::updateOrCreate(
            [
                'financial_year_start' => '2026-04-01',
                'financial_year_end' => '2027-03-31',
            ],
            [
                'name' => 'TCH Staff Medical Benefit FY 2026-27',

                'employee_annual_limit' => 50000.00,

                'dependent_family_annual_limit' => 25000.00,

                'carry_forward' => false,

                'is_active' => true,

                'remarks' => 'Annual medical benefit for eligible permanent TCH employees. Employee entitlement ₹50,000 and combined dependent-family entitlement ₹25,000. Unused balance expires at the end of the financial year.',

                'created_by' => null,
                'updated_by' => null,
            ]
        );
    }
}