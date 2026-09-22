<?php

use App\Http\Controllers\DiagnosticSampleController;
use App\Http\Controllers\Pharmacy\PharmacyStockTransferController;
use App\Http\Controllers\Pharmacy\PharmacyStockAuditController;
use App\Http\Controllers\Pharmacy\PharmacySupplierPayableController;
use App\Http\Controllers\Pharmacy\PharmacyPurchaseReturnController;
use App\Http\Controllers\Pharmacy\PharmacyGrnController;
use App\Http\Controllers\Pharmacy\PharmacyPurchaseOrderController;
use App\Http\Controllers\Pharmacy\PharmacySupplierController;

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Finance\FinanceDashboardController;
use App\Http\Controllers\Finance\FinanceMasterController;
use App\Http\Controllers\Finance\FinanceVoucherController;
use App\Http\Controllers\Finance\FinanceReportController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Administration\AdministrativeRequestController;
use App\Http\Controllers\InpatientMasterController;
use App\Http\Controllers\BedTariffController;
use App\Http\Controllers\IpBillingController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DischargeSummaryController;
use App\Http\Controllers\BedTransferController;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\LaboratoryTestParameterController;
use App\Http\Controllers\DiagnosticWorklistController;
use App\Http\Controllers\AdmissionClosureController;
use App\Http\Controllers\EmergencyAdmissionController;
use App\Http\Controllers\EmergencyTriageController;
use App\Http\Controllers\EmergencyVisitController;
use App\Http\Controllers\NursingController;
use App\Http\Controllers\OpdController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;

use App\Http\Controllers\Pharmacy\DispensingController;
use App\Http\Controllers\Pharmacy\MedicineController;
use App\Http\Controllers\Pharmacy\PharmacyDashboardController;
use App\Http\Controllers\Pharmacy\PharmacyDisposalController;
use App\Http\Controllers\Pharmacy\PharmacyReturnController;
use App\Http\Controllers\Pharmacy\StockAdjustmentController;
use App\Http\Controllers\Pharmacy\StockBatchController;
use App\Http\Controllers\Pharmacy\StockMovementController;

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
    | System Administration - User Management
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:admin'
    )->group(function () {

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

            Route::middleware('role:admin')->group(function () {

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
| Admin - Department Master
|--------------------------------------------------------------------------
*/

Route::middleware(
    'role:admin,hr'
)->prefix('admin')->name('admin.')->group(function () {

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


    /*
    |--------------------------------------------------------------------------
    | Admin - Employee / Staff Master
    |--------------------------------------------------------------------------
    */

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
});
       
       
       
            /*
        |--------------------------------------------------------------------------
        | Bed Tariff Master
        |--------------------------------------------------------------------------
        */

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
        'role:reception,nursing,medical_records,emergency'
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
        'role:reception,nursing,billing,laboratory,radiology,doctor,medical_records,emergency,ipd'
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
    | Patient Card
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:reception,medical_records'
    )->group(function () {

        Route::get(
            '/patients/{patient}/card',
            [PatientController::class, 'card']
        )
            ->whereNumber('patient')
            ->name('patients.card');
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
        'role:reception,nursing,emergency'
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
        'role:reception,nursing,doctor,emergency,ipd'
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

    });





        /*
    |--------------------------------------------------------------------------
    | Emergency - Nursing Triage
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:nursing,emergency'
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
    | Emergency - Admission
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:reception,doctor,emergency'
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
    | IPD - Inpatient Census / Admission View
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:reception,nursing,doctor,admin,ipd'
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


        /*
        |--------------------------------------------------------------------------
        | IPD - Bed Transfer
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | IPD - Admission Closure
        |--------------------------------------------------------------------------
        */

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
        'role:doctor,admin'
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

    });




    /*
    |--------------------------------------------------------------------------
    | OPD Queue / View
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:reception,nursing,billing,doctor,medical_records,management'
    )->group(function () {

        Route::get(
            '/opd',
            [OpdController::class, 'index']
        )->name('opd.index');


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
        'role:reception,medical_records'
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
        'role:reception,billing,finance'
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
        'role:nursing'
    )->group(function () {

        Route::get(
            '/nursing',
            [NursingController::class, 'index']
        )->name('nursing.index');


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
        'role:admin'
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

    Route::middleware(
        'role:billing'
    )->group(function () {

        Route::get(
            '/billing',
            [BillingController::class, 'index']
        )->name('billing.index');


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


        Route::get(
            '/billing/orders/{serviceOrder}/payment',
            [BillingController::class, 'payment']
        )
            ->whereNumber('serviceOrder')
            ->name('billing.payment');


        Route::post(
            '/billing/orders/{serviceOrder}/payment',
            [BillingController::class, 'processPayment']
        )
            ->whereNumber('serviceOrder')
            ->name('billing.payment.store');


        Route::get(
            '/billing/payments/{payment}/receipt',
            [BillingController::class, 'receipt']
        )
            ->whereNumber('payment')
            ->name('billing.receipt');

    });


    /*
    |--------------------------------------------------------------------------
    | Inpatient Billing
    |--------------------------------------------------------------------------
    |
    | Available to administrators and billing users.
    |
    */

    Route::middleware(
        'role:admin,billing'
    )->group(function () {

        Route::get(
            '/ip-billing',
            [IpBillingController::class, 'index']
        )->name('ip-billing.index');


        Route::get(
            '/ip-billing/advances/{ipBillingAdvance}/receipt',
            [IpBillingController::class, 'advanceReceipt']
        )
            ->whereNumber('ipBillingAdvance')
            ->name('ip-billing.advance.receipt');


        Route::get(
            '/ip-billing/{admission}/final-bill',
            [IpBillingController::class, 'finalBill']
        )
            ->whereNumber('admission')
            ->name('ip-billing.final-bill');


        Route::get(
            '/ip-billing/{admission}',
            [IpBillingController::class, 'show']
        )
            ->whereNumber('admission')
            ->name('ip-billing.show');


        Route::post(
            '/ip-billing/{admission}/generate-bed-charges',
            [IpBillingController::class, 'generateBedCharges']
        )
            ->whereNumber('admission')
            ->name('ip-billing.generate-bed-charges');


        Route::post(
            '/ip-billing/{admission}/advance',
            [IpBillingController::class, 'receiveAdvance']
        )
            ->whereNumber('admission')
            ->name('ip-billing.advance.store');

Route::post(
    '/ip-billing/{admission}/payment',
    [IpBillingController::class, 'receivePayment']
)
    ->whereNumber('admission')
    ->name('ip-billing.payment.store');
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
            '/ip-billing/{admission}/mhis/receipts',
            [IpBillingController::class, 'storeMhisReceipt']
        )
            ->whereNumber('admission')
            ->name('ip-billing.mhis.receipts.store');


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


        Route::post(
            '/ip-billing/{admission}/finalize',
            [IpBillingController::class, 'finalizeBill']
        )
            ->whereNumber('admission')
            ->name('ip-billing.finalize');

    });



    /*
    |--------------------------------------------------------------------------
    | Diagnostic Item Processing
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:laboratory,radiology'
    )->group(function () {

        Route::patch(
            '/diagnostics/items/{serviceOrderItem}/status',
            [DiagnosticWorklistController::class, 'updateStatus']
        )
            ->whereNumber('serviceOrderItem')
            ->name('diagnostics.items.status');
    });



    /*
    |--------------------------------------------------------------------------
    | Laboratory
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:laboratory'
    )->group(function () {

        Route::get(
            '/laboratory',
            [DiagnosticWorklistController::class, 'laboratory']
        )->name('laboratory.index');


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


        Route::get(
            '/diagnostics/items/{serviceOrderItem}/result/view',
            [DiagnosticWorklistController::class, 'showResult']
        )
            ->whereNumber('serviceOrderItem')
            ->name('diagnostics.items.result.show');

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

    Route::middleware(
        'role:radiology'
    )->group(function () {

        Route::get(
            '/imaging',
            [DiagnosticWorklistController::class, 'imaging']
        )->name('imaging.index');


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


        Route::get(
            '/diagnostics/items/{serviceOrderItem}/imaging-report/view',
            [DiagnosticWorklistController::class, 'showImagingReport']
        )
            ->whereNumber('serviceOrderItem')
            ->name('diagnostics.items.imaging-report.show');
    });



    /*
    |--------------------------------------------------------------------------
    | Pharmacy
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'role:pharmacy'
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
        )->name('pharmacy.dispensing.create');


        Route::post(
            '/pharmacy/dispensing',
            [DispensingController::class, 'store']
        )->name('pharmacy.dispensing.store');


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
            ->name('pharmacy.returns.create');


        Route::post(
            '/pharmacy/dispensing/{sale}/return',
            [PharmacyReturnController::class, 'store']
        )
            ->whereNumber('sale')
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
        )->name('pharmacy.medicines.create');


        Route::post(
            '/pharmacy/medicines',
            [MedicineController::class, 'store']
        )->name('pharmacy.medicines.store');


        Route::get(
            '/pharmacy/medicines/{medicine}/edit',
            [MedicineController::class, 'edit']
        )
            ->whereNumber('medicine')
            ->name('pharmacy.medicines.edit');


        Route::put(
            '/pharmacy/medicines/{medicine}',
            [MedicineController::class, 'update']
        )
            ->whereNumber('medicine')
            ->name('pharmacy.medicines.update');


        Route::patch(
            '/pharmacy/medicines/{medicine}/status',
            [MedicineController::class, 'toggleStatus']
        )
            ->whereNumber('medicine')
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
        )->name('pharmacy.stock-batches.create');


        Route::post(
            '/pharmacy/stock-batches',
            [StockBatchController::class, 'store']
        )->name('pharmacy.stock-batches.store');


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
            ->name('pharmacy.stock-batches.edit');


        Route::put(
            '/pharmacy/stock-batches/{stockBatch}',
            [StockBatchController::class, 'update']
        )
            ->whereNumber('stockBatch')
            ->name('pharmacy.stock-batches.update');


        Route::patch(
            '/pharmacy/stock-batches/{stockBatch}/status',
            [StockBatchController::class, 'toggleStatus']
        )
            ->whereNumber('stockBatch')
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
        )->name('pharmacy.suppliers.create');


        Route::post(
            '/pharmacy/suppliers',
            [PharmacySupplierController::class, 'store']
        )->name('pharmacy.suppliers.store');


        Route::get(
            '/pharmacy/suppliers/{pharmacySupplier}/edit',
            [PharmacySupplierController::class, 'edit']
        )
            ->whereNumber('pharmacySupplier')
            ->name('pharmacy.suppliers.edit');


        Route::put(
            '/pharmacy/suppliers/{pharmacySupplier}',
            [PharmacySupplierController::class, 'update']
        )
            ->whereNumber('pharmacySupplier')
            ->name('pharmacy.suppliers.update');


        Route::patch(
            '/pharmacy/suppliers/{pharmacySupplier}/status',
            [PharmacySupplierController::class, 'toggleStatus']
        )
            ->whereNumber('pharmacySupplier')
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
)->name('pharmacy.stock-transfers.create');


Route::post(
    '/pharmacy/stock-transfers',
    [PharmacyStockTransferController::class, 'store']
)->name('pharmacy.stock-transfers.store');


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
    ->name('pharmacy.stock-transfers.issue');


Route::patch(
    '/pharmacy/stock-transfers/{pharmacyStockTransfer}/receive',
    [PharmacyStockTransferController::class, 'receive']
)
    ->whereNumber('pharmacyStockTransfer')
    ->name('pharmacy.stock-transfers.receive');


Route::patch(
    '/pharmacy/stock-transfers/{pharmacyStockTransfer}/cancel',
    [PharmacyStockTransferController::class, 'cancel']
)
    ->whereNumber('pharmacyStockTransfer')
    ->name('pharmacy.stock-transfers.cancel');   
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

require __DIR__ . '/auth.php';
