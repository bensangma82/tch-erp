<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            // ---------------------------------------------------------
            // PATIENTS
            // ---------------------------------------------------------
            [
                'name' => 'patients.view',
                'label' => 'View Patients',
                'module' => 'patients',
                'description' => 'View patient registry and patient profiles.',
            ],
            [
                'name' => 'patients.create',
                'label' => 'Register Patients',
                'module' => 'patients',
                'description' => 'Create new patient records and assign UHID/MRD.',
            ],
            [
                'name' => 'patients.card',
                'label' => 'Print Patient Card',
                'module' => 'patients',
                'description' => 'View and print patient registration cards.',
            ],

            // ---------------------------------------------------------
            // OPD
            // ---------------------------------------------------------
            [
                'name' => 'opd.view',
                'label' => 'View OPD',
                'module' => 'opd',
                'description' => 'View OPD queue and encounters.',
            ],
            [
                'name' => 'opd.create',
                'label' => 'Create OPD Visit',
                'module' => 'opd',
                'description' => 'Register a patient for a new OPD encounter.',
            ],
            [
                'name' => 'opd.card',
                'label' => 'Print OPD Card',
                'module' => 'opd',
                'description' => 'View and print OPD encounter cards.',
            ],

            // ---------------------------------------------------------
            // NURSING
            // ---------------------------------------------------------
            [
                'name' => 'nursing.view',
                'label' => 'View Nursing Station',
                'module' => 'nursing',
                'description' => 'Access the nursing worklist.',
            ],
            [
                'name' => 'nursing.vitals',
                'label' => 'Record OPD Vitals',
                'module' => 'nursing',
                'description' => 'Create and update nursing vital signs.',
            ],

            // ---------------------------------------------------------
            // EMERGENCY
            // ---------------------------------------------------------
            [
                'name' => 'emergency.view',
                'label' => 'View Emergency',
                'module' => 'emergency',
                'description' => 'View emergency queue and visit details.',
            ],
            [
                'name' => 'emergency.create',
                'label' => 'Register Emergency Visit',
                'module' => 'emergency',
                'description' => 'Create new emergency visits.',
            ],
            [
                'name' => 'emergency.triage',
                'label' => 'Record Emergency Triage',
                'module' => 'emergency',
                'description' => 'Record emergency triage and vital signs.',
            ],
            [
                'name' => 'emergency.admit',
                'label' => 'Admit Emergency Patient',
                'module' => 'emergency',
                'description' => 'Admit an emergency patient into IPD.',
            ],

            // ---------------------------------------------------------
            // IPD
            // ---------------------------------------------------------
            [
                'name' => 'ipd.view',
                'label' => 'View IPD',
                'module' => 'ipd',
                'description' => 'View inpatient census and admissions.',
            ],
            [
                'name' => 'ipd.transfer',
                'label' => 'Transfer Bed',
                'module' => 'ipd',
                'description' => 'Transfer admitted patients between beds or wards.',
            ],
            [
                'name' => 'ipd.close',
                'label' => 'Close Admission',
                'module' => 'ipd',
                'description' => 'Close or discharge an inpatient admission.',
            ],
            [
                'name' => 'ipd.discharge-summary.view',
                'label' => 'View Discharge Summary',
                'module' => 'ipd',
                'description' => 'View and print discharge summaries.',
            ],
            [
                'name' => 'ipd.discharge-summary.edit',
                'label' => 'Edit Discharge Summary',
                'module' => 'ipd',
                'description' => 'Create or edit clinical discharge summaries.',
            ],

            // ---------------------------------------------------------
            // BILLING
            // ---------------------------------------------------------
            [
                'name' => 'billing.view',
                'label' => 'View OPD Billing',
                'module' => 'billing',
                'description' => 'Access OPD billing and investigation billing.',
            ],
            [
                'name' => 'billing.collect',
                'label' => 'Collect OPD Payments',
                'module' => 'billing',
                'description' => 'Collect OPD payments and issue receipts.',
            ],
            [
                'name' => 'ip-billing.view',
                'label' => 'View IP Billing',
                'module' => 'billing',
                'description' => 'View inpatient billing accounts.',
            ],
            [
                'name' => 'ip-billing.manage',
                'label' => 'Manage IP Billing',
                'module' => 'billing',
                'description' => 'Post charges, advances, payments, investigations and finalize bills.',
            ],

            // ---------------------------------------------------------
            // FINANCE
            // ---------------------------------------------------------
            [
                'name' => 'finance.dashboard',
                'label' => 'View Finance Dashboard',
                'module' => 'finance',
                'description' => 'View finance dashboard and balances.',
            ],
            [
                'name' => 'finance.vouchers.view',
                'label' => 'View Finance Vouchers',
                'module' => 'finance',
                'description' => 'View finance vouchers.',
            ],
            [
                'name' => 'finance.vouchers.create',
                'label' => 'Create Finance Vouchers',
                'module' => 'finance',
                'description' => 'Create finance vouchers.',
            ],
            [
                'name' => 'finance.vouchers.post',
                'label' => 'Post Finance Vouchers',
                'module' => 'finance',
                'description' => 'Post finance vouchers.',
            ],
            [
                'name' => 'finance.vouchers.cancel',
                'label' => 'Cancel Finance Vouchers',
                'module' => 'finance',
                'description' => 'Cancel finance vouchers.',
            ],
            [
                'name' => 'finance.reports',
                'label' => 'View Finance Reports',
                'module' => 'finance',
                'description' => 'Access finance reports.',
            ],
            [
                'name' => 'finance.master',
                'label' => 'Manage Finance Master',
                'module' => 'finance',
                'description' => 'Manage finance accounts and heads.',
            ],

            // ---------------------------------------------------------
            // LABORATORY
            // ---------------------------------------------------------
            [
                'name' => 'laboratory.view',
                'label' => 'View Laboratory Worklist',
                'module' => 'laboratory',
                'description' => 'View laboratory worklist.',
            ],
            [
                'name' => 'laboratory.sample',
                'label' => 'Manage Samples',
                'module' => 'laboratory',
                'description' => 'Collect, reject and recollect laboratory samples.',
            ],
            [
                'name' => 'laboratory.results',
                'label' => 'Enter Laboratory Results',
                'module' => 'laboratory',
                'description' => 'Enter and update laboratory results.',
            ],
            [
                'name' => 'laboratory.parameters',
                'label' => 'Manage Lab Parameters',
                'module' => 'laboratory',
                'description' => 'Manage laboratory test parameter masters.',
            ],

            // ---------------------------------------------------------
            // RADIOLOGY
            // ---------------------------------------------------------
            [
                'name' => 'radiology.view',
                'label' => 'View Radiology Worklist',
                'module' => 'radiology',
                'description' => 'View imaging and radiology worklist.',
            ],
            [
                'name' => 'radiology.report',
                'label' => 'Enter Radiology Report',
                'module' => 'radiology',
                'description' => 'Create and update imaging reports.',
            ],

            // ---------------------------------------------------------
            // PHARMACY
            // ---------------------------------------------------------
            [
                'name' => 'pharmacy.view',
                'label' => 'View Pharmacy',
                'module' => 'pharmacy',
                'description' => 'Access pharmacy dashboard and records.',
            ],
            [
                'name' => 'pharmacy.dispense',
                'label' => 'Dispense Medicines',
                'module' => 'pharmacy',
                'description' => 'Create pharmacy sales and dispense medicines.',
            ],
            [
                'name' => 'pharmacy.returns',
                'label' => 'Process Patient Returns',
                'module' => 'pharmacy',
                'description' => 'Process medicine returns from patients.',
            ],

            // Existing detailed pharmacy permissions are preserved:
            // pharmacy.po.create
            // pharmacy.po.approve
            // pharmacy.grn.create
            // pharmacy.purchase-return.create
            // pharmacy.supplier-payment.record
            // pharmacy.stock-adjustment.create
            // pharmacy.disposal.create
            // pharmacy.stock-audit.create
            // pharmacy.stock-audit.approve
            // pharmacy.stock-audit.post

            // ---------------------------------------------------------
            // STORES
            // ---------------------------------------------------------
            [
                'name' => 'stores.view',
                'label' => 'View Stores / Inventory',
                'module' => 'stores',
                'description' => 'View inventory and stores functions.',
            ],
            [
                'name' => 'stores.transfer',
                'label' => 'Manage Stock Transfers',
                'module' => 'stores',
                'description' => 'Create, issue and receive internal stock transfers.',
            ],

            // ---------------------------------------------------------
            // HR
            // ---------------------------------------------------------
            [
                'name' => 'hr.view',
                'label' => 'View HR',
                'module' => 'hr',
                'description' => 'View HR and staff information.',
            ],
            [
                'name' => 'hr.employees',
                'label' => 'Manage Employees',
                'module' => 'hr',
                'description' => 'Create and update employee records.',
            ],
            [
                'name' => 'hr.departments',
                'label' => 'Manage Departments',
                'module' => 'hr',
                'description' => 'Create and update hospital departments.',
            ],

            // ---------------------------------------------------------
            // ADMINISTRATION / SYSTEM
            // ---------------------------------------------------------
            [
                'name' => 'administration.view',
                'label' => 'View Administration',
                'module' => 'administration',
                'description' => 'View administrative requests and assigned work.',
            ],
            [
                'name' => 'administration.create',
                'label' => 'Create Administrative Request',
                'module' => 'administration',
                'description' => 'Create and submit administrative requests.',
            ],
            [
                'name' => 'administration.verify',
                'label' => 'Verify Administrative Request',
                'module' => 'administration',
                'description' => 'Verify submitted administrative requests.',
            ],
            [
                'name' => 'administration.approve',
                'label' => 'Approve Administrative Request',
                'module' => 'administration',
                'description' => 'Approve or reject administrative requests.',
            ],
            [
                'name' => 'administration.execute',
                'label' => 'Execute Administrative Work',
                'module' => 'administration',
                'description' => 'Start and complete assigned administrative work.',
            ],
            [
                'name' => 'system.users',
                'label' => 'Manage ERP Users',
                'module' => 'system',
                'description' => 'Create, edit, activate and deactivate ERP users.',
            ],
            [
                'name' => 'system.services',
                'label' => 'Manage Service Master',
                'module' => 'system',
                'description' => 'Manage service and charge master records.',
            ],
            [
                'name' => 'system.inpatient-master',
                'label' => 'Manage Inpatient Setup',
                'module' => 'system',
                'description' => 'Manage wards, rooms, beds and bed tariffs.',
            ],
        ];

        foreach ($permissions as $data) {
            Permission::updateOrCreate(
                ['name' => $data['name']],
                $data
            );
        }
    }
}