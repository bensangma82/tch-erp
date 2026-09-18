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
@endphp


<aside class="min-h-screen w-64 border-r border-slate-800 bg-slate-950 text-slate-200">

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


        {{-- ========================================================= --}}
        {{-- DASHBOARD --}}
        {{-- ========================================================= --}}

        <a
            href="{{ route('dashboard') }}"
            class="block rounded-md px-3 py-2
                {{ request()->routeIs('dashboard')
                    ? 'bg-slate-800 text-white'
                    : 'hover:bg-slate-800' }}"
        >
            Dashboard
        </a>



        {{-- ========================================================= --}}
        {{-- CLINICAL --}}
        {{-- ========================================================= --}}

        @if ($canClinical)

            <div class="px-3 pb-1 pt-4 text-xs uppercase tracking-wider text-slate-500">
                Clinical
            </div>


            {{-- ===================================================== --}}
            {{-- PATIENTS --}}
            {{-- ===================================================== --}}

            @if ($canReception || $canNursing || $canDoctor)

                <a
                    href="{{ route('patients.index') }}"
                    class="block rounded-md px-3 py-2
                        {{ request()->routeIs('patients.*')
                            ? 'bg-slate-800 text-white'
                            : 'hover:bg-slate-800' }}"
                >
                    Patients
                </a>

            @endif



            {{-- ===================================================== --}}
            {{-- OPD --}}
            {{-- ===================================================== --}}

            @if ($canReception || $canNursing || $canDoctor)

                <a
                    href="{{ route('opd.index') }}"
                    class="block rounded-md px-3 py-2
                        {{ request()->routeIs('opd.*')
                            ? 'bg-slate-800 text-white'
                            : 'hover:bg-slate-800' }}"
                >
                    OPD
                </a>

            @endif



            {{-- ===================================================== --}}
            {{-- NURSING STATION --}}
            {{-- ===================================================== --}}

            @if ($canNursing)

                <a
                    href="{{ route('nursing.index') }}"
                    class="block rounded-md px-3 py-2
                        {{ request()->routeIs('nursing.*')
                            ? 'bg-slate-800 text-white'
                            : 'hover:bg-slate-800' }}"
                >
                    Nursing Station
                </a>

            @endif



            {{-- ===================================================== --}}
            {{-- EMERGENCY --}}
            {{-- ===================================================== --}}

            @if ($canReception || $canNursing || $canDoctor)

                <a
                    href="{{ route('emergency.index') }}"
                    class="mt-2 flex items-center justify-between rounded-md px-3 py-2 font-semibold
                        {{ request()->routeIs('emergency.*')
                            ? 'bg-slate-800 text-white'
                            : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}"
                >
                    <span>
                        Emergency
                    </span>

                    <span class="text-xs text-slate-500">
                        ›
                    </span>
                </a>


                <div class="ml-3 mt-1 space-y-1 border-l border-slate-800 pl-3">


                    {{-- EMERGENCY QUEUE --}}

                    <a
                        href="{{ route('emergency.index') }}"
                        class="block rounded-md px-3 py-2 text-sm
                            {{
                                request()->routeIs('emergency.index')
                                ||
                                request()->routeIs('emergency.show')
                                ||
                                request()->routeIs('emergency.triage.*')
                                ||
                                request()->routeIs('emergency.admission.*')
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                            }}"
                    >
                        Emergency Queue
                    </a>


                    {{-- REGISTER EMERGENCY PATIENT --}}

                    @if ($canReception)

                        <a
                            href="{{ route('emergency.create') }}"
                            class="block rounded-md px-3 py-2 text-sm
                                {{ request()->routeIs('emergency.create')
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                        >
                            Register Patient
                        </a>

                    @endif

                </div>

            @endif



            {{-- ===================================================== --}}
            {{-- IPD --}}
            {{-- ===================================================== --}}

            @if ($canReception || $canNursing || $canDoctor)

                <a
                    href="{{ route('ipd.index') }}"
                    class="mt-2 flex items-center justify-between rounded-md px-3 py-2 font-semibold
                        {{ request()->routeIs('ipd.*')
                            ? 'bg-slate-800 text-white'
                            : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}"
                >
                    <span>
                        IPD
                    </span>

                    <span class="text-xs text-slate-500">
                        ›
                    </span>
                </a>


                <div class="ml-3 mt-1 space-y-1 border-l border-slate-800 pl-3">

                    <a
                        href="{{ route('ipd.index') }}"
                        class="block rounded-md px-3 py-2 text-sm
                            {{ request()->routeIs('ipd.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                    >
                        Patient Census
                    </a>

                </div>

            @endif



            {{-- ===================================================== --}}
            {{-- FUTURE CLINICAL MODULES --}}
            {{-- ===================================================== --}}

            @if ($isAdmin)

                <a
                    href="#"
                    class="mt-2 block rounded-md px-3 py-2 text-slate-500"
                >
                    ICU

                    <span class="float-right text-[10px] uppercase">
                        Soon
                    </span>
                </a>


                <a
                    href="#"
                    class="block rounded-md px-3 py-2 text-slate-500"
                >
                    Endoscopy

                    <span class="float-right text-[10px] uppercase">
                        Soon
                    </span>
                </a>


                <a
                    href="#"
                    class="block rounded-md px-3 py-2 text-slate-500"
                >
                    Echocardiography

                    <span class="float-right text-[10px] uppercase">
                        Soon
                    </span>
                </a>


                <a
                    href="#"
                    class="block rounded-md px-3 py-2 text-slate-500"
                >
                    Dialysis

                    <span class="float-right text-[10px] uppercase">
                        Soon
                    </span>
                </a>

            @endif

        @endif



        {{-- ========================================================= --}}
        {{-- DIAGNOSTICS --}}
        {{-- ========================================================= --}}

        @if ($canDiagnostics)

            <div class="px-3 pb-1 pt-4 text-xs uppercase tracking-wider text-slate-500">
                Diagnostics
            </div>


            {{-- ===================================================== --}}
            {{-- LABORATORY --}}
            {{-- ===================================================== --}}

            @if ($canLaboratory)

                <a
                    href="{{ route('laboratory.index') }}"
                    class="block rounded-md px-3 py-2
                        {{
                            request()->routeIs('laboratory.*')
                            ||
                            request()->routeIs('diagnostics.items.result.*')
                                ? 'bg-slate-800 text-white'
                                : 'hover:bg-slate-800'
                        }}"
                >
                    Laboratory
                </a>

            @endif



            {{-- ===================================================== --}}
            {{-- IMAGING --}}
            {{-- ===================================================== --}}

            @if ($canRadiology)

                <a
                    href="{{ route('imaging.index') }}"
                    class="block rounded-md px-3 py-2
                        {{
                            request()->routeIs('imaging.*')
                            ||
                            request()->routeIs('diagnostics.items.imaging-report.*')
                                ? 'bg-slate-800 text-white'
                                : 'hover:bg-slate-800'
                        }}"
                >
                    Imaging / Radiology
                </a>

            @endif

        @endif



        {{-- ========================================================= --}}
        {{-- OPERATIONS --}}
        {{-- ========================================================= --}}

        @if ($canOperations)

            <div class="px-3 pb-1 pt-4 text-xs uppercase tracking-wider text-slate-500">
                Operations
            </div>


            {{-- ===================================================== --}}
            {{-- PHARMACY --}}
            {{-- ===================================================== --}}

            @if ($canPharmacy)

                <a
                    href="{{ route('pharmacy.dashboard') }}"
                    class="flex items-center justify-between rounded-md px-3 py-2 font-semibold
                        {{ request()->routeIs('pharmacy.dashboard')
                            ? 'bg-slate-800 text-white'
                            : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}"
                >
                    <span>
                        Pharmacy
                    </span>

                    <span class="text-xs text-slate-500">
                        ›
                    </span>
                </a>


                <div class="ml-3 mt-1 space-y-1 border-l border-slate-800 pl-3">


                    {{-- DISPENSING --}}

                    <a
                        href="{{ route('pharmacy.dispensing.index') }}"
                        class="block rounded-md px-3 py-2 text-sm
                            {{
                                request()->routeIs('pharmacy.dispensing.*')
                                ||
                                request()->routeIs('pharmacy.returns.*')
                                    ? 'bg-slate-800 text-white'
                                    : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                            }}"
                    >
                        Dispensing
                    </a>



                    {{-- MEDICINE MASTER --}}

                    <a
                        href="{{ route('pharmacy.medicines.index') }}"
                        class="block rounded-md px-3 py-2 text-sm
                            {{ request()->routeIs('pharmacy.medicines.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                    >
                        Medicine Master
                    </a>



                    {{-- PHARMACY STOCK --}}

                    <a
                        href="{{ route('pharmacy.stock-batches.index') }}"
                        class="block rounded-md px-3 py-2 text-sm
                            {{ request()->routeIs('pharmacy.stock-batches.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                    >
                        Pharmacy Stock
                    </a>



                    {{-- SUPPLIER MASTER --}}

                    <a
                        href="{{ route('pharmacy.suppliers.index') }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition
                            {{ request()->routeIs('pharmacy.suppliers.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>

                        Supplier Master
                    </a>



                    {{-- PURCHASE ORDERS --}}

                    <a
                        href="{{ route('pharmacy.purchase-orders.index') }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition
                            {{ request()->routeIs('pharmacy.purchase-orders.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>

                        Purchase Orders
                    </a>



                    {{-- GRN REGISTER --}}

                    <a
                        href="{{ route('pharmacy.grns.index') }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition
                            {{ request()->routeIs('pharmacy.grns.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>

                        GRN Register
                    </a>



                    {{-- PURCHASE RETURNS --}}

                    <a
                        href="{{ route('pharmacy.purchase-returns.index') }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition
                            {{ request()->routeIs('pharmacy.purchase-returns.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>

                        Purchase Returns
                    </a>



                    {{-- SUPPLIER PAYABLES --}}

                    <a
                        href="{{ route('pharmacy.supplier-payables.index') }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition
                            {{ request()->routeIs('pharmacy.supplier-payables.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>

                        Supplier Payables
                    </a>



                    {{-- STOCK AUDIT --}}

                    <a
                        href="{{ route('pharmacy.stock-audits.index') }}"
                        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition
                            {{ request()->routeIs('pharmacy.stock-audits.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}"
                    >
                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>

                        Stock Audit
                    </a>



                    {{-- DISPOSAL REGISTER --}}

                    <a
                        href="{{ route('pharmacy.disposals.index') }}"
                        class="block rounded-md px-3 py-2 text-sm
                            {{ request()->routeIs('pharmacy.disposals.*')
                                ? 'bg-slate-800 text-white'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}"
                    >
                        Disposal Register
                    </a>



                    {{-- STOCK TRANSFERS --}}

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



            {{-- ===================================================== --}}
            {{-- FUTURE OPERATIONS MODULES --}}
            {{-- ===================================================== --}}

            @if ($isAdmin)

                <a
                    href="#"
                    class="mt-2 block rounded-md px-3 py-2 text-slate-500"
                >
                    Inventory

                    <span class="float-right text-[10px] uppercase">
                        Soon
                    </span>
                </a>


                <a
                    href="#"
                    class="block rounded-md px-3 py-2 text-slate-500"
                >
                    Purchase

                    <span class="float-right text-[10px] uppercase">
                        Soon
                    </span>
                </a>


                <a
                    href="#"
                    class="block rounded-md px-3 py-2 text-slate-500"
                >
                    Assets

                    <span class="float-right text-[10px] uppercase">
                        Soon
                    </span>
                </a>


                <a
                    href="#"
                    class="block rounded-md px-3 py-2 text-slate-500"
                >
                    Maintenance

                    <span class="float-right text-[10px] uppercase">
                        Soon
                    </span>
                </a>

            @endif

        @endif



        {{-- ========================================================= --}}
        {{-- BUSINESS --}}
        {{-- ========================================================= --}}

        @if ($canBusiness)

            <div class="px-3 pb-1 pt-4 text-xs uppercase tracking-wider text-slate-500">
                Business
            </div>


            {{-- ===================================================== --}}
            {{-- BILLING COUNTER --}}
            {{-- ===================================================== --}}

            @if ($canBilling)

                <a
                    href="{{ route('billing.index') }}"
                    class="block rounded-md px-3 py-2
                        {{ request()->routeIs('billing.*')
                            ? 'bg-slate-800 text-white'
                            : 'hover:bg-slate-800' }}"
                >
                    Billing Counter
                </a>


                <a
                    href="{{ route('ip-billing.index') }}"
                    class="block rounded-md px-3 py-2
                        {{ request()->routeIs('ip-billing.*')
                            ? 'bg-slate-800 text-white'
                            : 'hover:bg-slate-800' }}"
                >
                    IP Billing
                </a>

            @endif



            {{-- ===================================================== --}}
            {{-- FUTURE BUSINESS MODULES --}}
            {{-- ===================================================== --}}

            @if ($isAdmin)

                <a
                    href="#"
                    class="block rounded-md px-3 py-2 text-slate-500"
                >
                    MHIS / Insurance

                    <span class="float-right text-[10px] uppercase">
                        Soon
                    </span>
                </a>


                <a
                    href="#"
                    class="block rounded-md px-3 py-2 text-slate-500"
                >
                    Finance

                    <span class="float-right text-[10px] uppercase">
                        Soon
                    </span>
                </a>


                <a
                    href="#"
                    class="block rounded-md px-3 py-2 text-slate-500"
                >
                    HR

                    <span class="float-right text-[10px] uppercase">
                        Soon
                    </span>
                </a>


                <a
                    href="#"
                    class="block rounded-md px-3 py-2 text-slate-500"
                >
                    Reports

                    <span class="float-right text-[10px] uppercase">
                        Soon
                    </span>
                </a>

            @endif

        @endif



        {{-- ========================================================= --}}
        {{-- ADMINISTRATION --}}
        {{-- ========================================================= --}}

        @if ($isAdmin)

            <div class="px-3 pb-1 pt-4 text-xs uppercase tracking-wider text-slate-500">
                Administration
            </div>


            {{-- SERVICE MASTER --}}

            <a
                href="{{ route('services.index') }}"
                class="block rounded-md px-3 py-2
                    {{ request()->routeIs('services.*')
                        ? 'bg-slate-800 text-white'
                        : 'hover:bg-slate-800' }}"
            >
                Service Master
            </a>



            {{-- SYSTEM ADMINISTRATION --}}

            <a
                href="{{ route('admin.users.index') }}"
                class="block rounded-md px-3 py-2
                    {{ request()->routeIs('admin.users.*')
                        ? 'bg-slate-800 text-white'
                        : 'hover:bg-slate-800' }}"
            >
                System Administration
            </a>

        @endif


    </nav>

</aside>