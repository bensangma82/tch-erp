@php
    $user = Auth::user();

    $isAdmin = $user->isAdmin();

    /*
    |--------------------------------------------------------------------------
    | Functional role flags
    |--------------------------------------------------------------------------
    */
    $canReception = $isAdmin || $user->hasRole('reception');
    $canNursing = $isAdmin || $user->hasRole('nursing');
    $canBilling = $isAdmin || $user->hasRole('billing');
    $canFinance = $isAdmin || $user->hasRole('finance');
    $canLaboratory = $isAdmin || $user->hasRole('laboratory');
    $canRadiology = $isAdmin || $user->hasRole('radiology');
    $canPharmacy = $isAdmin || $user->hasRole('pharmacy');
    $canDoctor = $isAdmin || $user->hasRole('doctor');

    $canStores = $isAdmin || $user->hasRole('stores');
    $canHr = $isAdmin || $user->hasRole('hr');
    $canMedicalRecords = $isAdmin || $user->hasRole('medical_records');
    $canEmergency = $isAdmin || $user->hasRole('emergency');
    $canIpd = $isAdmin || $user->hasRole('ipd');
    $canManagement = $isAdmin || $user->hasRole('management');


    /*
    |--------------------------------------------------------------------------
    | Hybrid role + permission visibility
    |--------------------------------------------------------------------------
    |
    | Roles keep broad module access.
    | Permissions refine what appears inside those modules.
    |
    | If a role has not yet been configured in role_permission, the existing
    | role-based sidebar remains unchanged so current users do not suddenly
    | lose access while permissions are being rolled out.
    |
    */
    $rolePermissionsConfigured =
        $isAdmin
        || \Illuminate\Support\Facades\DB::table('role_permission')
            ->where('role', $user->role)
            ->exists();

    $canUsePermission = function (string $permission) use (
        $user,
        $isAdmin,
        $rolePermissionsConfigured
    ): bool {
        return $isAdmin
            || ! $rolePermissionsConfigured
            || $user->hasPermission($permission);
    };

    $canPatientsView = $canUsePermission('patients.view');
    $canOpdView = $canUsePermission('opd.view');
    $canNursingView = $canUsePermission('nursing.view');
    $canEmergencyView = $canUsePermission('emergency.view');
    $canIpdView = $canUsePermission('ipd.view');

    $canLaboratoryView = $canUsePermission('laboratory.view');
    $canRadiologyView = $canUsePermission('radiology.view');

    $canPharmacyView = $canUsePermission('pharmacy.view');

    $canBillingView = $canUsePermission('billing.view');
    $canIpBillingView = $canUsePermission('ip-billing.view');

    $canFinanceDashboard = $canUsePermission('finance.dashboard');
    $canFinanceVouchers = $canUsePermission('finance.vouchers.view');
    $canFinanceReports = $canUsePermission('finance.reports');
    $canFinanceMaster = $canUsePermission('finance.master');

    $canAdministrationView = $canUsePermission('administration.view');
    $canSystemServices = $canUsePermission('system.services');
    $canSystemUsers = $canUsePermission('system.users');
    $canHrDepartments = $canUsePermission('hr.departments');
    $canHrEmployees = $canUsePermission('hr.employees');
    $canLabParameters = $canUsePermission('laboratory.parameters');
    $canInpatientMaster = $canUsePermission('system.inpatient-master');

    /*
    |--------------------------------------------------------------------------
    | Sidebar section visibility
    |--------------------------------------------------------------------------
    |
    | Keep these aligned with routes/web.php.
    |
    */
    $canClinical =
        $canReception
        || $canNursing
        || $canDoctor
        || $canMedicalRecords
        || $canEmergency
        || $canIpd
        || $canManagement;

    $canDiagnostics =
        $canLaboratory
        || $canRadiology;

    $canOperations =
        $canPharmacy
        || $canStores;

    $canBusiness =
        $canBilling
        || $canFinance;

    $canAdministration =
        $isAdmin
        || $canHr;

    /*
    |--------------------------------------------------------------------------
    | Open section state
    |--------------------------------------------------------------------------
    */
    $clinicalOpen =
        request()->routeIs('patients.*')
        || request()->routeIs('opd.*')
        || request()->routeIs('nursing.*')
        || request()->routeIs('emergency.*')
        || request()->routeIs('ipd.*');

    $diagnosticsOpen =
        request()->routeIs('laboratory.*')
        || request()->routeIs('imaging.*')
        || request()->routeIs('diagnostics.items.*');

    $pharmacyOpen =
        request()->routeIs('pharmacy.*');

    $operationsOpen =
        $pharmacyOpen;

    $financeOpen =
        request()->routeIs('finance.*');

    $businessOpen =
        request()->routeIs('billing.*')
        || request()->routeIs('ip-billing.*')
        || $financeOpen;

    $masterDataOpen =
        request()->routeIs('admin.departments.*')
        || request()->routeIs('admin.employees.*')
        || request()->routeIs('admin.laboratory-parameters.*')
        || request()->routeIs('inpatient-master.*');

    $administrationOpen =
        request()->routeIs('administration.*')
        || request()->routeIs('services.*')
        || request()->routeIs('admin.users.*')
        || $masterDataOpen;
@endphp

<aside
    class="min-h-screen w-64 border-r border-slate-800 bg-slate-950 text-slate-200"
    x-data="{
        clinical: {{ $clinicalOpen ? 'true' : 'false' }},
        diagnostics: {{ $diagnosticsOpen ? 'true' : 'false' }},
        operations: {{ $operationsOpen ? 'true' : 'false' }},
        pharmacy: {{ $pharmacyOpen ? 'true' : 'false' }},
        business: {{ $businessOpen ? 'true' : 'false' }},
        finance: {{ $financeOpen ? 'true' : 'false' }},
        administration: {{ $administrationOpen ? 'true' : 'false' }},
        masterData: {{ $masterDataOpen ? 'true' : 'false' }}
    }"
>
    <div class="border-b border-slate-800 px-4 py-4">
        <div class="text-xs uppercase tracking-wider text-slate-500">
            Main Navigation
        </div>

        <div class="mt-2 text-sm font-semibold text-white">
            {{ $user->name }}
        </div>

        <div class="mt-1 text-xs text-slate-400">
            {{ ucfirst($user->role ?? 'staff') }}
        </div>
    </div>

    <nav class="space-y-1 px-3 py-4 text-sm">

        {{-- DASHBOARD --}}
        <a
            href="{{ route('dashboard') }}"
            class="block rounded-md px-3 py-2
                {{ request()->routeIs('dashboard')
                    ? 'bg-slate-800 text-white'
                    : 'hover:bg-slate-800' }}"
        >
            Dashboard
        </a>


        {{-- CLINICAL --}}
        @if ($canClinical)
            <div class="pt-3">
                <button
                    type="button"
                    @click="clinical = !clinical"
                    class="flex w-full items-center justify-between rounded-md px-3 py-2 font-semibold
                        {{ $clinicalOpen
                            ? 'bg-slate-900 text-white'
                            : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                >
                    <span>Clinical</span>

                    <span
                        class="text-xs transition-transform duration-200"
                        :class="{ 'rotate-90': clinical }"
                    >
                        ›
                    </span>
                </button>

                <div
                    x-show="clinical"
                    x-collapse
                    class="ml-3 mt-1 space-y-1 border-l border-slate-800 pl-3"
                >
                    @if (($canReception || $canNursing || $canDoctor || $canMedicalRecords) && $canPatientsView)
                        <a
                            href="{{ route('patients.index') }}"
                            class="block rounded-md px-3 py-2 text-sm
                                {{ request()->routeIs('patients.*')
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                        >
                            Patients
                        </a>

                        @if ($canOpdView)
                        <a
                            href="{{ route('opd.index') }}"
                            class="block rounded-md px-3 py-2 text-sm
                                {{ request()->routeIs('opd.*')
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                        >
                            OPD
                        </a>
                        @endif
                    @endif

                    @if ($canManagement && ! $canReception && ! $canNursing && ! $canDoctor && ! $canMedicalRecords && $canOpdView)
                        <a
                            href="{{ route('opd.index') }}"
                            class="block rounded-md px-3 py-2 text-sm
                                {{ request()->routeIs('opd.*')
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                        >
                            OPD
                        </a>
                    @endif

                    @if ($canNursing && $canNursingView)
                        <a
                            href="{{ route('nursing.index') }}"
                            class="block rounded-md px-3 py-2 text-sm
                                {{ request()->routeIs('nursing.*')
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                        >
                            Nursing Station
                        </a>
                    @endif

                    @if (($canReception || $canNursing || $canDoctor || $canEmergency) && $canEmergencyView)
                        <a
                            href="{{ route('emergency.index') }}"
                            class="block rounded-md px-3 py-2 text-sm
                                {{ request()->routeIs('emergency.*')
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                        >
                            Emergency
                        </a>
                    @endif

                    @if (($canReception || $canNursing || $canDoctor || $canIpd) && $canIpdView)
                        <a
                            href="{{ route('ipd.index') }}"
                            class="block rounded-md px-3 py-2 text-sm
                                {{ request()->routeIs('ipd.*')
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                        >
                            IPD
                        </a>
                    @endif
                </div>
            </div>
        @endif


        {{-- DIAGNOSTICS --}}
        @if ($canDiagnostics)
            <div class="pt-2">
                <button
                    type="button"
                    @click="diagnostics = !diagnostics"
                    class="flex w-full items-center justify-between rounded-md px-3 py-2 font-semibold
                        {{ $diagnosticsOpen
                            ? 'bg-slate-900 text-white'
                            : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                >
                    <span>Diagnostics</span>

                    <span
                        class="text-xs transition-transform duration-200"
                        :class="{ 'rotate-90': diagnostics }"
                    >
                        ›
                    </span>
                </button>

                <div
                    x-show="diagnostics"
                    x-collapse
                    class="ml-3 mt-1 space-y-1 border-l border-slate-800 pl-3"
                >
                    @if ($canLaboratory && $canLaboratoryView)
                        <a
                            href="{{ route('laboratory.index') }}"
                            class="block rounded-md px-3 py-2 text-sm
                                {{
                                    request()->routeIs('laboratory.*')
                                    || request()->routeIs('diagnostics.items.result.*')
                                    || request()->routeIs('diagnostics.items.sample.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                                }}"
                        >
                            Laboratory
                        </a>
                    @endif

                    @if ($canRadiology && $canRadiologyView)
                        <a
                            href="{{ route('imaging.index') }}"
                            class="block rounded-md px-3 py-2 text-sm
                                {{
                                    request()->routeIs('imaging.*')
                                    || request()->routeIs('diagnostics.items.imaging-report.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                                }}"
                        >
                            Imaging / Radiology
                        </a>
                    @endif
                </div>
            </div>
        @endif


        {{-- OPERATIONS --}}
        @if ($canOperations)
            <div class="pt-2">
                <button
                    type="button"
                    @click="operations = !operations"
                    class="flex w-full items-center justify-between rounded-md px-3 py-2 font-semibold
                        {{ $operationsOpen
                            ? 'bg-slate-900 text-white'
                            : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                >
                    <span>Operations</span>

                    <span
                        class="text-xs transition-transform duration-200"
                        :class="{ 'rotate-90': operations }"
                    >
                        ›
                    </span>
                </button>

                <div
                    x-show="operations"
                    x-collapse
                    class="ml-3 mt-1 space-y-1 border-l border-slate-800 pl-3"
                >
                    @if ($canPharmacy && $canPharmacyView)
                        <button
                            type="button"
                            @click="pharmacy = !pharmacy"
                            class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium
                                {{ $pharmacyOpen
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                        >
                            <span>Pharmacy</span>

                            <span
                                class="text-xs transition-transform duration-200"
                                :class="{ 'rotate-90': pharmacy }"
                            >
                                ›
                            </span>
                        </button>

                        <div
                            x-show="pharmacy"
                            x-collapse
                            class="ml-3 mt-1 space-y-1 border-l border-slate-800 pl-3"
                        >
                            <a
                                href="{{ route('pharmacy.dashboard') }}"
                                class="block rounded-md px-3 py-2 text-sm
                                    {{ request()->routeIs('pharmacy.dashboard')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                            >
                                Dashboard
                            </a>

                            <a
                                href="{{ route('pharmacy.dispensing.index') }}"
                                class="block rounded-md px-3 py-2 text-sm
                                    {{
                                        request()->routeIs('pharmacy.dispensing.*')
                                        || request()->routeIs('pharmacy.returns.*')
                                            ? 'bg-slate-800 text-white'
                                            : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                                    }}"
                            >
                                Dispensing
                            </a>

                            <a
                                href="{{ route('pharmacy.medicines.index') }}"
                                class="block rounded-md px-3 py-2 text-sm
                                    {{ request()->routeIs('pharmacy.medicines.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                            >
                                Medicine Master
                            </a>

                            <a
                                href="{{ route('pharmacy.stock-batches.index') }}"
                                class="block rounded-md px-3 py-2 text-sm
                                    {{ request()->routeIs('pharmacy.stock-batches.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                            >
                                Pharmacy Stock
                            </a>

                            <a
                                href="{{ route('pharmacy.suppliers.index') }}"
                                class="block rounded-md px-3 py-2 text-sm
                                    {{ request()->routeIs('pharmacy.suppliers.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                            >
                                Supplier Master
                            </a>

                            <a
                                href="{{ route('pharmacy.purchase-orders.index') }}"
                                class="block rounded-md px-3 py-2 text-sm
                                    {{ request()->routeIs('pharmacy.purchase-orders.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                            >
                                Purchase Orders
                            </a>

                            <a
                                href="{{ route('pharmacy.grns.index') }}"
                                class="block rounded-md px-3 py-2 text-sm
                                    {{ request()->routeIs('pharmacy.grns.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                            >
                                GRN Register
                            </a>

                            <a
                                href="{{ route('pharmacy.purchase-returns.index') }}"
                                class="block rounded-md px-3 py-2 text-sm
                                    {{ request()->routeIs('pharmacy.purchase-returns.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                            >
                                Purchase Returns
                            </a>

                            <a
                                href="{{ route('pharmacy.supplier-payables.index') }}"
                                class="block rounded-md px-3 py-2 text-sm
                                    {{ request()->routeIs('pharmacy.supplier-payables.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                            >
                                Supplier Payables
                            </a>

                            <a
                                href="{{ route('pharmacy.stock-audits.index') }}"
                                class="block rounded-md px-3 py-2 text-sm
                                    {{ request()->routeIs('pharmacy.stock-audits.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                            >
                                Stock Audit
                            </a>

                            <a
                                href="{{ route('pharmacy.disposals.index') }}"
                                class="block rounded-md px-3 py-2 text-sm
                                    {{ request()->routeIs('pharmacy.disposals.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                            >
                                Disposal Register
                            </a>

                            <a
                                href="{{ route('pharmacy.stock-transfers.index') }}"
                                class="block rounded-md px-3 py-2 text-sm
                                    {{ request()->routeIs('pharmacy.stock-transfers.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                            >
                                Stock Transfers
                            </a>
                        </div>
                    @endif

                    @if ($canStores && ! $canPharmacy)
                        <div class="rounded-md px-3 py-2 text-sm text-slate-500">
                            Stores / Inventory
                            <span class="float-right text-[10px] uppercase">Setup pending</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif


        {{-- BUSINESS --}}
        @if ($canBusiness)
            <div class="pt-2">
                <button
                    type="button"
                    @click="business = !business"
                    class="flex w-full items-center justify-between rounded-md px-3 py-2 font-semibold
                        {{ $businessOpen
                            ? 'bg-slate-900 text-white'
                            : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                >
                    <span>Business</span>

                    <span
                        class="text-xs transition-transform duration-200"
                        :class="{ 'rotate-90': business }"
                    >
                        ›
                    </span>
                </button>

                <div
                    x-show="business"
                    x-collapse
                    class="ml-3 mt-1 space-y-1 border-l border-slate-800 pl-3"
                >
                    @if ($canBilling)
                        @if ($canBillingView)
                        <a
                            href="{{ route('billing.index') }}"
                            class="block rounded-md px-3 py-2 text-sm
                                {{ request()->routeIs('billing.*')
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                        >
                            Billing Counter
                        </a>
                        @endif

                        @if ($canIpBillingView)
                        <a
                            href="{{ route('ip-billing.index') }}"
                            class="block rounded-md px-3 py-2 text-sm
                                {{ request()->routeIs('ip-billing.*')
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                        >
                            IP Billing
                        </a>
                        @endif
                    @endif


                    {{-- FINANCE --}}
                    @if ($canFinance)

                        <div class="my-2 border-t border-slate-800"></div>

                        <button
                            type="button"
                            @click="finance = !finance"
                            class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium
                                {{ $financeOpen
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                        >
                            <span>Finance</span>

                            <span
                                class="text-xs transition-transform duration-200"
                                :class="{ 'rotate-90': finance }"
                            >
                                ›
                            </span>
                        </button>

                        <div
                            x-show="finance"
                            x-collapse
                            class="ml-3 mt-1 space-y-1 border-l border-slate-800 pl-3"
                        >
                            @if ($canFinanceDashboard)
                            <a
                                href="{{ route('finance.dashboard') }}"
                                class="block rounded-md px-3 py-2 text-sm
                                    {{ request()->routeIs('finance.dashboard')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                            >
                                Dashboard
                            </a>
                            @endif

                            @if ($canFinanceVouchers)
                            <a
                                href="{{ route('finance.vouchers.index') }}"
                                class="block rounded-md px-3 py-2 text-sm
                                    {{ request()->routeIs('finance.vouchers.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                            >
                                Vouchers
                            </a>
                            @endif


                                                        @if ($canFinanceReports)
                            <a
                                href="{{ route('finance.reports.index') }}"
                                class="block rounded-md px-3 py-2 text-sm
                                    {{ request()->routeIs('finance.reports.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                            >
                                Reports
                            </a>
                            @endif

                            @if ($canFinanceMaster)
                            <a
                                href="{{ route('finance.master.index') }}"
                                class="block rounded-md px-3 py-2 text-sm
                                    {{ request()->routeIs('finance.master.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                            >
                                Finance Master
                            </a>
                            @endif
                        </div>

                    @endif
                </div>
            </div>
        @endif


        {{-- ADMINISTRATION --}}
        @if ($canAdministration)
            <div class="pt-2">
                <button
                    type="button"
                    @click="administration = !administration"
                    class="flex w-full items-center justify-between rounded-md px-3 py-2 font-semibold
                        {{ $administrationOpen
                            ? 'bg-slate-900 text-white'
                            : 'text-slate-300 hover:bg-slate-900 hover:text-white' }}"
                >
                    <span>Administration</span>

                    <span
                        class="text-xs transition-transform duration-200"
                        :class="{ 'rotate-90': administration }"
                    >
                        ›
                    </span>
                </button>

                <div
                    x-show="administration"
                    x-collapse
                    class="ml-3 mt-1 space-y-1 border-l border-slate-800 pl-3"
                >
                    @if ($isAdmin)
                    {{-- ADMINISTRATION WORKFLOW --}}
                    <a
                        href="{{ route('administration.dashboard') }}"
                        class="block rounded-md px-3 py-2 text-sm
                            {{ request()->routeIs('administration.dashboard')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                    >
                        Administration Dashboard
                    </a>

                    <a
                        href="{{ route('administration.requests.index') }}"
                        class="block rounded-md px-3 py-2 text-sm
                            {{ request()->routeIs('administration.requests.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                    >
                        Requests
                    </a>

                    <a
                        href="{{ route('administration.my-work.index') }}"
                        class="block rounded-md px-3 py-2 text-sm
                            {{ request()->routeIs('administration.my-work.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                    >
                        My Work
                    </a>

                    <div class="my-2 border-t border-slate-800"></div>

                    {{-- SYSTEM / MASTER CONFIGURATION --}}
                    <a
                        href="{{ route('services.index') }}"
                        class="block rounded-md px-3 py-2 text-sm
                            {{ request()->routeIs('services.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                    >
                        Service Master
                    </a>

                    <a
                        href="{{ route('admin.users.index') }}"
                        class="block rounded-md px-3 py-2 text-sm
                            {{ request()->routeIs('admin.users.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                    >
                        User Management
                    </a>

                    @endif
<a
    href="{{ route('admin.role-permissions.index') }}"
    class="block rounded-md px-3 py-2 text-sm
        {{ request()->routeIs('admin.role-permissions.*')
            ? 'bg-slate-800 text-white'
            : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
>
    Role & Permissions
</a>
                    <button
                        type="button"
                        @click="masterData = !masterData"
                        class="flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium
                            {{ $masterDataOpen
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <span>Master Data</span>

                        <span
                            class="text-xs transition-transform duration-200"
                            :class="{ 'rotate-90': masterData }"
                        >
                            ›
                        </span>
                    </button>

                    <div
                        x-show="masterData"
                        x-collapse
                        class="ml-3 mt-1 space-y-1 border-l border-slate-800 pl-3"
                    >
                        <a
                            href="{{ route('admin.departments.index') }}"
                            class="block rounded-md px-3 py-2 text-sm
                                {{ request()->routeIs('admin.departments.*')
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                        >
                            Departments
                        </a>

                        <a
                            href="{{ route('admin.employees.index') }}"
                            class="block rounded-md px-3 py-2 text-sm
                                {{ request()->routeIs('admin.employees.*')
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                        >
                            Staff / Employees
                        </a>

                        @if ($isAdmin)
<a
    href="{{ route('services.index', ['scope' => 'charges']) }}"
    class="block rounded-lg px-3 py-2.5 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white"
>
    Charge Master
</a>


                        <a
                            href="{{ route('admin.laboratory-parameters.index') }}"
                            class="block rounded-md px-3 py-2 text-sm
                                {{ request()->routeIs('admin.laboratory-parameters.*')
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                        >
                            Laboratory Parameter Master
                        </a>

                        <a
                            href="{{ route('inpatient-master.index') }}"
                            class="block rounded-md px-3 py-2 text-sm
                                {{ request()->routeIs('inpatient-master.*')
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                        >
                            Inpatient Setup
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        @endif

    </nav>
</aside>