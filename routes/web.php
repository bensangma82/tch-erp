<?php

use App\Http\Controllers\Admin\AssetCategoryController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\AssetMovementController;
use App\Http\Controllers\Admin\AssetVendorController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\EmployeeContractController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeeDocumentController;
use App\Http\Controllers\Admin\EmployeeLeaveBalanceController;
use App\Http\Controllers\Admin\HrController;
use App\Http\Controllers\Admin\LeaveRequestController;
use App\Http\Controllers\Admin\LeaveTypeController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Administration\AdministrativeRequestController;
use App\Http\Controllers\AdmissionClosureController;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\BedManagementController;
use App\Http\Controllers\BedTariffController;
use App\Http\Controllers\BedTransferController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CharityAdjustmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiagnosticSampleController;
use App\Http\Controllers\DiagnosticWorklistController;
use App\Http\Controllers\DischargeSummaryController;
use App\Http\Controllers\EmergencyAdmissionController;
use App\Http\Controllers\EmergencyClinicalNoteController;
use App\Http\Controllers\EmergencyTriageController;
use App\Http\Controllers\EmergencyVisitController;
use App\Http\Controllers\Finance\FinanceDashboardController;
use App\Http\Controllers\Finance\FinanceMasterController;
use App\Http\Controllers\Finance\FinanceReportController;
use App\Http\Controllers\Finance\FinanceVoucherController;
use App\Http\Controllers\Finance\TallyExportController;
use App\Http\Controllers\Finance\TallyIntegrationController;
use App\Http\Controllers\Finance\TallyReconciliationController;
use App\Http\Controllers\Hr\EmployeeSalaryStructureController;
use App\Http\Controllers\Hr\PayrollRunController;
use App\Http\Controllers\Hr\SalaryComponentController;
use App\Http\Controllers\Hr\StaffMedicalBenefitController;
use App\Http\Controllers\InpatientMasterController;
use App\Http\Controllers\IpBillingController;
use App\Http\Controllers\LaboratoryTestParameterController;
use App\Http\Controllers\NursingController;
use App\Http\Controllers\OpdAdmissionController;
use App\Http\Controllers\OpdController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\Pharmacy\DispensingController;
use App\Http\Controllers\Pharmacy\GstPurchaseReportController;
use App\Http\Controllers\Pharmacy\MedicineController;
use App\Http\Controllers\Pharmacy\PharmacyDashboardController;
use App\Http\Controllers\Pharmacy\PharmacyDisposalController;
use App\Http\Controllers\Pharmacy\PharmacyGrnController;
use App\Http\Controllers\Pharmacy\PharmacyPurchaseOrderController;
use App\Http\Controllers\Pharmacy\PharmacyPurchaseReturnController;
use App\Http\Controllers\Pharmacy\PharmacyReturnController;
use App\Http\Controllers\Pharmacy\PharmacyStockAuditController;
use App\Http\Controllers\Pharmacy\PharmacyStockTransferController;
use App\Http\Controllers\Pharmacy\PharmacySupplierController;
use App\Http\Controllers\Pharmacy\PharmacySupplierPayableController;
use App\Http\Controllers\Pharmacy\StockAdjustmentController;
use App\Http\Controllers\Pharmacy\StockBatchController;
use App\Http\Controllers\Pharmacy\StockMovementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Authenticated Hospital ERP
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'active',
])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/dashboard',
        [DashboardController::class, 'index']
    )->name('dashboard');

    Route::get(
        '/dashboard/metrics',
        [DashboardController::class, 'metrics']
    )->name('dashboard.metrics');

    /*
    |--------------------------------------------------------------------------
    | System Administration - User Management / Role Permissions
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin')->group(function () {

        Route::get(
            '/admin/users',
            [UserController::class, 'index']
        )->name('admin.users.index');

        Route::get(
            '/admin/users/create',
            [UserController::class, 'create']
        )->name('admin.users.create');

        Route::post(
            '/admin/users',
            [UserController::class, 'store']
        )->name('admin.users.store');

        Route::get(
            '/admin/users/{user}/edit',
            [UserController::class, 'edit']
        )
            ->whereNumber('user')
            ->name('admin.users.edit');

        Route::put(
            '/admin/users/{user}',
            [UserController::class, 'update']
        )
            ->whereNumber('user')
            ->name('admin.users.update');

        Route::put(
            '/admin/users/{user}/password',
            [UserController::class, 'resetPassword']
        )
            ->whereNumber('user')
            ->name('admin.users.password');

        Route::patch(
            '/admin/users/{user}/status',
            [UserController::class, 'toggleStatus']
        )
            ->whereNumber('user')
            ->name('admin.users.status');

        /*
        |--------------------------------------------------------------------------
        | Role & Permission Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/admin/role-permissions',
            [RolePermissionController::class, 'index']
        )->name('admin.role-permissions.index');

        Route::post(
            '/admin/role-permissions',
            [RolePermissionController::class, 'update']
        )->name('admin.role-permissions.update');

    });

    /*
|--------------------------------------------------------------------------
| Asset Management
|--------------------------------------------------------------------------
*/

    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            Route::resource(
                'asset-categories',
                AssetCategoryController::class
            )->except([
                'show',
                'destroy',
            ]);

            Route::resource(
                'asset-vendors',
                AssetVendorController::class
            )->except([
                'show',
                'destroy',
            ]);

            Route::resource(
                'assets',
                AssetController::class
            )->except([
                'destroy',
            ]);

            Route::get(
                'assets/{asset}/movements/create',
                [AssetMovementController::class, 'create']
            )->name('assets.movements.create');

            Route::post(
                'assets/{asset}/movements',
                [AssetMovementController::class, 'store']
            )->name('assets.movements.store');

        });
    /*
    |--------------------------------------------------------------------------
    | Admin / HR - Department & Employee Masters
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin,hr')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            Route::get(
                '/hr',
                [HrController::class, 'index']
            )->name('hr.index');

            Route::get(
                '/departments',
                [DepartmentController::class, 'index']
            )->name('departments.index');

            Route::get(
                '/departments/create',
                [DepartmentController::class, 'create']
            )->name('departments.create');

            Route::post(
                '/departments',
                [DepartmentController::class, 'store']
            )->name('departments.store');

            Route::get(
                '/departments/{department}/edit',
                [DepartmentController::class, 'edit']
            )
                ->whereNumber('department')
                ->name('departments.edit');

            Route::put(
                '/departments/{department}',
                [DepartmentController::class, 'update']
            )
                ->whereNumber('department')
                ->name('departments.update');

            Route::get(
                '/employees',
                [EmployeeController::class, 'index']
            )->name('employees.index');

            Route::get(
                '/employees/create',
                [EmployeeController::class, 'create']
            )->name('employees.create');

            Route::post(
                '/employees',
                [EmployeeController::class, 'store']
            )->name('employees.store');

            Route::get(
                '/employees/{employee}/edit',
                [EmployeeController::class, 'edit']
            )
                ->whereNumber('employee')
                ->name('employees.edit');

            Route::put(
                '/employees/{employee}',
                [EmployeeController::class, 'update']
            )
                ->whereNumber('employee')
                ->name('employees.update');

            Route::get(
                '/hr/leave-types',
                [LeaveTypeController::class, 'index']
            )->name('hr.leave-types.index');

            Route::post(
                '/hr/leave-types',
                [LeaveTypeController::class, 'store']
            )->name('hr.leave-types.store');

            Route::put(
                '/hr/leave-types/{leaveType}',
                [LeaveTypeController::class, 'update']
            )
                ->whereNumber('leaveType')
                ->name('hr.leave-types.update');

            Route::get(
                '/hr/leave-balances',
                [EmployeeLeaveBalanceController::class, 'index']
            )->name('hr.leave-balances.index');

            Route::put(
                '/hr/leave-balances/{employee}',
                [EmployeeLeaveBalanceController::class, 'update']
            )
                ->whereNumber('employee')
                ->name('hr.leave-balances.update');

            Route::get(
                '/hr/leave-requests',
                [LeaveRequestController::class, 'index']
            )->name('hr.leave-requests.index');

            Route::get(
                '/hr/leave-requests/create',
                [LeaveRequestController::class, 'create']
            )->name('hr.leave-requests.create');

            Route::post(
                '/hr/leave-requests',
                [LeaveRequestController::class, 'store']
            )->name('hr.leave-requests.store');

            Route::patch(
                '/hr/leave-requests/{leaveRequest}/approve',
                [LeaveRequestController::class, 'approve']
            )
                ->whereNumber('leaveRequest')
                ->name('hr.leave-requests.approve');

            Route::patch(
                '/hr/leave-requests/{leaveRequest}/reject',
                [LeaveRequestController::class, 'reject']
            )
                ->whereNumber('leaveRequest')
                ->name('hr.leave-requests.reject');

            Route::patch(
                '/hr/leave-requests/{leaveRequest}/cancel',
                [LeaveRequestController::class, 'cancel']
            )
                ->whereNumber('leaveRequest')
                ->name('hr.leave-requests.cancel');

            Route::get(
                '/hr/contracts',
                [EmployeeContractController::class, 'index']
            )->name('hr.contracts.index');

            Route::get(
                '/hr/contracts/create',
                [EmployeeContractController::class, 'create']
            )->name('hr.contracts.create');

            Route::post(
                '/hr/contracts',
                [EmployeeContractController::class, 'store']
            )->name('hr.contracts.store');

            Route::get(
                '/hr/contracts/{contract}/edit',
                [EmployeeContractController::class, 'edit']
            )
                ->whereNumber('contract')
                ->name('hr.contracts.edit');

            Route::put(
                '/hr/contracts/{contract}',
                [EmployeeContractController::class, 'update']
            )
                ->whereNumber('contract')
                ->name('hr.contracts.update');

            Route::post(
                '/hr/contracts/{contract}/renew',
                [EmployeeContractController::class, 'renew']
            )
                ->whereNumber('contract')
                ->name('hr.contracts.renew');

            Route::post(
                '/hr/contracts/{contract}/terminate',
                [EmployeeContractController::class, 'terminate']
            )
                ->whereNumber('contract')
                ->name('hr.contracts.terminate');

            Route::post(
                '/hr/contracts/refresh-statuses',
                [EmployeeContractController::class, 'refreshStatuses']
            )->name('hr.contracts.refresh-statuses');
            Route::get(
                '/hr/documents',
                [EmployeeDocumentController::class, 'index']
            )->name('hr.documents.index');

            Route::get(
                '/hr/documents/create',
                [EmployeeDocumentController::class, 'create']
            )->name('hr.documents.create');

            Route::post(
                '/hr/documents',
                [EmployeeDocumentController::class, 'store']
            )->name('hr.documents.store');

            Route::get(
                '/hr/documents/{document}/view',
                [EmployeeDocumentController::class, 'viewFile']
            )
                ->whereNumber('document')
                ->name('hr.documents.view');

            Route::get(
                '/hr/documents/{document}/download',
                [EmployeeDocumentController::class, 'download']
            )
                ->whereNumber('document')
                ->name('hr.documents.download');

            Route::patch(
                '/hr/documents/{document}/verify',
                [EmployeeDocumentController::class, 'verify']
            )
                ->whereNumber('document')
                ->name('hr.documents.verify');

            Route::patch(
                '/hr/documents/{document}/reject',
                [EmployeeDocumentController::class, 'reject']
            )
                ->whereNumber('document')
                ->name('hr.documents.reject');

            /*
|--------------------------------------------------------------------------
| HR - Staff Medical Benefits
|--------------------------------------------------------------------------
*/

            Route::get(
                '/hr/medical-benefits',
                [StaffMedicalBenefitController::class, 'index']
            )->name('hr.medical-benefits.index');

            Route::get(
                '/hr/medical-benefits/patient-search',
                [StaffMedicalBenefitController::class, 'searchPatients']
            )->name('hr.medical-benefits.patient-search');

            Route::post(
                '/hr/medical-benefits/employees',
                [
                    StaffMedicalBenefitController::class,
                    'storeEmployeeBenefit',
                ]
            )->name(
                'hr.medical-benefits.employees.store'
            );

            Route::get(
                '/hr/medical-benefits/{benefitAccount}',
                [StaffMedicalBenefitController::class, 'show']
            )
                ->whereNumber('benefitAccount')
                ->name('hr.medical-benefits.show');

            Route::post(
                '/hr/medical-benefits/{benefitAccount}/dependents',
                [StaffMedicalBenefitController::class, 'storeDependent']
            )
                ->whereNumber('benefitAccount')
                ->name('hr.medical-benefits.dependents.store');

            Route::patch(
                '/hr/medical-benefits/{benefitAccount}/dependents/{dependent}/deactivate',
                [StaffMedicalBenefitController::class, 'deactivateDependent']
            )
                ->whereNumber('benefitAccount')
                ->whereNumber('dependent')
                ->name('hr.medical-benefits.dependents.deactivate');

            Route::put(
                '/hr/medical-benefits/employees/{employee}/patient',
                [StaffMedicalBenefitController::class, 'linkEmployeePatient']
            )
                ->whereNumber('employee')
                ->name('hr.medical-benefits.employee-patient.link');

            Route::delete(
                '/hr/medical-benefits/employees/{employee}/patient',
                [StaffMedicalBenefitController::class, 'unlinkEmployeePatient']
            )
                ->whereNumber('employee')
                ->name('hr.medical-benefits.employee-patient.unlink');

            /*
|--------------------------------------------------------------------------
| HR - Payroll / Salary Components
|--------------------------------------------------------------------------
*/

            Route::get(
                '/hr/payroll/salary-components',
                [SalaryComponentController::class, 'index']
            )->name('hr.payroll.salary-components.index');

            Route::get(
                '/hr/payroll/salary-components/create',
                [SalaryComponentController::class, 'create']
            )->name('hr.payroll.salary-components.create');

            Route::post(
                '/hr/payroll/salary-components',
                [SalaryComponentController::class, 'store']
            )->name('hr.payroll.salary-components.store');

            Route::get(
                '/hr/payroll/salary-components/{salaryComponent}/edit',
                [SalaryComponentController::class, 'edit']
            )
                ->whereNumber('salaryComponent')
                ->name('hr.payroll.salary-components.edit');

            Route::put(
                '/hr/payroll/salary-components/{salaryComponent}',
                [SalaryComponentController::class, 'update']
            )
                ->whereNumber('salaryComponent')
                ->name('hr.payroll.salary-components.update');

            Route::patch(
                '/hr/payroll/salary-components/{salaryComponent}/toggle-status',
                [SalaryComponentController::class, 'toggleStatus']
            )
                ->whereNumber('salaryComponent')
                ->name('hr.payroll.salary-components.toggle-status');

            /*
|--------------------------------------------------------------------------
| HR - Payroll / Employee Salary Structures
|--------------------------------------------------------------------------
*/

            Route::get(
                '/hr/payroll/salary-structures',
                [EmployeeSalaryStructureController::class, 'index']
            )->name('hr.payroll.salary-structures.index');

            Route::get(
                '/hr/payroll/salary-structures/create',
                [EmployeeSalaryStructureController::class, 'create']
            )->name('hr.payroll.salary-structures.create');

            Route::post(
                '/hr/payroll/salary-structures',
                [EmployeeSalaryStructureController::class, 'store']
            )->name('hr.payroll.salary-structures.store');

            Route::get(
                '/hr/payroll/salary-structures/{salaryStructure}/edit',
                [EmployeeSalaryStructureController::class, 'edit']
            )
                ->whereNumber('salaryStructure')
                ->name('hr.payroll.salary-structures.edit');

            Route::put(
                '/hr/payroll/salary-structures/{salaryStructure}',
                [EmployeeSalaryStructureController::class, 'update']
            )
                ->whereNumber('salaryStructure')
                ->name('hr.payroll.salary-structures.update');

            Route::patch(
                '/hr/payroll/salary-structures/{salaryStructure}/activate',
                [EmployeeSalaryStructureController::class, 'activate']
            )
                ->whereNumber('salaryStructure')
                ->name('hr.payroll.salary-structures.activate');

            /*
|--------------------------------------------------------------------------
| HR - Payroll Runs
|--------------------------------------------------------------------------
*/

            Route::get(
                '/hr/payroll/runs',
                [PayrollRunController::class, 'index']
            )->name('hr.payroll.runs.index');

            Route::get(
                '/hr/payroll/runs/create',
                [PayrollRunController::class, 'create']
            )->name('hr.payroll.runs.create');

            Route::post(
                '/hr/payroll/runs',
                [PayrollRunController::class, 'store']
            )->name('hr.payroll.runs.store');

            Route::get(
                '/hr/payroll/runs/{payrollRun}',
                [PayrollRunController::class, 'show']
            )
                ->whereNumber('payrollRun')
                ->name('hr.payroll.runs.show');

            Route::get(
                '/hr/payroll/runs/{payrollRun}/entries/{payrollEntry}/payslip',
                [PayrollRunController::class, 'payslip']
            )
                ->whereNumber('payrollRun')
                ->whereNumber('payrollEntry')
                ->name('hr.payroll.runs.payslip');

            Route::post(
                '/hr/payroll/runs/{payrollRun}/entries/{payrollEntry}/payment',
                [PayrollRunController::class, 'recordPayment']
            )
                ->whereNumber('payrollRun')
                ->whereNumber('payrollEntry')
                ->name('hr.payroll.runs.payment');

            Route::post(
                '/hr/payroll/runs/{payrollRun}/bulk-payment',
                [PayrollRunController::class, 'recordBulkPayment']
            )
                ->whereNumber('payrollRun')
                ->name('hr.payroll.runs.bulk-payment');

            Route::post(
                '/hr/payroll/runs/{payrollRun}/calculate',
                [PayrollRunController::class, 'calculate']
            )
                ->whereNumber('payrollRun')
                ->name('hr.payroll.runs.calculate');

            Route::post(
                '/hr/payroll/runs/{payrollRun}/approve',
                [PayrollRunController::class, 'approve']
            )
                ->whereNumber('payrollRun')
                ->name('hr.payroll.runs.approve');

            Route::post(
                '/hr/payroll/runs/{payrollRun}/lock',
                [PayrollRunController::class, 'lock']
            )
                ->whereNumber('payrollRun')
                ->name('hr.payroll.runs.lock');

            Route::post(
                '/hr/payroll/runs/{payrollRun}/finance-vouchers',
                [PayrollRunController::class, 'createFinanceVouchers']
            )
                ->whereNumber('payrollRun')
                ->name('hr.payroll.runs.finance-vouchers');

            /*
|--------------------------------------------------------------------------
| HR - Payroll Adjustments
|--------------------------------------------------------------------------
*/

            Route::post(
                '/hr/payroll/runs/{payrollRun}/entries/{payrollEntry}/adjustments',
                [PayrollRunController::class, 'storeAdjustment']
            )
                ->whereNumber('payrollRun')
                ->whereNumber('payrollEntry')
                ->name('hr.payroll.runs.adjustments.store');

            Route::delete(
                '/hr/payroll/runs/{payrollRun}/adjustments/{payrollAdjustment}',
                [PayrollRunController::class, 'destroyAdjustment']
            )
                ->whereNumber('payrollRun')
                ->whereNumber('payrollAdjustment')
                ->name('hr.payroll.runs.adjustments.destroy');

        });

    /*
    |--------------------------------------------------------------------------
    | Admin - Bed Tariff Master
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin')->group(function () {

        Route::get(
            '/admin/bed-tariffs',
            [BedTariffController::class, 'index']
        )->name('admin.bed-tariffs.index');

        Route::post(
            '/admin/bed-tariffs',
            [BedTariffController::class, 'store']
        )->name('admin.bed-tariffs.store');

        Route::patch(
            '/admin/bed-tariffs/{bedTariff}/deactivate',
            [BedTariffController::class, 'deactivate']
        )
            ->whereNumber('bedTariff')
            ->name('admin.bed-tariffs.deactivate');

    });

    /*
    |--------------------------------------------------------------------------
    | Admin - Inpatient Master
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:admin'
    )->group(function () {

        Route::get(
            '/admin/inpatient-master',
            [InpatientMasterController::class, 'index']
        )->name('inpatient-master.index');

        Route::post(
            '/admin/inpatient-master/wards',
            [InpatientMasterController::class, 'storeWard']
        )->name('inpatient-master.wards.store');

        Route::put(
            '/admin/inpatient-master/wards/{ward}',
            [InpatientMasterController::class, 'updateWard']
        )
            ->whereNumber('ward')
            ->name('inpatient-master.wards.update');

        Route::post(
            '/admin/inpatient-master/rooms',
            [InpatientMasterController::class, 'storeRoom']
        )->name('inpatient-master.rooms.store');

        Route::put(
            '/admin/inpatient-master/rooms/{room}',
            [InpatientMasterController::class, 'updateRoom']
        )
            ->whereNumber('room')
            ->name('inpatient-master.rooms.update');

        Route::post(
            '/admin/inpatient-master/beds',
            [InpatientMasterController::class, 'storeBed']
        )->name('inpatient-master.beds.store');

        Route::put(
            '/admin/inpatient-master/beds/{bed}',
            [InpatientMasterController::class, 'updateBed']
        )
            ->whereNumber('bed')
            ->name('inpatient-master.beds.update');

    });

    /*
    |--------------------------------------------------------------------------
    | Finance / Accounts
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:admin,finance'
    )->group(function () {

        Route::get(
            '/admin/finance',
            [FinanceDashboardController::class, 'index']
        )->name('finance.dashboard');

        Route::get(
    '/admin/finance/revenue/{financeHead}',
    [FinanceDashboardController::class, 'revenueDetail']
)
    ->whereNumber('financeHead')
    ->name('finance.revenue.detail');

        Route::get(
            '/admin/finance/master',
            [FinanceMasterController::class, 'index']
        )->name('finance.master.index');

        Route::post(
            '/admin/finance/master/accounts',
            [FinanceMasterController::class, 'storeAccount']
        )->name('finance.master.accounts.store');

        Route::put(
            '/admin/finance/master/accounts/{financeAccount}',
            [FinanceMasterController::class, 'updateAccount']
        )
            ->whereNumber('financeAccount')
            ->name('finance.master.accounts.update');

        Route::post(
            '/admin/finance/master/heads',
            [FinanceMasterController::class, 'storeHead']
        )->name('finance.master.heads.store');

        Route::put(
            '/admin/finance/master/heads/{financeHead}',
            [FinanceMasterController::class, 'updateHead']
        )
            ->whereNumber('financeHead')
            ->name('finance.master.heads.update');

        /*
|--------------------------------------------------------------------------
| Tally Integration
|--------------------------------------------------------------------------
*/

        Route::get(
            '/admin/finance/tally',
            [TallyIntegrationController::class, 'index']
        )->name('finance.tally.index');

        Route::get(
            '/admin/finance/tally/trial-balance',
            [TallyIntegrationController::class, 'trialBalance']
        )->name('finance.tally.trial-balance');

        Route::get(
            '/admin/finance/tally/income-expenditure',
            [TallyIntegrationController::class, 'incomeAndExpenditure']
        )->name('finance.tally.income-expenditure');

        Route::get(
            '/admin/finance/tally/balance-sheet',
            [TallyIntegrationController::class, 'balanceSheet']
        )->name('finance.tally.balance-sheet');

        Route::get(
            '/admin/finance/tally/cash-flow',
            [TallyIntegrationController::class, 'cashFlow']
        )->name('finance.tally.cash-flow');

                  Route::get(
            '/admin/finance/tally/reconciliation',
            [TallyReconciliationController::class, 'index']
        )->name('finance.tally.reconciliation');

        Route::get(
            '/admin/finance/tally/reconciliation/erp/{financeVoucher}',
            [TallyReconciliationController::class, 'showErp']
        )
            ->whereNumber('financeVoucher')
            ->name('finance.tally.reconciliation.erp');

        Route::post(
            '/admin/finance/tally/reconciliation/erp/{financeVoucher}/tally/{tallyVoucher}/match',
            [TallyReconciliationController::class, 'manualMatch']
        )
            ->whereNumber('financeVoucher')
            ->whereNumber('tallyVoucher')
            ->name('finance.tally.reconciliation.manual-match');

        Route::put(
            '/admin/finance/tally/heads/{financeHead}',
            [TallyIntegrationController::class, 'saveHead']
        )
            ->whereNumber('financeHead')
            ->name('finance.tally.heads.update');

        Route::put(
            '/admin/finance/tally/accounts/{financeAccount}',
            [TallyIntegrationController::class, 'saveAccount']
        )
            ->whereNumber('financeAccount')
            ->name('finance.tally.accounts.update');

        /*
        |--------------------------------------------------------------------------
        | Tally Voucher Export / Posting
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/admin/finance/tally/exports',
            [TallyExportController::class, 'index']
        )->name('finance.tally.exports.index');

        Route::post(
            '/admin/finance/tally/exports/{financeVoucher}/download',
            [TallyExportController::class, 'download']
        )->name('finance.tally.exports.download');

        Route::post(
            '/admin/finance/tally/exports/{financeVoucher}/send',
            [TallyExportController::class, 'send']
        )->name('finance.tally.exports.send');

        Route::post(
            '/admin/finance/tally/exports/{financeVoucher}/confirm',
            [TallyExportController::class, 'confirm']
        )->name('finance.tally.exports.confirm');

        Route::post(
            '/admin/finance/tally/exports/{financeVoucher}/fail',
            [TallyExportController::class, 'markFailed']
        )->name('finance.tally.exports.fail');

        Route::post(
            '/admin/finance/tally/exports/{financeVoucher}/re-export',
            [TallyExportController::class, 'reExport']
        )->name('finance.tally.exports.re-export');

        Route::post(
            '/admin/finance/tally/exports/{financeVoucher}/retry-direct',
            [TallyExportController::class, 'retryDirect']
        )->name('finance.tally.exports.retry-direct');

        /*
        |--------------------------------------------------------------------------
        | Finance Vouchers
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/admin/finance/vouchers',
            [FinanceVoucherController::class, 'index']
        )->name('finance.vouchers.index');

        Route::get(
            '/admin/finance/vouchers/create',
            [FinanceVoucherController::class, 'create']
        )->name('finance.vouchers.create');

        Route::post(
            '/admin/finance/vouchers',
            [FinanceVoucherController::class, 'store']
        )->name('finance.vouchers.store');

        Route::get(
            '/admin/finance/vouchers/{financeVoucher}',
            [FinanceVoucherController::class, 'show']
        )
            ->whereNumber('financeVoucher')
            ->name('finance.vouchers.show');

        Route::patch(
            '/admin/finance/vouchers/{financeVoucher}/post',
            [FinanceVoucherController::class, 'post']
        )
            ->whereNumber('financeVoucher')
            ->name('finance.vouchers.post');

        Route::patch(
            '/admin/finance/vouchers/{financeVoucher}/cancel',
            [FinanceVoucherController::class, 'cancel']
        )
            ->whereNumber('financeVoucher')
            ->name('finance.vouchers.cancel');

        /*
|--------------------------------------------------------------------------
| Finance Reports
|--------------------------------------------------------------------------
*/

        Route::get(
            '/admin/finance/reports',
            [FinanceReportController::class, 'index']
        )->name('finance.reports.index');

    });
    /*
    |--------------------------------------------------------------------------
    | Patient Registration
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:patients.create'
    )->group(function () {

        Route::get(
            '/patients/create',
            [PatientController::class, 'create']
        )->name('patients.create');

        Route::post(
            '/patients',
            [PatientController::class, 'store']
        )->name('patients.store');
    });

    /*
    |--------------------------------------------------------------------------
    | Patient Read Access
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:patients.view'
    )->group(function () {

        Route::get(
            '/patients',
            [PatientController::class, 'index']
        )->name('patients.index');

        Route::get(
            '/patients/{patient}',
            [PatientController::class, 'show']
        )
            ->whereNumber('patient')
            ->name('patients.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Patient Edit Access
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:patients.edit'
    )->group(function () {

        Route::get(
            '/patients/{patient}/edit',
            [PatientController::class, 'edit']
        )
            ->whereNumber('patient')
            ->name('patients.edit');

        Route::put(
            '/patients/{patient}',
            [PatientController::class, 'update']
        )
            ->whereNumber('patient')
            ->name('patients.update');
    });

    /*
    |--------------------------------------------------------------------------
    | Patient Card
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:patients.card'
    )->group(function () {

        Route::get(
            '/patients/{patient}/card',
            [PatientController::class, 'card']
        )
            ->whereNumber('patient')
            ->name('patients.card');

            Route::get(
    '/patients/{patient}/sticker',
    [PatientController::class, 'sticker']
)
    ->whereNumber('patient')
    ->name('patients.sticker');
    });

    /*
    |--------------------------------------------------------------------------
    | Emergency Department
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Emergency - Registration
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:emergency.create'
    )->group(function () {

        Route::get(
            '/emergency/register',
            [EmergencyVisitController::class, 'create']
        )->name('emergency.create');

        Route::post(
            '/emergency',
            [EmergencyVisitController::class, 'store']
        )->name('emergency.store');

    });

    /*
    |--------------------------------------------------------------------------
    | Emergency - Queue / Read Access
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:emergency.view'
    )->group(function () {

        Route::get(
            '/emergency',
            [EmergencyVisitController::class, 'index']
        )->name('emergency.index');

        Route::get(
            '/emergency/{emergencyVisit}',
            [EmergencyVisitController::class, 'show']
        )
            ->whereNumber('emergencyVisit')
            ->name('emergency.show');

        Route::get(
            '/emergency/{emergencyVisit}/consultation-payment',
            [EmergencyVisitController::class, 'consultationPayment']
        )
            ->whereNumber('emergencyVisit')
            ->name('emergency.consultation-payment');

    });

    /*
    |--------------------------------------------------------------------------
    | Emergency - Nursing Triage
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:emergency.triage'
    )->group(function () {

        Route::get(
            '/emergency/{emergencyVisit}/triage',
            [EmergencyTriageController::class, 'create']
        )
            ->whereNumber('emergencyVisit')
            ->name('emergency.triage.create');

        Route::post(
            '/emergency/{emergencyVisit}/triage',
            [EmergencyTriageController::class, 'store']
        )
            ->whereNumber('emergencyVisit')
            ->name('emergency.triage.store');

    });
    /*
|--------------------------------------------------------------------------
| Emergency - Clinical Visit Sheet
|--------------------------------------------------------------------------
*/

    Route::middleware(
        'permission:emergency.view'
    )->group(function () {

        Route::get(
            '/emergency/{emergencyVisit}/clinical-note',
            [EmergencyClinicalNoteController::class, 'create']
        )
            ->whereNumber('emergencyVisit')
            ->name('emergency.clinical-note.create');

        Route::post(
            '/emergency/{emergencyVisit}/clinical-note',
            [EmergencyClinicalNoteController::class, 'store']
        )
            ->whereNumber('emergencyVisit')
            ->name('emergency.clinical-note.store');

        Route::get(
            '/emergency/{emergencyVisit}/clinical-note/print',
            [EmergencyClinicalNoteController::class, 'print']
        )
            ->whereNumber('emergencyVisit')
            ->name('emergency.clinical-note.print');

    });

    /*
    |--------------------------------------------------------------------------
    | Emergency - Admission
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:emergency.admit'
    )->group(function () {

        Route::get(
            '/emergency/{emergencyVisit}/admit',
            [EmergencyAdmissionController::class, 'create']
        )
            ->whereNumber('emergencyVisit')
            ->name('emergency.admission.create');

        Route::post(
            '/emergency/{emergencyVisit}/admit',
            [EmergencyAdmissionController::class, 'store']
        )
            ->whereNumber('emergencyVisit')
            ->name('emergency.admission.store');

    });

    /*
|--------------------------------------------------------------------------
| OPD - Admission
|--------------------------------------------------------------------------
*/

    Route::middleware(
        'permission:ipd.admit'
    )->group(function () {

        Route::get(
            '/opd/{encounter}/admit',
            [OpdAdmissionController::class, 'create']
        )
            ->whereNumber('encounter')
            ->name('opd.admission.create');

        Route::post(
            '/opd/{encounter}/admit',
            [OpdAdmissionController::class, 'store']
        )
            ->whereNumber('encounter')
            ->name('opd.admission.store');

    });
    /*
    |--------------------------------------------------------------------------
    | IPD - Inpatient Census / Admission View
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:ipd.view'
    )->group(function () {

        Route::get(
            '/ipd',
            [AdmissionController::class, 'index']
        )->name('ipd.index');

        Route::get(
            '/ipd/{admission}',
            [AdmissionController::class, 'show']
        )
            ->whereNumber('admission')
            ->name('ipd.show');

    });

    /*
|--------------------------------------------------------------------------
| IPD - Bed Management
|--------------------------------------------------------------------------
*/

    Route::middleware(
        'permission:ipd.view'
    )->group(function () {

        Route::get(
            '/ipd/bed-management',
            [BedManagementController::class, 'index']
        )->name('ipd.bed-management.index');

        Route::put(
            '/ipd/bed-management/{bed}/status',
            [BedManagementController::class, 'updateStatus']
        )
            ->whereNumber('bed')
            ->name('ipd.bed-management.status');

    });
    /*
    |--------------------------------------------------------------------------
    | IPD - Bed Transfer
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:ipd.transfer'
    )->group(function () {

        Route::get(
            '/ipd/{admission}/transfer',
            [BedTransferController::class, 'create']
        )
            ->whereNumber('admission')
            ->name('ipd.transfer.create');

        Route::post(
            '/ipd/{admission}/transfer',
            [BedTransferController::class, 'store']
        )
            ->whereNumber('admission')
            ->name('ipd.transfer.store');

    });

    /*
    |--------------------------------------------------------------------------
    | IPD - Admission Closure
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:ipd.close'
    )->group(function () {

        Route::get(
            '/ipd/{admission}/close',
            [AdmissionClosureController::class, 'create']
        )
            ->whereNumber('admission')
            ->name('ipd.closure.create');

        Route::post(
            '/ipd/{admission}/close',
            [AdmissionClosureController::class, 'store']
        )
            ->whereNumber('admission')
            ->name('ipd.closure.store');

    });

    Route::middleware(
        'permission:ipd.discharge-summary.view'
    )->group(function () {

        /*
        |--------------------------------------------------------------------------
        | IPD - Discharge Summary View / Print
        |--------------------------------------------------------------------------
        |
        | Reception and nursing may view / print an already prepared summary.
        | They cannot edit the clinical content.
        |
        */

        Route::get(
            '/ipd/{admission}/discharge-summary',
            [DischargeSummaryController::class, 'show']
        )
            ->whereNumber('admission')
            ->name('ipd.discharge-summary.show');

    });

    /*
    |--------------------------------------------------------------------------
    | IPD - Discharge Summary Clinical Editing
    |--------------------------------------------------------------------------
    |
    | Only doctors and administrators may create or modify the clinical
    | discharge summary.
    |
    */

    Route::middleware(
        'permission:ipd.discharge-summary.edit'
    )->group(function () {

        Route::get(
            '/ipd/{admission}/discharge-summary/edit',
            [DischargeSummaryController::class, 'edit']
        )
            ->whereNumber('admission')
            ->name('ipd.discharge-summary.edit');

        Route::put(
            '/ipd/{admission}/discharge-summary',
            [DischargeSummaryController::class, 'update']
        )
            ->whereNumber('admission')
            ->name('ipd.discharge-summary.update');

        Route::post(
            '/ipd/{admission}/discharge-summary/finalise',
            [DischargeSummaryController::class, 'finalise']
        )
            ->whereNumber('admission')
            ->name('ipd.discharge-summary.finalise');

    });

    /*
    |--------------------------------------------------------------------------
    | OPD Queue / View
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:opd.view'
    )->group(function () {

        Route::get(
            '/opd',
            [OpdController::class, 'index']
        )->name('opd.index');

    });

    Route::middleware(
        'permission:opd.card'
    )->group(function () {

        Route::get(
            '/opd/{encounter}/card',
            [OpdController::class, 'card']
        )
            ->whereNumber('encounter')
            ->name('opd.card');

    });

    /*
    |--------------------------------------------------------------------------
    | OPD Registration
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:opd.create'
    )->group(function () {
        Route::get(
            '/opd/register',
            [OpdController::class, 'create']
        )->name('opd.create');

        Route::post(
            '/opd',
            [OpdController::class, 'store']
        )->name('opd.store');
    });

    /*
    |--------------------------------------------------------------------------
    | OPD Payment Receipt
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:billing.view'
    )->group(function () {

        Route::get(
            '/payments/{payment}/receipt',
            [OpdController::class, 'receipt']
        )
            ->whereNumber('payment')
            ->name('payments.receipt');
    });

    /*
    |--------------------------------------------------------------------------
    | Nursing Station
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:nursing.view'
    )->group(function () {

        Route::get(
            '/nursing',
            [NursingController::class, 'index']
        )->name('nursing.index');

    });

    Route::middleware(
        'permission:nursing.vitals'
    )->group(function () {

        Route::get(
            '/nursing/{encounter}/vitals',
            [NursingController::class, 'createVitals']
        )
            ->whereNumber('encounter')
            ->name('nursing.vitals.create');

        Route::post(
            '/nursing/{encounter}/vitals',
            [NursingController::class, 'storeVitals']
        )
            ->whereNumber('encounter')
            ->name('nursing.vitals.store');

    });

    /*
    |--------------------------------------------------------------------------
    | Service Master
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:system.services'
    )->group(function () {
        Route::resource(
            'services',
            ServiceController::class
        )->only([
            'index',
            'create',
            'store',
            'edit',
            'update',
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Billing Counter
    |--------------------------------------------------------------------------
    */

    /*
|--------------------------------------------------------------------------
| Billing Counter - View
|--------------------------------------------------------------------------
*/

    Route::middleware(
        'permission:billing.view'
    )->group(function () {

        Route::get(
            '/billing',
            [BillingController::class, 'index']
        )->name('billing.index');

        Route::get(
            '/billing/orders/{serviceOrder}/payment',
            [BillingController::class, 'payment']
        )
            ->whereNumber('serviceOrder')
            ->name('billing.payment');

        Route::get(
            '/billing/invoices/{invoice}/payment',
            [BillingController::class, 'invoicePayment']
        )
            ->whereNumber('invoice')
            ->name('billing.invoice.payment');

        Route::get(
            '/billing/payments/{payment}/receipt',
            [BillingController::class, 'receipt']
        )
            ->whereNumber('payment')
            ->name('billing.receipt');
    });

    /*
    |--------------------------------------------------------------------------
    | Billing Counter - Investigation Ordering
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:billing.order-investigations'
    )->group(function () {

        Route::get(
            '/billing/{encounter}/investigations',
            [BillingController::class, 'create']
        )
            ->whereNumber('encounter')
            ->name('billing.create');

        Route::post(
            '/billing/{encounter}/investigations',
            [BillingController::class, 'store']
        )
            ->whereNumber('encounter')
            ->name('billing.store');
    });

    /*
    |--------------------------------------------------------------------------
    | Billing Counter - Payment Collection
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:billing.collect'
    )->group(function () {

        Route::post(
            '/billing/orders/{serviceOrder}/payment',
            [BillingController::class, 'processPayment']
        )
            ->whereNumber('serviceOrder')
            ->name('billing.payment.store');

        Route::post(
            '/billing/invoices/{invoice}/payment',
            [BillingController::class, 'processInvoicePayment']
        )
            ->whereNumber('invoice')
            ->name('billing.invoice.payment.store');
    });

    /*
|--------------------------------------------------------------------------
| Inpatient Billing - View
|--------------------------------------------------------------------------
*/

    Route::middleware(
        'permission:ip-billing.view'
    )->group(function () {

        Route::get(
            '/ip-billing',
            [IpBillingController::class, 'index']
        )->name('ip-billing.index');

        Route::get(
            '/ip-billing/{admission}/final-bill',
            [IpBillingController::class, 'finalBill']
        )
            ->whereNumber('admission')
            ->name('ip-billing.final-bill');
    });

    /*
    |--------------------------------------------------------------------------
    | Inpatient Billing - Management
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:ip-billing.manage'
    )->group(function () {

        Route::post(
            '/ip-billing/{admission}/generate-bed-charges',
            [IpBillingController::class, 'generateBedCharges']
        )
            ->whereNumber('admission')
            ->name('ip-billing.generate-bed-charges');

        Route::post(
            '/ip-billing/{admission}/payment',
            [IpBillingController::class, 'receivePayment']
        )
            ->whereNumber('admission')
            ->name('ip-billing.payment.store');

        Route::post(
            '/ip-billing/{admission}/staff-medical-benefit',
            [IpBillingController::class, 'applyStaffMedicalBenefit']
        )
            ->whereNumber('admission')
            ->name('ip-billing.staff-medical-benefit.store');

        Route::post(
            '/ip-billing/{admission}/charges',
            [IpBillingController::class, 'storeCharge']
        )
            ->whereNumber('admission')
            ->name('ip-billing.charges.store');

        Route::post(
            '/ip-billing/{admission}/mhis',
            [IpBillingController::class, 'storeMhisClaim']
        )
            ->whereNumber('admission')
            ->name('ip-billing.mhis.store');

        Route::post(
            '/ip-billing/{admission}/mhis/adjustments',
            [IpBillingController::class, 'storeMhisAdjustment']
        )
            ->whereNumber('admission')
            ->name('ip-billing.mhis-adjustments.store');

        Route::post(
            '/ip-billing/{admission}/mhis/receipts',
            [IpBillingController::class, 'storeMhisReceipt']
        )
            ->whereNumber('admission')
            ->name('ip-billing.mhis.receipts.store');

        Route::post(
            '/ip-billing/{admission}/finalize',
            [IpBillingController::class, 'finalizeBill']
        )
            ->whereNumber('admission')
            ->name('ip-billing.finalize');
    });

    /*
|--------------------------------------------------------------------------
| Inpatient Billing - Patient Refunds
|--------------------------------------------------------------------------
*/

    Route::middleware(
        'permission:ip-billing.refund'
    )->group(function () {

        Route::post(
            '/ip-billing/{admission}/refunds',
            [IpBillingController::class, 'storeRefund']
        )
            ->whereNumber('admission')
            ->name('ip-billing.refunds.store');
    });
    /*
    |--------------------------------------------------------------------------
    | Charity / Write-off Requests
    |--------------------------------------------------------------------------
    |
    | Billing staff and administrators may submit a request.
    | Submission does not alter the patient's bill.
    |
    */

    Route::middleware(
        'role:admin,billing'
    )->group(function () {

        Route::post(
            '/charity-adjustments',
            [
                CharityAdjustmentController::class,
                'store',
            ]
        )
            ->name(
                'charity-adjustments.store'
            );

    });

    /*
    |--------------------------------------------------------------------------
    | Charity / Write-off Approval
    |--------------------------------------------------------------------------
    |
    | Only authorized administrative / financial users may approve,
    | reject or apply a charity adjustment.
    |
    */

    Route::middleware(
        'role:admin,finance,management'
    )->group(function () {

        Route::post(
            '/charity-adjustments/{charityAdjustment}/approve',
            [
                CharityAdjustmentController::class,
                'approve',
            ]
        )
            ->whereNumber(
                'charityAdjustment'
            )
            ->name(
                'charity-adjustments.approve'
            );

        Route::post(
            '/charity-adjustments/{charityAdjustment}/reject',
            [
                CharityAdjustmentController::class,
                'reject',
            ]
        )
            ->whereNumber(
                'charityAdjustment'
            )
            ->name(
                'charity-adjustments.reject'
            );

        Route::post(
            '/charity-adjustments/{charityAdjustment}/apply',
            [
                CharityAdjustmentController::class,
                'apply',
            ]
        )
            ->whereNumber(
                'charityAdjustment'
            )
            ->name(
                'charity-adjustments.apply'
            );

    });
    /*
    |--------------------------------------------------------------------------
    | IPD Nursing / Billing Shared Financial Actions
    |--------------------------------------------------------------------------
    |
    | Nursing may view the inpatient billing record, collect admission
    | advances, and print advance receipts. Doctors are intentionally
    | excluded from these financial actions.
    |
    */

    /*
|--------------------------------------------------------------------------
| IP Billing - View
|--------------------------------------------------------------------------
*/

    Route::middleware(
        'permission:ip-billing.view'
    )->group(function () {

        Route::get(
            '/ip-billing/{admission}',
            [IpBillingController::class, 'show']
        )
            ->whereNumber('admission')
            ->name('ip-billing.show');
    });

    /*
    |--------------------------------------------------------------------------
    | IP Billing - Advance Collection
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:ip-billing.advance'
    )->group(function () {

        Route::get(
            '/ip-billing/advances/{ipBillingAdvance}/receipt',
            [IpBillingController::class, 'advanceReceipt']
        )
            ->whereNumber('ipBillingAdvance')
            ->name('ip-billing.advance.receipt');

        Route::post(
            '/ip-billing/{admission}/advance',
            [IpBillingController::class, 'receiveAdvance']
        )
            ->whereNumber('admission')
            ->name('ip-billing.advance.store');
    });

    /*
    |--------------------------------------------------------------------------
    | IPD Investigation Ordering
    |--------------------------------------------------------------------------
    |
    | Doctors and nursing staff may order investigations for admitted
    | patients. This does not grant doctors access to advance collection
    | or the other inpatient billing functions.
    |
    */

    Route::middleware(
        'permission:ip-billing.order-investigations'
    )->group(function () {
        Route::get(
            '/ip-billing/{admission}/investigations',
            [IpBillingController::class, 'createInvestigationOrder']
        )
            ->whereNumber('admission')
            ->name('ip-billing.investigations.create');

        Route::post(
            '/ip-billing/{admission}/investigations',
            [IpBillingController::class, 'storeInvestigationOrder']
        )
            ->whereNumber('admission')
            ->name('ip-billing.investigations.store');

    });

    /*
    |--------------------------------------------------------------------------
    | Diagnostic Item Processing
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:laboratory.results,radiology.report'
    )->group(function () {

        Route::patch(
            '/diagnostics/items/{serviceOrderItem}/status',
            [DiagnosticWorklistController::class, 'updateStatus']
        )
            ->whereNumber('serviceOrderItem')
            ->name('diagnostics.items.status');

        Route::post(
            '/diagnostics/orders/{serviceOrderId}/start-processing',
            [DiagnosticWorklistController::class, 'bulkStartProcessing']
        )
            ->whereNumber('serviceOrderId')
            ->name('diagnostics.orders.start-processing');
    });

    /*
    |--------------------------------------------------------------------------
    | Laboratory
    |--------------------------------------------------------------------------
    */

    /*
|--------------------------------------------------------------------------
| Laboratory - View
|--------------------------------------------------------------------------
*/

    Route::middleware(
        'permission:laboratory.view'
    )->group(function () {

        Route::get(
            '/laboratory',
            [DiagnosticWorklistController::class, 'laboratory']
        )->name('laboratory.index');

        Route::get(
            '/diagnostics/items/{serviceOrderItem}/result/view',
            [DiagnosticWorklistController::class, 'showResult']
        )
            ->whereNumber('serviceOrderItem')
            ->name('diagnostics.items.result.show');

        Route::get(
            '/diagnostics/orders/{serviceOrderId}/results/group',
            [DiagnosticWorklistController::class, 'showGroupedResults']
        )
            ->whereNumber('serviceOrderId')
            ->name('diagnostics.orders.results.group');
    });

    /*
    |--------------------------------------------------------------------------
    | Laboratory - Sample Processing
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:laboratory.sample'
    )->group(function () {

        Route::get(
            '/diagnostics/items/{serviceOrderItem}/sample',
            [DiagnosticSampleController::class, 'create']
        )
            ->whereNumber('serviceOrderItem')
            ->name('diagnostics.items.sample.create');

        Route::post(
            '/diagnostics/items/{serviceOrderItem}/sample',
            [DiagnosticSampleController::class, 'store']
        )
            ->whereNumber('serviceOrderItem')
            ->name('diagnostics.items.sample.store');

        Route::post(
            '/diagnostics/orders/{serviceOrderId}/samples/collect',
            [DiagnosticSampleController::class, 'bulkStore']
        )
            ->whereNumber('serviceOrderId')
            ->name('diagnostics.orders.samples.bulk-store');

        Route::get(
            '/diagnostics/samples/{sampleNo}/label',
            [DiagnosticSampleController::class, 'printLabel']
        )
            ->name('diagnostics.samples.label');

        Route::get(
            '/diagnostics/items/{serviceOrderItem}/sample/reject',
            [DiagnosticSampleController::class, 'rejectForm']
        )
            ->whereNumber('serviceOrderItem')
            ->name('diagnostics.items.sample.reject-form');

        Route::post(
            '/diagnostics/items/{serviceOrderItem}/sample/reject',
            [DiagnosticSampleController::class, 'reject']
        )
            ->whereNumber('serviceOrderItem')
            ->name('diagnostics.items.sample.reject');
    });

    /*
    |--------------------------------------------------------------------------
    | Laboratory - Result Entry
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:laboratory.results'
    )->group(function () {

        Route::get(
            '/diagnostics/items/{serviceOrderItem}/result',
            [DiagnosticWorklistController::class, 'editResult']
        )
            ->whereNumber('serviceOrderItem')
            ->name('diagnostics.items.result.edit');

        Route::post(
            '/diagnostics/items/{serviceOrderItem}/result',
            [DiagnosticWorklistController::class, 'saveResult']
        )
            ->whereNumber('serviceOrderItem')
            ->name('diagnostics.items.result.save');
    });

    /*
    |--------------------------------------------------------------------------
    | Laboratory - Parameter Master
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:laboratory.parameters'
    )->group(function () {

        Route::get(
            '/admin/laboratory-parameters',
            [LaboratoryTestParameterController::class, 'index']
        )->name('admin.laboratory-parameters.index');

        Route::get(
            '/admin/laboratory-parameters/{service}/edit',
            [LaboratoryTestParameterController::class, 'edit']
        )
            ->whereNumber('service')
            ->name('admin.laboratory-parameters.edit');

        Route::put(
            '/admin/laboratory-parameters/{service}',
            [LaboratoryTestParameterController::class, 'update']
        )
            ->whereNumber('service')
            ->name('admin.laboratory-parameters.update');
    });

    /*
    |--------------------------------------------------------------------------
    | Imaging / Radiology
    |--------------------------------------------------------------------------
    */

    /*
|--------------------------------------------------------------------------
| Imaging / Radiology - View
|--------------------------------------------------------------------------
*/

    Route::middleware(
        'permission:radiology.view'
    )->group(function () {

        Route::get(
            '/imaging',
            [DiagnosticWorklistController::class, 'imaging']
        )->name('imaging.index');

        Route::get(
            '/diagnostics/items/{serviceOrderItem}/imaging-report/view',
            [DiagnosticWorklistController::class, 'showImagingReport']
        )
            ->whereNumber('serviceOrderItem')
            ->name('diagnostics.items.imaging-report.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Imaging / Radiology - Reporting
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:radiology.report'
    )->group(function () {

        Route::get(
            '/diagnostics/items/{serviceOrderItem}/imaging-report',
            [DiagnosticWorklistController::class, 'editImagingReport']
        )
            ->whereNumber('serviceOrderItem')
            ->name('diagnostics.items.imaging-report.edit');

        Route::post(
            '/diagnostics/items/{serviceOrderItem}/imaging-report',
            [DiagnosticWorklistController::class, 'saveImagingReport']
        )
            ->whereNumber('serviceOrderItem')
            ->name('diagnostics.items.imaging-report.save');
    });

    /*
    |--------------------------------------------------------------------------
    | Pharmacy
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'permission:pharmacy.view'
    )->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Pharmacy Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/pharmacy',
            [PharmacyDashboardController::class, 'index']
        )->name('pharmacy.dashboard');

        /*
        |--------------------------------------------------------------------------
        | Pharmacy - Dispensing / Sales
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/pharmacy/dispensing',
            [DispensingController::class, 'index']
        )->name('pharmacy.dispensing.index');

        Route::get(
            '/pharmacy/dispensing/create',
            [DispensingController::class, 'create']
        )
            ->middleware('permission:pharmacy.dispense')
            ->name('pharmacy.dispensing.create');

        Route::post(
            '/pharmacy/dispensing',
            [DispensingController::class, 'store']
        )
            ->middleware('permission:pharmacy.dispense')
            ->name('pharmacy.dispensing.store');

        Route::get(
            '/pharmacy/dispensing/{sale}/receipt',
            [DispensingController::class, 'receipt']
        )
            ->whereNumber('sale')
            ->name('pharmacy.dispensing.receipt');

        Route::get(
            '/pharmacy/dispensing/{sale}',
            [DispensingController::class, 'show']
        )
            ->whereNumber('sale')
            ->name('pharmacy.dispensing.show');

        /*
        |--------------------------------------------------------------------------
        | Pharmacy - Patient Returns
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/pharmacy/dispensing/{sale}/return',
            [PharmacyReturnController::class, 'create']
        )
            ->whereNumber('sale')
            ->middleware('permission:pharmacy.returns')
            ->name('pharmacy.returns.create');

        Route::post(
            '/pharmacy/dispensing/{sale}/return',
            [PharmacyReturnController::class, 'store']
        )
            ->whereNumber('sale')
            ->middleware('permission:pharmacy.returns')
            ->name('pharmacy.returns.store');

        Route::get(
            '/pharmacy/returns/{pharmacyReturn}/receipt',
            [PharmacyReturnController::class, 'receipt']
        )
            ->whereNumber('pharmacyReturn')
            ->name('pharmacy.returns.receipt');

        /*
        |--------------------------------------------------------------------------
        | Pharmacy - Medicine Master
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/pharmacy/medicines',
            [MedicineController::class, 'index']
        )->name('pharmacy.medicines.index');

        Route::get(
            '/pharmacy/medicines/create',
            [MedicineController::class, 'create']
        )
            ->middleware('permission:pharmacy.master')
            ->name('pharmacy.medicines.create');

        Route::post(
            '/pharmacy/medicines',
            [MedicineController::class, 'store']
        )
            ->middleware('permission:pharmacy.master')
            ->name('pharmacy.medicines.store');

        Route::get(
            '/pharmacy/medicines/{medicine}/edit',
            [MedicineController::class, 'edit']
        )
            ->whereNumber('medicine')
            ->middleware('permission:pharmacy.master')
            ->name('pharmacy.medicines.edit');

        Route::put(
            '/pharmacy/medicines/{medicine}',
            [MedicineController::class, 'update']
        )
            ->whereNumber('medicine')
            ->middleware('permission:pharmacy.master')
            ->name('pharmacy.medicines.update');

        Route::patch(
            '/pharmacy/medicines/{medicine}/status',
            [MedicineController::class, 'toggleStatus']
        )
            ->whereNumber('medicine')
            ->middleware('permission:pharmacy.master')
            ->name('pharmacy.medicines.status');

        /*
        |--------------------------------------------------------------------------
        | Pharmacy - Stock Batches
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/pharmacy/stock-batches',
            [StockBatchController::class, 'index']
        )->name('pharmacy.stock-batches.index');

        Route::get(
            '/pharmacy/stock-batches/create',
            [StockBatchController::class, 'create']
        )
            ->middleware('permission:pharmacy.opening-stock.create')
            ->name('pharmacy.stock-batches.create');

        Route::post(
            '/pharmacy/stock-batches',
            [StockBatchController::class, 'store']
        )
            ->middleware('permission:pharmacy.opening-stock.create')
            ->name('pharmacy.stock-batches.store');

        Route::get(
            '/pharmacy/stock-batches/{stockBatch}/movements',
            [StockMovementController::class, 'index']
        )
            ->whereNumber('stockBatch')
            ->name('pharmacy.stock-batches.movements');

        Route::get(
            '/pharmacy/stock-batches/{stockBatch}/edit',
            [StockBatchController::class, 'edit']
        )
            ->whereNumber('stockBatch')
            ->middleware('permission:pharmacy.master')
            ->name('pharmacy.stock-batches.edit');

        Route::put(
            '/pharmacy/stock-batches/{stockBatch}',
            [StockBatchController::class, 'update']
        )
            ->whereNumber('stockBatch')
            ->middleware('permission:pharmacy.master')
            ->name('pharmacy.stock-batches.update');

        Route::patch(
            '/pharmacy/stock-batches/{stockBatch}/status',
            [StockBatchController::class, 'toggleStatus']
        )
            ->whereNumber('stockBatch')
            ->middleware('permission:pharmacy.master')
            ->name('pharmacy.stock-batches.status');

        /*
        |--------------------------------------------------------------------------
        | Pharmacy - Manual Stock Adjustments
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/pharmacy/stock-batches/{stockBatch}/adjust',
            [StockAdjustmentController::class, 'create']
        )
            ->whereNumber('stockBatch')
            ->middleware(
                'permission:pharmacy.stock-adjustment.create'
            )
            ->name('pharmacy.stock-batches.adjust');

        Route::post(
            '/pharmacy/stock-batches/{stockBatch}/adjust',
            [StockAdjustmentController::class, 'store']
        )
            ->whereNumber('stockBatch')
            ->middleware(
                'permission:pharmacy.stock-adjustment.create'
            )
            ->name('pharmacy.stock-batches.adjust.store');

        /*
        |--------------------------------------------------------------------------
        | Pharmacy - Disposal / Write-off
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/pharmacy/disposals',
            [PharmacyDisposalController::class, 'index']
        )->name('pharmacy.disposals.index');

        Route::get(
            '/pharmacy/stock-batches/{stockBatch}/dispose',
            [PharmacyDisposalController::class, 'create']
        )
            ->whereNumber('stockBatch')
            ->middleware(
                'permission:pharmacy.disposal.create'
            )
            ->name('pharmacy.disposals.create');

        Route::post(
            '/pharmacy/stock-batches/{stockBatch}/dispose',
            [PharmacyDisposalController::class, 'store']
        )
            ->whereNumber('stockBatch')
            ->middleware(
                'permission:pharmacy.disposal.create'
            )
            ->name('pharmacy.disposals.store');

        Route::get(
            '/pharmacy/disposals/{pharmacyDisposal}/receipt',
            [PharmacyDisposalController::class, 'receipt']
        )
            ->whereNumber('pharmacyDisposal')
            ->name('pharmacy.disposals.receipt');

        /*
        |--------------------------------------------------------------------------
        | Pharmacy - Supplier Master
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/pharmacy/suppliers',
            [PharmacySupplierController::class, 'index']
        )->name('pharmacy.suppliers.index');

        Route::get(
            '/pharmacy/suppliers/create',
            [PharmacySupplierController::class, 'create']
        )
            ->middleware('permission:pharmacy.master')
            ->name('pharmacy.suppliers.create');

        Route::post(
            '/pharmacy/suppliers',
            [PharmacySupplierController::class, 'store']
        )
            ->middleware('permission:pharmacy.master')
            ->name('pharmacy.suppliers.store');

        Route::get(
            '/pharmacy/suppliers/{pharmacySupplier}/edit',
            [PharmacySupplierController::class, 'edit']
        )
            ->whereNumber('pharmacySupplier')
            ->middleware('permission:pharmacy.master')
            ->name('pharmacy.suppliers.edit');

        Route::put(
            '/pharmacy/suppliers/{pharmacySupplier}',
            [PharmacySupplierController::class, 'update']
        )
            ->whereNumber('pharmacySupplier')
            ->middleware('permission:pharmacy.master')
            ->name('pharmacy.suppliers.update');

        Route::patch(
            '/pharmacy/suppliers/{pharmacySupplier}/status',
            [PharmacySupplierController::class, 'toggleStatus']
        )
            ->whereNumber('pharmacySupplier')
            ->middleware('permission:pharmacy.master')
            ->name('pharmacy.suppliers.status');

        /*
        |--------------------------------------------------------------------------
        | Pharmacy - Purchase Orders
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/pharmacy/purchase-orders',
            [PharmacyPurchaseOrderController::class, 'index']
        )->name('pharmacy.purchase-orders.index');

        Route::get(
            '/pharmacy/purchase-orders/create',
            [PharmacyPurchaseOrderController::class, 'create']
        )
            ->middleware(
                'permission:pharmacy.po.create'
            )
            ->name('pharmacy.purchase-orders.create');

        Route::post(
            '/pharmacy/purchase-orders',
            [PharmacyPurchaseOrderController::class, 'store']
        )
            ->middleware(
                'permission:pharmacy.po.create'
            )
            ->name('pharmacy.purchase-orders.store');

        Route::get(
            '/pharmacy/purchase-orders/{pharmacyPurchaseOrder}',
            [PharmacyPurchaseOrderController::class, 'show']
        )
            ->whereNumber('pharmacyPurchaseOrder')
            ->name('pharmacy.purchase-orders.show');

        Route::patch(
            '/pharmacy/purchase-orders/{pharmacyPurchaseOrder}/approve',
            [PharmacyPurchaseOrderController::class, 'approve']
        )
            ->whereNumber('pharmacyPurchaseOrder')
            ->middleware(
                'permission:pharmacy.po.approve'
            )
            ->name('pharmacy.purchase-orders.approve');

        Route::patch(
            '/pharmacy/purchase-orders/{pharmacyPurchaseOrder}/cancel',
            [PharmacyPurchaseOrderController::class, 'cancel']
        )
            ->whereNumber('pharmacyPurchaseOrder')
            ->middleware('permission:pharmacy.po.create')
            ->name('pharmacy.purchase-orders.cancel');

        /*
        |--------------------------------------------------------------------------
        | Pharmacy - GRN / Goods Receipt
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/pharmacy/grns',
            [PharmacyGrnController::class, 'index']
        )->name('pharmacy.grns.index');

        Route::get(
            '/pharmacy/purchase-orders/{pharmacyPurchaseOrder}/grn',
            [PharmacyGrnController::class, 'create']
        )
            ->whereNumber('pharmacyPurchaseOrder')
            ->middleware(
                'permission:pharmacy.grn.create'
            )
            ->name('pharmacy.grns.create');

        Route::post(
            '/pharmacy/purchase-orders/{pharmacyPurchaseOrder}/grn',
            [PharmacyGrnController::class, 'store']
        )
            ->whereNumber('pharmacyPurchaseOrder')
            ->middleware(
                'permission:pharmacy.grn.create'
            )
            ->name('pharmacy.grns.store');

        Route::get(
            '/pharmacy/grns/{pharmacyGrn}',
            [PharmacyGrnController::class, 'show']
        )
            ->whereNumber('pharmacyGrn')
            ->name('pharmacy.grns.show');

        /*
        |--------------------------------------------------------------------------
        | Pharmacy - Purchase Returns
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/pharmacy/purchase-returns',
            [PharmacyPurchaseReturnController::class, 'index']
        )->name('pharmacy.purchase-returns.index');

        Route::get(
            '/pharmacy/grns/{pharmacyGrn}/purchase-return',
            [PharmacyPurchaseReturnController::class, 'create']
        )
            ->whereNumber('pharmacyGrn')
            ->middleware(
                'permission:pharmacy.purchase-return.create'
            )
            ->name('pharmacy.purchase-returns.create');

        Route::post(
            '/pharmacy/grns/{pharmacyGrn}/purchase-return',
            [PharmacyPurchaseReturnController::class, 'store']
        )
            ->whereNumber('pharmacyGrn')
            ->middleware(
                'permission:pharmacy.purchase-return.create'
            )
            ->name('pharmacy.purchase-returns.store');

        Route::get(
            '/pharmacy/purchase-returns/{pharmacyPurchaseReturn}',
            [PharmacyPurchaseReturnController::class, 'show']
        )
            ->whereNumber('pharmacyPurchaseReturn')
            ->name('pharmacy.purchase-returns.show');

        /*
        |--------------------------------------------------------------------------
        | Pharmacy - Supplier Payables
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/pharmacy/supplier-payables',
            [PharmacySupplierPayableController::class, 'index']
        )->name('pharmacy.supplier-payables.index');

        Route::post(
            '/pharmacy/grns/{pharmacyGrn}/supplier-payable',
            [PharmacySupplierPayableController::class, 'createFromGrn']
        )
            ->whereNumber('pharmacyGrn')
            ->middleware('permission:pharmacy.grn.create')
            ->name('pharmacy.supplier-payables.create-from-grn');

        Route::get(
            '/pharmacy/supplier-payables/{pharmacySupplierPayable}',
            [PharmacySupplierPayableController::class, 'show']
        )
            ->whereNumber('pharmacySupplierPayable')
            ->name('pharmacy.supplier-payables.show');

        Route::post(
            '/pharmacy/supplier-payables/{pharmacySupplierPayable}/payments',
            [PharmacySupplierPayableController::class, 'storePayment']
        )
            ->whereNumber('pharmacySupplierPayable')
            ->middleware(
                'permission:pharmacy.supplier-payment.record'
            )
            ->name('pharmacy.supplier-payables.payments.store');

        /*
        |--------------------------------------------------------------------------
        | Pharmacy - Stock Audits
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/pharmacy/stock-audits',
            [PharmacyStockAuditController::class, 'index']
        )->name('pharmacy.stock-audits.index');

        /*
         * IMPORTANT:
         * /create must appear before /{pharmacyStockAudit}
         */

        Route::get(
            '/pharmacy/stock-audits/create',
            [PharmacyStockAuditController::class, 'create']
        )
            ->middleware(
                'permission:pharmacy.stock-audit.create'
            )
            ->name('pharmacy.stock-audits.create');

        Route::post(
            '/pharmacy/stock-audits',
            [PharmacyStockAuditController::class, 'store']
        )
            ->middleware(
                'permission:pharmacy.stock-audit.create'
            )
            ->name('pharmacy.stock-audits.store');

        Route::get(
            '/pharmacy/stock-audits/{pharmacyStockAudit}',
            [PharmacyStockAuditController::class, 'show']
        )
            ->whereNumber('pharmacyStockAudit')
            ->name('pharmacy.stock-audits.show');

        Route::get(
            '/pharmacy/stock-audits/{pharmacyStockAudit}/print',
            [PharmacyStockAuditController::class, 'print']
        )
            ->whereNumber('pharmacyStockAudit')
            ->name('pharmacy.stock-audits.print');

        Route::put(
            '/pharmacy/stock-audits/{pharmacyStockAudit}/counts',
            [PharmacyStockAuditController::class, 'updateCounts']
        )
            ->whereNumber('pharmacyStockAudit')
            ->middleware(
                'permission:pharmacy.stock-audit.create'
            )
            ->name('pharmacy.stock-audits.counts.update');

        Route::patch(
            '/pharmacy/stock-audits/{pharmacyStockAudit}/submit-review',
            [PharmacyStockAuditController::class, 'submitForReview']
        )
            ->whereNumber('pharmacyStockAudit')
            ->middleware(
                'permission:pharmacy.stock-audit.create'
            )
            ->name('pharmacy.stock-audits.submit-review');

        Route::patch(
            '/pharmacy/stock-audits/{pharmacyStockAudit}/approve',
            [PharmacyStockAuditController::class, 'approve']
        )
            ->whereNumber('pharmacyStockAudit')
            ->middleware(
                'permission:pharmacy.stock-audit.approve'
            )
            ->name('pharmacy.stock-audits.approve');

        Route::patch(
            '/pharmacy/stock-audits/{pharmacyStockAudit}/post',
            [PharmacyStockAuditController::class, 'post']
        )
            ->whereNumber('pharmacyStockAudit')
            ->middleware(
                'permission:pharmacy.stock-audit.post'
            )
            ->name('pharmacy.stock-audits.post');

        Route::patch(
            '/pharmacy/stock-audits/{pharmacyStockAudit}/cancel',
            [PharmacyStockAuditController::class, 'cancel']
        )
            ->whereNumber('pharmacyStockAudit')
            ->middleware(
                'permission:pharmacy.stock-audit.create'
            )
            ->name('pharmacy.stock-audits.cancel');

        /*
|--------------------------------------------------------------------------
| Pharmacy - Internal Stock Transfers
|--------------------------------------------------------------------------
*/

        Route::get(
            '/pharmacy/stock-transfers',
            [PharmacyStockTransferController::class, 'index']
        )->name('pharmacy.stock-transfers.index');

        Route::get(
            '/pharmacy/stock-transfers/create',
            [PharmacyStockTransferController::class, 'create']
        )
            ->middleware('permission:stores.transfer')
            ->name('pharmacy.stock-transfers.create');

        Route::post(
            '/pharmacy/stock-transfers',
            [PharmacyStockTransferController::class, 'store']
        )
            ->middleware('permission:stores.transfer')
            ->name('pharmacy.stock-transfers.store');

        Route::get(
            '/pharmacy/stock-transfers/{pharmacyStockTransfer}',
            [PharmacyStockTransferController::class, 'show']
        )
            ->whereNumber('pharmacyStockTransfer')
            ->name('pharmacy.stock-transfers.show');

        Route::patch(
            '/pharmacy/stock-transfers/{pharmacyStockTransfer}/issue',
            [PharmacyStockTransferController::class, 'issue']
        )
            ->whereNumber('pharmacyStockTransfer')
            ->middleware('permission:stores.transfer')
            ->name('pharmacy.stock-transfers.issue');

        Route::patch(
            '/pharmacy/stock-transfers/{pharmacyStockTransfer}/receive',
            [PharmacyStockTransferController::class, 'receive']
        )
            ->whereNumber('pharmacyStockTransfer')
            ->middleware('permission:stores.transfer')
            ->name('pharmacy.stock-transfers.receive');

        Route::patch(
            '/pharmacy/stock-transfers/{pharmacyStockTransfer}/cancel',
            [PharmacyStockTransferController::class, 'cancel']
        )
            ->whereNumber('pharmacyStockTransfer')
            ->middleware('permission:stores.transfer')
            ->name('pharmacy.stock-transfers.cancel');
    });

    Route::post(
        '/pharmacy/dispensing/{admission}/interim-payment',
        [IpBillingController::class, 'receiveAdvance']
    )
        ->whereNumber('admission')
        ->middleware('permission:ip-billing.advance')
        ->name('pharmacy.dispensing.interim-payment.store');

    /*
|--------------------------------------------------------------------------
| Pharmacy GST Purchase Report
|--------------------------------------------------------------------------
*/

    Route::middleware(
        'permission:pharmacy.gst-report'
    )->group(function () {

        Route::get(
            '/pharmacy/reports/gst-purchases',
            [GstPurchaseReportController::class, 'index']
        )->name('pharmacy.reports.gst-purchases.index');

        Route::get(
            '/pharmacy/reports/gst-purchases/export',
            [GstPurchaseReportController::class, 'export']
        )->name('pharmacy.reports.gst-purchases.export');

    });

    /*
    |--------------------------------------------------------------------------
    | Future Doctor Portal
    |--------------------------------------------------------------------------
    |
    | Doctor-specific routes will later be added here:
    |
    | /doctor
    | /doctor/encounters/{encounter}
    | consultation notes
    | diagnoses
    | investigation ordering
    | prescriptions
    | complete consultation
    |
    */

});

/*
|--------------------------------------------------------------------------
| User Profile
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'active',
])->group(function () {

    Route::get(
        '/profile',
        [ProfileController::class, 'edit']
    )->name('profile.edit');

    Route::patch(
        '/profile',
        [ProfileController::class, 'update']
    )->name('profile.update');

    Route::delete(
        '/profile',
        [ProfileController::class, 'destroy']
    )->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Administration
|--------------------------------------------------------------------------
|
| Administrative request workflow.
| Protected by the same authenticated / verified / active middleware
| used by the main hospital ERP.
|
*/

Route::middleware([
    'auth',
    'verified',
    'active',
])
    ->prefix('administration')
    ->name('administration.')
    ->group(function () {

        Route::get(
            '/',
            [AdministrativeRequestController::class, 'index']
        )->name('dashboard');

        Route::get(
            '/requests',
            [AdministrativeRequestController::class, 'index']
        )->name('requests.index');

        Route::get(
            '/requests/create',
            [AdministrativeRequestController::class, 'create']
        )->name('requests.create');

        Route::post(
            '/requests',
            [AdministrativeRequestController::class, 'store']
        )->name('requests.store');

        Route::post(
            '/requests/{administrativeRequest}/submit',
            [AdministrativeRequestController::class, 'submit']
        )
            ->whereNumber('administrativeRequest')
            ->name('requests.submit');

        Route::get(
            '/requests/{administrativeRequest}',
            [AdministrativeRequestController::class, 'show']
        )
            ->whereNumber('administrativeRequest')
            ->name('requests.show');

        Route::post(
            '/requests/{administrativeRequest}/verify',
            [AdministrativeRequestController::class, 'verify']
        )
            ->whereNumber('administrativeRequest')
            ->name('requests.verify');

        Route::post(
            '/requests/{administrativeRequest}/ms-approve',
            [AdministrativeRequestController::class, 'msApprove']
        )
            ->whereNumber('administrativeRequest')
            ->name('requests.ms-approve');

        Route::post(
            '/requests/{administrativeRequest}/ms-reject',
            [AdministrativeRequestController::class, 'msReject']
        )
            ->whereNumber('administrativeRequest')
            ->name('requests.ms-reject');

        Route::post(
            '/requests/{administrativeRequest}/ms-return',
            [AdministrativeRequestController::class, 'msReturn']
        )
            ->whereNumber('administrativeRequest')
            ->name('requests.ms-return');

        Route::post(
            '/requests/{administrativeRequest}/start-execution',
            [AdministrativeRequestController::class, 'startExecution']
        )
            ->whereNumber('administrativeRequest')
            ->name('requests.start-execution');

        Route::post(
            '/requests/{administrativeRequest}/mark-executed',
            [AdministrativeRequestController::class, 'markExecuted']
        )
            ->whereNumber('administrativeRequest')
            ->name('requests.mark-executed');

        Route::post(
            '/requests/{administrativeRequest}/close',
            [AdministrativeRequestController::class, 'closeRequest']
        )
            ->whereNumber('administrativeRequest')
            ->name('requests.close');

        Route::post(
            '/requests/{administrativeRequest}/start-execution',
            [AdministrativeRequestController::class, 'startExecution']
        )
            ->whereNumber('administrativeRequest')
            ->name('requests.start-execution');

        Route::post(
            '/requests/{administrativeRequest}/mark-executed',
            [AdministrativeRequestController::class, 'markExecuted']
        )
            ->whereNumber('administrativeRequest')
            ->name('requests.mark-executed');

        Route::post(
            '/requests/{administrativeRequest}/close',
            [AdministrativeRequestController::class, 'closeRequest']
        )
            ->whereNumber('administrativeRequest')
            ->name('requests.close');

        Route::post(
            '/requests/{administrativeRequest}/documents',
            [AdministrativeRequestController::class, 'uploadDocument']
        )
            ->whereNumber('administrativeRequest')
            ->name('requests.documents.store');

        Route::get(
            '/requests/{administrativeRequest}/documents/{document}/view',
            [AdministrativeRequestController::class, 'viewDocument']
        )
            ->whereNumber('administrativeRequest')
            ->whereNumber('document')
            ->name('requests.documents.view');
        Route::post(
            '/requests/{administrativeRequest}/higher-approval',
            [AdministrativeRequestController::class, 'recordHigherApproval']
        )
            ->whereNumber('administrativeRequest')
            ->name('requests.higher-approval');

        Route::get(
            '/my-work',
            [AdministrativeRequestController::class, 'myWork']
        )
            ->name('my-work.index');
    });

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
