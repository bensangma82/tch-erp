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
    | Section visibility
    |--------------------------------------------------------------------------
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
        || request()->routeIs('inpatient-master.*')
        || (
            request()->routeIs('services.*')
            && request('scope') === 'charges'
        );

    $administrationOpen =
        request()->routeIs('administration.*')
        || request()->routeIs('services.*')
        || request()->routeIs('admin.users.*')
        || request()->routeIs('admin.role-permissions.*')
        || $masterDataOpen;

    $roleLabel = match ($user->role ?? 'staff') {
        'admin' => 'Administrator',
        'reception' => 'Reception',
        'nursing' => 'Nursing',
        'doctor' => 'Doctor',
        'billing' => 'Billing',
        'finance' => 'Finance / Accounts',
        'laboratory' => 'Laboratory',
        'radiology' => 'Radiology',
        'pharmacy' => 'Pharmacy',
        'stores' => 'Stores / Inventory',
        'hr' => 'HR',
        'medical_records' => 'Medical Records',
        'emergency' => 'Emergency',
        'ipd' => 'IPD / Ward',
        'management' => 'Management',
        default => ucfirst(str_replace('_', ' ', $user->role ?? 'staff')),
    };
@endphp


<aside
    class="flex min-h-screen w-64 shrink-0 flex-col border-r border-slate-800 bg-slate-950 text-slate-200"
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

    {{-- ========================================================= --}}
    {{-- USER PANEL --}}
    {{-- ========================================================= --}}

    <div class="border-b border-slate-800 px-4 py-3">

        <div class="mb-2 flex items-center justify-between">

            <div class="text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                Main Navigation
            </div>

            <span class="inline-flex h-1.5 w-1.5 rounded-full bg-emerald-400"></span>

        </div>


        <div class="flex items-center gap-3 px-1 py-1.5">

            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-slate-800 text-xs font-semibold text-slate-100">
                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
            </div>


            <div class="min-w-0 flex-1">

                <div class="truncate text-sm font-semibold text-white">
                    {{ $user->name }}
                </div>

                <div class="mt-0.5 truncate text-[11px] font-medium text-slate-400">
                    {{ $roleLabel }}
                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- NAVIGATION --}}
    {{-- ========================================================= --}}

    <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-3 text-sm">

        {{-- DASHBOARD --}}
        <a
            href="{{ route('dashboard') }}"
            class="group flex items-center gap-3 rounded-md px-3 py-1.5 font-medium transition
                {{
                    request()->routeIs('dashboard')
                        ? 'bg-slate-800 text-white'
                        : 'text-slate-300 hover:bg-slate-900/70 hover:text-white'
                }}"
        >
            <span class="flex h-6 w-6 shrink-0 items-center justify-center text-slate-400 group-hover:text-slate-200"
            >
                <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13h8V3H3v10zm10 8h8V11h-8v10zM3 21h8v-6H3v6zm10-12h8V3h-8v6z"/>
                </svg>
            </span>

            <span class="flex-1">
                Dashboard
            </span>
        </a>


        {{-- CLINICAL --}}
        @if ($canClinical)

            <div class="pt-1">

                <button
                    type="button"
                    @click="clinical = !clinical"
                    class="group flex w-full items-center gap-3 rounded-md px-3 py-1.5 font-medium transition
                        {{
                            $clinicalOpen
                                ? 'text-white'
                                : 'text-slate-300 hover:bg-slate-900/70 hover:text-white'
                        }}"
                >
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center text-slate-400">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6V3z"/>
                        </svg>
                    </span>

                    <span class="flex-1 text-left">
                        Clinical
                    </span>

                    <svg
                        class="h-4 w-4 text-slate-500 transition-transform duration-200"
                        :class="{ 'rotate-90': clinical }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>


                <div
                    x-show="clinical"
                    x-collapse
                    class="ml-6 mt-0.5 space-y-0.5 border-l border-slate-800 pl-3"
                >

                    @if (($canReception || $canNursing || $canDoctor || $canMedicalRecords) && $canPatientsView)

                        <a
                            href="{{ route('patients.index') }}"
                            class="block rounded-md px-3 py-1.5 text-[12.5px] transition
                                {{
                                    request()->routeIs('patients.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-900/70 hover:text-white'
                                }}"
                        >
                            Patients
                        </a>

                    @endif


                    @if (
                        (
                            $canReception
                            || $canNursing
                            || $canDoctor
                            || $canMedicalRecords
                            || $canManagement
                        )
                        && $canOpdView
                    )

                        <a
                            href="{{ route('opd.index') }}"
                            class="block rounded-md px-3 py-1.5 text-[12.5px] transition
                                {{
                                    request()->routeIs('opd.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-900/70 hover:text-white'
                                }}"
                        >
                            OPD
                        </a>

                    @endif


                    @if ($canNursing && $canNursingView)

                        <a
                            href="{{ route('nursing.index') }}"
                            class="block rounded-md px-3 py-1.5 text-[12.5px] transition
                                {{
                                    request()->routeIs('nursing.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-900/70 hover:text-white'
                                }}"
                        >
                            Nursing Station
                        </a>

                    @endif


                    @if (($canReception || $canNursing || $canDoctor || $canEmergency) && $canEmergencyView)

                        <a
                            href="{{ route('emergency.index') }}"
                            class="block rounded-md px-3 py-1.5 text-[12.5px] transition
                                {{
                                    request()->routeIs('emergency.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-900/70 hover:text-white'
                                }}"
                        >
                            Emergency
                        </a>

                    @endif


                    @if (($canReception || $canNursing || $canDoctor || $canIpd) && $canIpdView)

                        <a
                            href="{{ route('ipd.index') }}"
                            class="block rounded-md px-3 py-1.5 text-[12.5px] transition
                                {{
                                    request()->routeIs('ipd.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-900/70 hover:text-white'
                                }}"
                        >
                            IPD / Ward
                        </a>

                    @endif

                </div>

            </div>

        @endif


        {{-- DIAGNOSTICS --}}
        @if ($canDiagnostics)

            <div>

                <button
                    type="button"
                    @click="diagnostics = !diagnostics"
                    class="group flex w-full items-center gap-3 rounded-md px-3 py-1.5 font-medium transition
                        {{
                            $diagnosticsOpen
                                ? 'text-white'
                                : 'text-slate-300 hover:bg-slate-900/70 hover:text-white'
                        }}"
                >
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center text-slate-400">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 3h6M10 3v5l-5 9a2 2 0 001.75 3h10.5A2 2 0 0019 17l-5-9V3"/>
                        </svg>
                    </span>

                    <span class="flex-1 text-left">
                        Diagnostics
                    </span>

                    <svg
                        class="h-4 w-4 text-slate-500 transition-transform duration-200"
                        :class="{ 'rotate-90': diagnostics }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>


                <div
                    x-show="diagnostics"
                    x-collapse
                    class="ml-6 mt-0.5 space-y-0.5 border-l border-slate-800 pl-3"
                >

                    @if ($canLaboratory && $canLaboratoryView)

                        <a
                            href="{{ route('laboratory.index') }}"
                            class="block rounded-md px-3 py-1.5 text-[12.5px] transition
                                {{
                                    request()->routeIs('laboratory.*')
                                    || request()->routeIs('diagnostics.items.result.*')
                                    || request()->routeIs('diagnostics.items.sample.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-900/70 hover:text-white'
                                }}"
                        >
                            Laboratory
                        </a>

                    @endif


                    @if ($canRadiology && $canRadiologyView)

                        <a
                            href="{{ route('imaging.index') }}"
                            class="block rounded-md px-3 py-1.5 text-[12.5px] transition
                                {{
                                    request()->routeIs('imaging.*')
                                    || request()->routeIs('diagnostics.items.imaging-report.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-900/70 hover:text-white'
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

            <div>

                <button
                    type="button"
                    @click="operations = !operations"
                    class="group flex w-full items-center gap-3 rounded-md px-3 py-1.5 font-medium transition
                        {{
                            $operationsOpen
                                ? 'text-white'
                                : 'text-slate-300 hover:bg-slate-900/70 hover:text-white'
                        }}"
                >
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center text-slate-400">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M7 4v6M17 4v6M5 12h14v8H5z"/>
                        </svg>
                    </span>

                    <span class="flex-1 text-left">
                        Operations
                    </span>

                    <svg
                        class="h-4 w-4 text-slate-500 transition-transform duration-200"
                        :class="{ 'rotate-90': operations }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>


                <div
                    x-show="operations"
                    x-collapse
                    class="ml-6 mt-0.5 space-y-0.5 border-l border-slate-800 pl-3"
                >

                    @if ($canPharmacy && $canPharmacyView)

                        <button
                            type="button"
                            @click="pharmacy = !pharmacy"
                            class="flex w-full items-center justify-between rounded-md px-3 py-1.5 text-[13px] font-medium transition
                                {{
                                    $pharmacyOpen
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-900/70 hover:text-white'
                                }}"
                        >
                            <span>Pharmacy</span>

                            <svg
                                class="h-3.5 w-3.5 transition-transform duration-200"
                                :class="{ 'rotate-90': pharmacy }"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>


                        <div
                            x-show="pharmacy"
                            x-collapse
                            class="ml-3 mt-0.5 space-y-0.5 border-l border-slate-800 pl-3"
                        >

                            <a href="{{ route('pharmacy.dashboard') }}"
                               class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('pharmacy.dashboard') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                Dashboard
                            </a>

                            <a href="{{ route('pharmacy.dispensing.index') }}"
                               class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('pharmacy.dispensing.*') || request()->routeIs('pharmacy.returns.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                Dispensing
                            </a>

                            <a href="{{ route('pharmacy.medicines.index') }}"
                               class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('pharmacy.medicines.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                Medicine Master
                            </a>

                            <a href="{{ route('pharmacy.stock-batches.index') }}"
                               class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('pharmacy.stock-batches.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                Pharmacy Stock
                            </a>

                            <a href="{{ route('pharmacy.suppliers.index') }}"
                               class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('pharmacy.suppliers.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                Supplier Master
                            </a>

                            <a href="{{ route('pharmacy.purchase-orders.index') }}"
                               class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('pharmacy.purchase-orders.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                Purchase Orders
                            </a>

                            <a href="{{ route('pharmacy.grns.index') }}"
                               class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('pharmacy.grns.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                GRN Register
                            </a>

                            <a href="{{ route('pharmacy.purchase-returns.index') }}"
                               class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('pharmacy.purchase-returns.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                Purchase Returns
                            </a>

                            <a href="{{ route('pharmacy.supplier-payables.index') }}"
                               class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('pharmacy.supplier-payables.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                Supplier Payables
                            </a>

                            <a href="{{ route('pharmacy.stock-audits.index') }}"
                               class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('pharmacy.stock-audits.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                Stock Audit
                            </a>

                            <a href="{{ route('pharmacy.disposals.index') }}"
                               class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('pharmacy.disposals.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                Disposal Register
                            </a>

                            <a href="{{ route('pharmacy.stock-transfers.index') }}"
                               class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('pharmacy.stock-transfers.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                Stock Transfers
                            </a>

                        </div>

                    @endif


                    @if ($canStores && ! $canPharmacy)

                        <div class="flex items-center justify-between rounded-lg px-3 py-2 text-[13px] text-slate-500">
                            <span>Stores / Inventory</span>
                            <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-slate-500">
                                Soon
                            </span>
                        </div>

                    @endif

                </div>

            </div>

        @endif


        {{-- BUSINESS --}}
        @if ($canBusiness)

            <div>

                <button
                    type="button"
                    @click="business = !business"
                    class="group flex w-full items-center gap-3 rounded-md px-3 py-1.5 font-medium transition
                        {{
                            $businessOpen
                                ? 'text-white'
                                : 'text-slate-300 hover:bg-slate-900/70 hover:text-white'
                        }}"
                >
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center text-slate-400">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16v13H4zM8 7V4h8v3M4 11h16"/>
                        </svg>
                    </span>

                    <span class="flex-1 text-left">
                        Business
                    </span>

                    <svg
                        class="h-4 w-4 text-slate-500 transition-transform duration-200"
                        :class="{ 'rotate-90': business }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>


                <div
                    x-show="business"
                    x-collapse
                    class="ml-6 mt-0.5 space-y-0.5 border-l border-slate-800 pl-3"
                >

                    @if ($canBilling && $canBillingView)

                        <a
                            href="{{ route('billing.index') }}"
                            class="block rounded-md px-3 py-1.5 text-[12.5px] transition
                                {{
                                    request()->routeIs('billing.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-900/70 hover:text-white'
                                }}"
                        >
                            Billing Counter
                        </a>

                    @endif


                    @if ($canBilling && $canIpBillingView)

                        <a
                            href="{{ route('ip-billing.index') }}"
                            class="block rounded-md px-3 py-1.5 text-[12.5px] transition
                                {{
                                    request()->routeIs('ip-billing.*')
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-900/70 hover:text-white'
                                }}"
                        >
                            IP Billing
                        </a>

                    @endif


                    @if ($canFinance)

                        <div class="my-2 border-t border-slate-800"></div>

                        <button
                            type="button"
                            @click="finance = !finance"
                            class="flex w-full items-center justify-between rounded-md px-3 py-1.5 text-[13px] font-medium transition
                                {{
                                    $financeOpen
                                        ? 'bg-slate-800 text-white'
                                        : 'text-slate-400 hover:bg-slate-900/70 hover:text-white'
                                }}"
                        >
                            <span>Finance</span>

                            <svg
                                class="h-3.5 w-3.5 transition-transform duration-200"
                                :class="{ 'rotate-90': finance }"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>


                        <div
                            x-show="finance"
                            x-collapse
                            class="ml-3 mt-0.5 space-y-0.5 border-l border-slate-800 pl-3"
                        >

                            @if ($canFinanceDashboard)
                                <a href="{{ route('finance.dashboard') }}"
                                   class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('finance.dashboard') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                    Dashboard
                                </a>
                            @endif

                            @if ($canFinanceVouchers)
                                <a href="{{ route('finance.vouchers.index') }}"
                                   class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('finance.vouchers.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                    Vouchers
                                </a>
                            @endif

                            @if ($canFinanceReports)
                                <a href="{{ route('finance.reports.index') }}"
                                   class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('finance.reports.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                    Reports
                                </a>
                            @endif

                            @if ($canFinanceMaster)
                                <a href="{{ route('finance.master.index') }}"
                                   class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('finance.master.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
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

            <div>

                <button
                    type="button"
                    @click="administration = !administration"
                    class="group flex w-full items-center gap-3 rounded-md px-3 py-1.5 font-medium transition
                        {{
                            $administrationOpen
                                ? 'text-white'
                                : 'text-slate-300 hover:bg-slate-900/70 hover:text-white'
                        }}"
                >
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center text-slate-400">
                        <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4"/>
                        </svg>
                    </span>

                    <span class="flex-1 text-left">
                        Administration
                    </span>

                    <svg
                        class="h-4 w-4 text-slate-500 transition-transform duration-200"
                        :class="{ 'rotate-90': administration }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>


                <div
                    x-show="administration"
                    x-collapse
                    class="ml-6 mt-0.5 space-y-0.5 border-l border-slate-800 pl-3"
                >

                    @if ($isAdmin)

                        <div class="px-3 pb-1 pt-2 text-[9px] font-bold uppercase tracking-[0.14em] text-slate-600">
                            Workflow
                        </div>

                        <a href="{{ route('administration.dashboard') }}"
                           class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('administration.dashboard') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                            Administration Dashboard
                        </a>

                        <a href="{{ route('administration.requests.index') }}"
                           class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('administration.requests.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                            Requests
                        </a>

                        <a href="{{ route('administration.my-work.index') }}"
                           class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('administration.my-work.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                            My Work
                        </a>


                        <div class="my-2 border-t border-slate-800"></div>

                        <div class="px-3 pb-1 pt-1 text-[9px] font-bold uppercase tracking-[0.14em] text-slate-600">
                            System
                        </div>


                        @if ($canSystemServices)

                            <a href="{{ route('services.index') }}"
                               class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('services.*') && request('scope') !== 'charges' ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                Service Master
                            </a>

                        @endif


                        @if ($canSystemUsers)

                            <a href="{{ route('admin.users.index') }}"
                               class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('admin.users.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                User Management
                            </a>

                        @endif


                        @if (Route::has('admin.role-permissions.index'))

                            <a
                                href="{{ route('admin.role-permissions.index') }}"
                                class="block rounded-md px-3 py-1.5 text-[12.5px] transition
                                    {{
                                        request()->routeIs('admin.role-permissions.*')
                                            ? 'bg-slate-800 text-white'
                                            : 'text-slate-400 hover:bg-slate-900/70 hover:text-white'
                                    }}"
                            >
                                Role & Permissions
                            </a>

                        @endif

                    @endif


                    {{-- MASTER DATA --}}
                    <button
                        type="button"
                        @click="masterData = !masterData"
                        class="mt-1 flex w-full items-center justify-between rounded-md px-3 py-1.5 text-[13px] font-medium transition
                            {{
                                $masterDataOpen
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-900/70 hover:text-white'
                            }}"
                    >
                        <span>Master Data</span>

                        <svg
                            class="h-3.5 w-3.5 transition-transform duration-200"
                            :class="{ 'rotate-90': masterData }"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>


                    <div
                        x-show="masterData"
                        x-collapse
                        class="ml-3 mt-0.5 space-y-0.5 border-l border-slate-800 pl-3"
                    >

                        @if ($canHrDepartments)

                            <a href="{{ route('admin.departments.index') }}"
                               class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('admin.departments.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                Departments
                            </a>

                        @endif


                        @if ($canHrEmployees)

                            <a href="{{ route('admin.employees.index') }}"
                               class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('admin.employees.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                Staff / Employees
                            </a>

                        @endif


                        @if ($isAdmin && $canSystemServices)

                            <a
                                href="{{ route('services.index', ['scope' => 'charges']) }}"
                                class="block rounded-md px-3 py-1.5 text-[12.5px] transition
                                    {{
                                        request()->routeIs('services.*')
                                        && request('scope') === 'charges'
                                            ? 'bg-slate-800 text-white'
                                            : 'text-slate-400 hover:bg-slate-900/70 hover:text-white'
                                    }}"
                            >
                                Charge Master
                            </a>

                        @endif


                        @if ($isAdmin && $canLabParameters)

                            <a href="{{ route('admin.laboratory-parameters.index') }}"
                               class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('admin.laboratory-parameters.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                Laboratory Parameter Master
                            </a>

                        @endif


                        @if ($isAdmin && $canInpatientMaster)

                            <a href="{{ route('inpatient-master.index') }}"
                               class="block rounded-md px-3 py-1.5 text-[12.5px] transition {{ request()->routeIs('inpatient-master.*') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-900/70 hover:text-white' }}">
                                Inpatient Setup
                            </a>

                        @endif

                    </div>

                </div>

            </div>

        @endif

    </nav>


    {{-- ========================================================= --}}
    {{-- FOOTER --}}
    {{-- ========================================================= --}}

    <div class="border-t border-slate-800 px-4 py-3">

        <div class="text-center text-[10px] leading-5 text-slate-500">

            <div>
                © {{ now()->year }} Tura Christian Hospital
            </div>

            <div class="text-slate-600">
                Designed by Dr. Benjamin
            </div>

        </div>

    </div>

</aside>
