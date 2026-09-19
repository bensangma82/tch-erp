@php
    $user = Auth::user();

    $isAdmin = $user->isAdmin();

    $canReception = $isAdmin || $user->hasRole('reception');
    $canNursing = $isAdmin || $user->hasRole('nursing');
    $canBilling = $isAdmin || $user->hasRole('billing');
    $canLaboratory = $isAdmin || $user->hasRole('laboratory');
    $canRadiology = $isAdmin || $user->hasRole('radiology');
    $canPharmacy = $isAdmin || $user->hasRole('pharmacy');
    $canDoctor = $isAdmin || $user->hasRole('doctor');

    $canClinical =
        $canReception
        || $canNursing
        || $canDoctor;

    $canDiagnostics =
        $canLaboratory
        || $canRadiology;

    $canOperations =
        $canPharmacy
        || $isAdmin;

    $canBusiness =
        $canBilling
        || $isAdmin;

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

    $businessOpen =
        request()->routeIs('billing.*')
        || request()->routeIs('ip-billing.*');

    $masterDataOpen =
        request()->routeIs('admin.departments.*')
        || request()->routeIs('admin.employees.*')
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
                    @if ($canReception || $canNursing || $canDoctor)
                        <a
                            href="{{ route('patients.index') }}"
                            class="block rounded-md px-3 py-2 text-sm
                                {{ request()->routeIs('patients.*')
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                        >
                            Patients
                        </a>

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

                    @if ($canNursing)
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

                    @if ($canReception || $canNursing || $canDoctor)
                        <a
                            href="{{ route('emergency.index') }}"
                            class="block rounded-md px-3 py-2 text-sm
                                {{ request()->routeIs('emergency.*')
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                        >
                            Emergency
                        </a>

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
                    @if ($canLaboratory)
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

                    @if ($canRadiology)
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
                    @if ($canPharmacy)
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
                        <a
                            href="{{ route('billing.index') }}"
                            class="block rounded-md px-3 py-2 text-sm
                                {{ request()->routeIs('billing.*')
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                        >
                            Billing Counter
                        </a>

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
                </div>
            </div>
        @endif

        {{-- ADMINISTRATION --}}
        @if ($isAdmin)
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

                        <a
                            href="{{ route('inpatient-master.index') }}"
                            class="block rounded-md px-3 py-2 text-sm
                                {{ request()->routeIs('inpatient-master.*')
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                        >
                            Inpatient Setup
                        </a>
                    </div>
                </div>
            </div>
        @endif

    </nav>
</aside>
