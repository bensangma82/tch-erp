@php
    $user = Auth::user();

    $isAdmin = $user->isAdmin();

    $isReception  = $user->hasRole('reception');
    $isNursing    = $user->hasRole('nursing');
    $isBilling    = $user->hasRole('billing');
    $isLaboratory = $user->hasRole('laboratory');
    $isRadiology  = $user->hasRole('radiology');
    $isDoctor     = $user->hasRole('doctor');

    /*
    |--------------------------------------------------------------------------
    | Dashboard Permissions
    |--------------------------------------------------------------------------
    |
    | These control visibility only.
    | Route middleware remains the actual security layer.
    |
    */

    $showOpd =
        $isAdmin
        || $isReception
        || $isNursing
        || $isDoctor;

    $showVitals =
        $isAdmin
        || $isNursing;

    $showLaboratory =
        $isAdmin
        || $isLaboratory;

    $showImaging =
        $isAdmin
        || $isRadiology;

    $showBilling =
        $isAdmin
        || $isBilling;

    $showDoctorQueue =
        $isAdmin
        || $isReception
        || $isNursing
        || $isDoctor;

    $showPatients =
        $isAdmin
        || $isReception
        || $isNursing
        || $isDoctor;

    $showQuickOpd =
        $isAdmin
        || $isReception
        || $isNursing
        || $isDoctor;

    $showQuickNursing =
        $isAdmin
        || $isNursing;

    $showQuickBilling =
        $isAdmin
        || $isBilling;

    $showQuickLaboratory =
        $isAdmin
        || $isLaboratory;

    $showQuickImaging =
        $isAdmin
        || $isRadiology;
@endphp


<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Hospital Dashboard
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Tura Christian Hospital ERP
                </p>

            </div>


            <div class="flex items-center gap-3">

                {{-- LIVE STATUS --}}
                <div
                    id="dashboard-live-status"
                    class="hidden items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 sm:flex"
                >

                    <span class="relative flex h-2.5 w-2.5">

                        <span
                            id="dashboard-live-ping"
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                        ></span>

                        <span
                            id="dashboard-live-dot"
                            class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"
                        ></span>

                    </span>

                    <span id="dashboard-live-text">
                        Live
                    </span>

                </div>


                {{-- DATE --}}
                <div class="rounded-xl border border-slate-200 bg-white px-4 py-2 shadow-sm">

                    <div class="text-xs text-slate-400">
                        {{ now()->format('l') }}
                    </div>

                    <div class="text-sm font-semibold text-slate-800">
                        {{ now()->format('d F Y') }}
                    </div>

                </div>

            </div>

        </div>

    </x-slot>



    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-[1500px] px-4 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- HERO --}}
            {{-- ========================================================= --}}

            <section
                class="relative overflow-hidden rounded-2xl border border-slate-200 shadow-sm"
                style="
                    min-height: 250px;

                    background-image:
                        linear-gradient(
                            90deg,
                            rgba(5, 35, 75, 0.96) 0%,
                            rgba(5, 52, 94, 0.82) 38%,
                            rgba(3, 36, 64, 0.30) 70%,
                            rgba(3, 30, 55, 0.16) 100%
                        ),
                        url('{{ asset('images/tch2.png') }}');

                    background-size: cover;
                    background-position: center;
                "
            >

                <div class="relative z-10 flex min-h-[250px] flex-col justify-between p-7 text-white sm:p-9 lg:p-10">

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-[0.35em] text-blue-100">
                            Tura Christian Hospital
                        </div>


                        <h1 class="mt-5 max-w-xl text-4xl font-light leading-tight tracking-tight sm:text-5xl">
                            Healing Together
                        </h1>


                        <p class="mt-2 text-xl font-light text-blue-50 sm:text-2xl">
                            for a Healthier Tomorrow
                        </p>


                        <div class="mt-5 h-1 w-16 rounded-full bg-teal-400"></div>

                    </div>



                    <div class="mt-8 flex flex-wrap gap-x-8 gap-y-4 text-xs sm:text-sm">


                        {{-- PEOPLE --}}
                        <div class="flex items-center gap-3">

                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white/15 backdrop-blur">

                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 000-7.78z"
                                    />
                                </svg>

                            </div>


                            <div>

                                <div class="font-semibold">
                                    People
                                </div>

                                <div class="text-white/70">
                                    at the centre
                                </div>

                            </div>

                        </div>



                        {{-- CARE --}}
                        <div class="flex items-center gap-3">

                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white/15 backdrop-blur">

                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 21s-6-4.35-8.25-7.75C1.5 9.85 3.15 5.5 7 5.5c2.1 0 3.4 1.1 5 3 1.6-1.9 2.9-3 5-3 3.85 0 5.5 4.35 3.25 7.75C18 16.65 12 21 12 21z"
                                    />
                                </svg>

                            </div>


                            <div>

                                <div class="font-semibold">
                                    Care
                                </div>

                                <div class="text-white/70">
                                    for every life
                                </div>

                            </div>

                        </div>



                        {{-- STRONGER --}}
                        <div class="flex items-center gap-3">

                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white/15 backdrop-blur">

                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 3l7 4v5c0 5-3 8-7 9-4-1-7-4-7-9V7l7-4z"
                                    />
                                </svg>

                            </div>


                            <div>

                                <div class="font-semibold">
                                    Stronger
                                </div>

                                <div class="text-white/70">
                                    healthier communities
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </section>



            {{-- ========================================================= --}}
            {{-- ACTIVITY HEADER --}}
            {{-- ========================================================= --}}

            <div class="mt-8 flex items-end justify-between">

                <div>

                    <h3 class="text-lg font-bold text-slate-900">
                        Today's Hospital Activity
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">

                        @if ($isAdmin)

                            Current operational status across hospital services.

                        @elseif ($isLaboratory)

                            Current laboratory workload and activity.

                        @elseif ($isRadiology)

                            Current imaging and radiology workload.

                        @elseif ($isBilling)

                            Current billing workload requiring attention.

                        @elseif ($isNursing)

                            Current OPD and nursing workload.

                        @elseif ($isDoctor)

                            Current OPD consultation queue.

                        @elseif ($isReception)

                            Current OPD registration and consultation activity.

                        @else

                            Current hospital activity.

                        @endif

                    </p>

                </div>


                <div class="hidden text-xs text-slate-400 sm:block">

                    Updated

                    <span
                        id="dashboard-updated-time"
                        class="font-medium text-slate-500"
                    >
                        {{ now()->format('h:i:s A') }}
                    </span>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- ROLE-AWARE LIVE METRIC CARDS --}}
            {{-- ========================================================= --}}

            <div
                class="mt-5 grid gap-4"
                style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));"
            >


                {{-- TODAY OPD --}}
                @if ($showOpd)

                    <a
                        href="{{ route('opd.index') }}"
                        class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-lg"
                    >

                        <div class="flex items-start justify-between">

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-700">

                                <svg
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"
                                    />
                                </svg>

                            </div>


                            <span class="text-xs font-semibold text-blue-600">
                                OPD
                            </span>

                        </div>


                        <div class="mt-5 text-sm font-medium text-slate-500">
                            Today's OPD
                        </div>


                        <div class="mt-1 text-3xl font-bold tracking-tight text-slate-900">

                            <span
                                id="metric-today-opd"
                                class="inline-block transition duration-300"
                            >
                                {{ $todayOpd }}
                            </span>

                        </div>


                        <div class="mt-2 text-xs text-slate-400">
                            Registered encounters
                        </div>

                    </a>

                @endif



                {{-- AWAITING VITALS --}}
                @if ($showVitals)

                    <a
                        href="{{ route('nursing.index') }}"
                        class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-lg"
                    >

                        <div class="flex items-start justify-between">

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">

                                <svg
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3 12h4l2-5 4 10 2-5h6"
                                    />
                                </svg>

                            </div>


                            <span class="text-xs font-semibold text-amber-600">
                                Vitals
                            </span>

                        </div>


                        <div class="mt-5 text-sm font-medium text-slate-500">
                            Awaiting Vitals
                        </div>


                        <div class="mt-1 text-3xl font-bold tracking-tight text-slate-900">

                            <span
                                id="metric-awaiting-vitals"
                                class="inline-block transition duration-300"
                            >
                                {{ $awaitingVitals }}
                            </span>

                        </div>


                        <div class="mt-2 text-xs text-slate-400">
                            Waiting at nursing station
                        </div>

                    </a>

                @endif



                {{-- LABORATORY --}}
                @if ($showLaboratory)

                    <a
                        href="{{ route('laboratory.index') }}"
                        class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-purple-200 hover:shadow-lg"
                    >

                        <div class="flex items-start justify-between">

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-purple-50 text-purple-700">

                                <svg
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M9 3h6M10 3v5l-5 9a2 2 0 001.75 3h10.5A2 2 0 0019 17l-5-9V3"
                                    />
                                </svg>

                            </div>


                            <span class="text-xs font-semibold text-purple-600">
                                Lab
                            </span>

                        </div>


                        <div class="mt-5 text-sm font-medium text-slate-500">
                            Laboratory
                        </div>


                        <div class="mt-1 text-3xl font-bold tracking-tight text-slate-900">

                            <span
                                id="metric-laboratory"
                                class="inline-block transition duration-300"
                            >
                                {{ $pendingLaboratory }}
                            </span>

                        </div>


                        <div class="mt-2 text-xs text-slate-400">
                            Pending / in process
                        </div>

                    </a>

                @endif



                {{-- IMAGING --}}
                @if ($showImaging)

                    <a
                        href="{{ route('imaging.index') }}"
                        class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-cyan-200 hover:shadow-lg"
                    >

                        <div class="flex items-start justify-between">

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-cyan-50 text-cyan-700">

                                <svg
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >

                                    <rect
                                        x="3"
                                        y="5"
                                        width="18"
                                        height="14"
                                        rx="2"
                                    />

                                    <circle
                                        cx="9"
                                        cy="10"
                                        r="2"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M21 15l-4-4-6 6-3-3-5 5"
                                    />

                                </svg>

                            </div>


                            <span class="text-xs font-semibold text-cyan-600">
                                Imaging
                            </span>

                        </div>


                        <div class="mt-5 text-sm font-medium text-slate-500">
                            Imaging
                        </div>


                        <div class="mt-1 text-3xl font-bold tracking-tight text-slate-900">

                            <span
                                id="metric-imaging"
                                class="inline-block transition duration-300"
                            >
                                {{ $pendingImaging }}
                            </span>

                        </div>


                        <div class="mt-2 text-xs text-slate-400">
                            Pending / in process
                        </div>

                    </a>

                @endif



                {{-- BILLING --}}
                @if ($showBilling)

                    <a
                        href="{{ route('billing.index') }}"
                        class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-rose-200 hover:shadow-lg"
                    >

                        <div class="flex items-start justify-between">

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-50 text-rose-600">

                                <svg
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M6 2h9l5 5v15H6z"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M14 2v6h6M9 13h6M9 17h6"
                                    />

                                </svg>

                            </div>


                            <span class="text-xs font-semibold text-rose-600">
                                Billing
                            </span>

                        </div>


                        <div class="mt-5 text-sm font-medium text-slate-500">
                            Awaiting Payment
                        </div>


                        <div class="mt-1 text-3xl font-bold tracking-tight text-slate-900">

                            <span
                                id="metric-awaiting-payment"
                                class="inline-block transition duration-300"
                            >
                                {{ $awaitingPayment }}
                            </span>

                        </div>


                        <div class="mt-2 text-xs text-slate-400">
                            Investigation orders
                        </div>

                    </a>

                @endif



                {{-- DOCTOR QUEUE --}}
                @if ($showDoctorQueue)

                    <a
                        href="{{ route('opd.index') }}"
                        class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-emerald-200 hover:shadow-lg"
                    >

                        <div class="flex items-start justify-between">

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">

                                <svg
                                    class="h-6 w-6"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >

                                    <circle
                                        cx="12"
                                        cy="7"
                                        r="4"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M5.5 21a6.5 6.5 0 0113 0"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M18 8h4M20 6v4"
                                    />

                                </svg>

                            </div>


                            <span class="text-xs font-semibold text-emerald-600">
                                Queue
                            </span>

                        </div>


                        <div class="mt-5 text-sm font-medium text-slate-500">
                            Waiting for Doctor
                        </div>


                        <div class="mt-1 text-3xl font-bold tracking-tight text-slate-900">

                            <span
                                id="metric-waiting-doctor"
                                class="inline-block transition duration-300"
                            >
                                {{ $waitingForDoctor }}
                            </span>

                        </div>


                        <div class="mt-2 text-xs text-slate-400">
                            Vitals completed
                        </div>

                    </a>

                @endif

            </div>



            {{-- ========================================================= --}}
            {{-- QUICK ACCESS --}}
            {{-- ========================================================= --}}

            <section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">


                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-bold text-slate-900">
                        Quick Access
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Modules available to your account
                    </p>

                </div>


                <div
                    class="grid gap-3 p-5"
                    style="grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));"
                >


                    {{-- PATIENTS --}}
                    @if ($showPatients)

                        <a
                            href="{{ route('patients.index') }}"
                            class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-4 transition hover:border-blue-200 hover:bg-blue-50/50"
                        >

                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 font-bold text-blue-700">
                                P
                            </div>


                            <div>

                                <div class="text-sm font-semibold text-slate-800">
                                    Patients
                                </div>

                                <div class="text-xs text-slate-400">

                                    @if ($isReception || $isAdmin)
                                        Register & manage
                                    @else
                                        View patients
                                    @endif

                                </div>

                            </div>

                        </a>

                    @endif



                    {{-- OPD --}}
                    @if ($showQuickOpd)

                        <a
                            href="{{ route('opd.index') }}"
                            class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-4 transition hover:border-emerald-200 hover:bg-emerald-50/50"
                        >

                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 font-bold text-emerald-700">
                                O
                            </div>


                            <div>

                                <div class="text-sm font-semibold text-slate-800">
                                    OPD
                                </div>

                                <div class="text-xs text-slate-400">

                                    @if ($isReception || $isAdmin)
                                        Registration & queue
                                    @elseif ($isDoctor)
                                        Consultation queue
                                    @else
                                        Patient queue
                                    @endif

                                </div>

                            </div>

                        </a>

                    @endif



                    {{-- NURSING --}}
                    @if ($showQuickNursing)

                        <a
                            href="{{ route('nursing.index') }}"
                            class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-4 transition hover:border-rose-200 hover:bg-rose-50/50"
                        >

                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-rose-50 font-bold text-rose-700">
                                N
                            </div>


                            <div>

                                <div class="text-sm font-semibold text-slate-800">
                                    Nursing
                                </div>

                                <div class="text-xs text-slate-400">
                                    Vitals & patient care
                                </div>

                            </div>

                        </a>

                    @endif



                    {{-- BILLING --}}
                    @if ($showQuickBilling)

                        <a
                            href="{{ route('billing.index') }}"
                            class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-4 transition hover:border-amber-200 hover:bg-amber-50/50"
                        >

                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 font-bold text-amber-700">
                                ₹
                            </div>


                            <div>

                                <div class="text-sm font-semibold text-slate-800">
                                    Billing
                                </div>

                                <div class="text-xs text-slate-400">
                                    Payments & orders
                                </div>

                            </div>

                        </a>

                    @endif



                    {{-- LAB --}}
                    @if ($showQuickLaboratory)

                        <a
                            href="{{ route('laboratory.index') }}"
                            class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-4 transition hover:border-purple-200 hover:bg-purple-50/50"
                        >

                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-purple-50 font-bold text-purple-700">
                                L
                            </div>


                            <div>

                                <div class="text-sm font-semibold text-slate-800">
                                    Laboratory
                                </div>

                                <div class="text-xs text-slate-400">
                                    Tests & results
                                </div>

                            </div>

                        </a>

                    @endif



                    {{-- IMAGING --}}
                    @if ($showQuickImaging)

                        <a
                            href="{{ route('imaging.index') }}"
                            class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-4 transition hover:border-cyan-200 hover:bg-cyan-50/50"
                        >

                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-cyan-50 font-bold text-cyan-700">
                                I
                            </div>


                            <div>

                                <div class="text-sm font-semibold text-slate-800">
                                    Imaging
                                </div>

                                <div class="text-xs text-slate-400">
                                    Radiology workflow
                                </div>

                            </div>

                        </a>

                    @endif

                </div>

            </section>



            {{-- ========================================================= --}}
            {{-- LOWER DASHBOARD --}}
            {{-- ========================================================= --}}

            <div class="mt-6 grid gap-6 lg:grid-cols-2">


                {{-- ===================================================== --}}
                {{-- ROLE-AWARE OPERATIONAL STATUS --}}
                {{-- ===================================================== --}}

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">


                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-bold text-slate-900">
                            Operational Status
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Current workload requiring your attention
                        </p>

                    </div>


                    <div class="space-y-6 p-6">


                        @if ($showVitals)

                            <div>

                                <div class="flex items-center justify-between text-sm">

                                    <span class="font-medium text-slate-600">
                                        Awaiting Vitals
                                    </span>

                                    <span
                                        id="status-awaiting-vitals"
                                        class="font-bold text-slate-900"
                                    >
                                        {{ $awaitingVitals }}
                                    </span>

                                </div>


                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">

                                    <div
                                        id="bar-awaiting-vitals"
                                        class="h-full rounded-full bg-amber-400 transition-all duration-500"
                                        style="width: {{ min($awaitingVitals * 10, 100) }}%"
                                    ></div>

                                </div>

                            </div>

                        @endif



                        @if ($showLaboratory)

                            <div>

                                <div class="flex items-center justify-between text-sm">

                                    <span class="font-medium text-slate-600">
                                        Laboratory Workload
                                    </span>

                                    <span
                                        id="status-laboratory"
                                        class="font-bold text-slate-900"
                                    >
                                        {{ $pendingLaboratory }}
                                    </span>

                                </div>


                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">

                                    <div
                                        id="bar-laboratory"
                                        class="h-full rounded-full bg-purple-500 transition-all duration-500"
                                        style="width: {{ min($pendingLaboratory * 8, 100) }}%"
                                    ></div>

                                </div>

                            </div>

                        @endif



                        @if ($showImaging)

                            <div>

                                <div class="flex items-center justify-between text-sm">

                                    <span class="font-medium text-slate-600">
                                        Imaging Workload
                                    </span>

                                    <span
                                        id="status-imaging"
                                        class="font-bold text-slate-900"
                                    >
                                        {{ $pendingImaging }}
                                    </span>

                                </div>


                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">

                                    <div
                                        id="bar-imaging"
                                        class="h-full rounded-full bg-cyan-500 transition-all duration-500"
                                        style="width: {{ min($pendingImaging * 8, 100) }}%"
                                    ></div>

                                </div>

                            </div>

                        @endif



                        @if ($showBilling)

                            <div>

                                <div class="flex items-center justify-between text-sm">

                                    <span class="font-medium text-slate-600">
                                        Pending Payments
                                    </span>

                                    <span
                                        id="status-awaiting-payment"
                                        class="font-bold text-slate-900"
                                    >
                                        {{ $awaitingPayment }}
                                    </span>

                                </div>


                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">

                                    <div
                                        id="bar-awaiting-payment"
                                        class="h-full rounded-full bg-rose-500 transition-all duration-500"
                                        style="width: {{ min($awaitingPayment * 8, 100) }}%"
                                    ></div>

                                </div>

                            </div>

                        @endif



                        @if ($showDoctorQueue)

                            <div>

                                <div class="flex items-center justify-between text-sm">

                                    <span class="font-medium text-slate-600">
                                        Waiting for Doctor
                                    </span>

                                    <span
                                        id="status-waiting-doctor"
                                        class="font-bold text-slate-900"
                                    >
                                        {{ $waitingForDoctor }}
                                    </span>

                                </div>


                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">

                                    <div
                                        id="bar-waiting-doctor"
                                        class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                        style="width: {{ min($waitingForDoctor * 10, 100) }}%"
                                    ></div>

                                </div>

                            </div>

                        @endif


                        @if (
                            ! $showVitals
                            && ! $showLaboratory
                            && ! $showImaging
                            && ! $showBilling
                            && ! $showDoctorQueue
                        )

                            <div class="py-6 text-center text-sm text-slate-500">
                                No operational tasks assigned to this role.
                            </div>

                        @endif

                    </div>

                </section>



                {{-- ===================================================== --}}
                {{-- SYSTEM INFO --}}
                {{-- ===================================================== --}}

                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">


                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-bold text-slate-900">
                            Hospital ERP
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            System overview
                        </p>

                    </div>


                    <div class="p-6">

                        <div class="rounded-2xl bg-gradient-to-br from-slate-950 via-blue-950 to-slate-900 p-7 text-white">

                            <div class="text-xs font-semibold uppercase tracking-[0.25em] text-blue-200">
                                Tura Christian Hospital
                            </div>


                            <div class="mt-4 text-2xl font-semibold">
                                Hospital Operations
                            </div>


                            <p class="mt-3 max-w-md text-sm leading-6 text-slate-300">
                                Integrated patient registration, OPD, nursing,
                                billing, laboratory and imaging workflows.
                            </p>



                            <div class="mt-7 rounded-xl border border-white/10 bg-white/5 p-4">

                                <div class="flex items-center justify-between">

                                    <div>

                                        <div class="text-xs text-slate-400">
                                            Logged in as
                                        </div>

                                        <div class="mt-1 text-sm font-semibold text-white">
                                            {{ $user->name }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ ucfirst($user->role ?? 'staff') }}
                                        </div>

                                    </div>


                                    <div class="text-right">

                                        <div class="text-xs text-slate-400">
                                            Dashboard
                                        </div>

                                        <div
                                            id="system-connection-text"
                                            class="mt-1 text-sm font-semibold text-emerald-300"
                                        >
                                            Connected
                                        </div>

                                    </div>


                                    <span
                                        id="system-connection-dot"
                                        class="h-3 w-3 rounded-full bg-emerald-400"
                                    ></span>

                                </div>

                            </div>



                            <div class="mt-5 text-xs text-slate-400">

                                Automatic refresh:

                                <span class="font-semibold text-slate-200">
                                    every 15 seconds
                                </span>

                            </div>

                        </div>

                    </div>

                </section>

            </div>



            {{-- ========================================================= --}}
            {{-- FOOTER --}}
            {{-- ========================================================= --}}

            <div class="mt-8 flex flex-col gap-2 border-t border-slate-200 py-5 text-xs text-slate-400 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    TCH Hospital ERP
                </div>

                <div>
                    Tura Christian Hospital • Estd. 1908
                </div>

            </div>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- LIVE DASHBOARD SCRIPT --}}
    {{-- ========================================================= --}}

    <script>

        document.addEventListener('DOMContentLoaded', function () {

            const metricsUrl =
                @json(route('dashboard.metrics'));

            let isRefreshing = false;


            /*
            |--------------------------------------------------------------------------
            | Set text
            |--------------------------------------------------------------------------
            */

            function setText(id, value) {

                const element =
                    document.getElementById(id);

                if (!element) {
                    return;
                }

                element.textContent =
                    value ?? 0;
            }


            /*
            |--------------------------------------------------------------------------
            | Update metric
            |--------------------------------------------------------------------------
            */

            function updateMetric(id, value) {

                const element =
                    document.getElementById(id);

                /*
                |--------------------------------------------------------------------------
                | Element may intentionally be hidden for this role.
                |--------------------------------------------------------------------------
                */

                if (!element) {
                    return;
                }


                const previousValue =
                    element.textContent.trim();

                const newValue =
                    String(value ?? 0);


                if (previousValue !== newValue) {

                    element.textContent =
                        newValue;

                    element.classList.add(
                        'scale-110',
                        'text-blue-700'
                    );


                    setTimeout(function () {

                        element.classList.remove(
                            'scale-110',
                            'text-blue-700'
                        );

                    }, 500);

                } else {

                    element.textContent =
                        newValue;

                }
            }


            /*
            |--------------------------------------------------------------------------
            | Update progress bar
            |--------------------------------------------------------------------------
            */

            function updateBar(
                id,
                value,
                multiplier
            ) {

                const element =
                    document.getElementById(id);

                if (!element) {
                    return;
                }


                const number =
                    Number(value) || 0;

                const width =
                    Math.min(
                        number * multiplier,
                        100
                    );


                element.style.width =
                    width + '%';
            }


            /*
            |--------------------------------------------------------------------------
            | Dashboard connection status
            |--------------------------------------------------------------------------
            */

            function setLiveStatus(status) {

                const container =
                    document.getElementById(
                        'dashboard-live-status'
                    );

                const text =
                    document.getElementById(
                        'dashboard-live-text'
                    );

                const dot =
                    document.getElementById(
                        'dashboard-live-dot'
                    );

                const ping =
                    document.getElementById(
                        'dashboard-live-ping'
                    );

                const systemText =
                    document.getElementById(
                        'system-connection-text'
                    );

                const systemDot =
                    document.getElementById(
                        'system-connection-dot'
                    );


                if (!container || !text) {
                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | UPDATING
                |--------------------------------------------------------------------------
                */

                if (status === 'updating') {

                    text.textContent =
                        'Updating';


                    container.classList.remove(
                        'border-red-200',
                        'bg-red-50',
                        'text-red-700',
                        'border-emerald-200',
                        'bg-emerald-50',
                        'text-emerald-700'
                    );


                    container.classList.add(
                        'border-amber-200',
                        'bg-amber-50',
                        'text-amber-700'
                    );


                    if (dot) {

                        dot.classList.remove(
                            'bg-red-500',
                            'bg-emerald-500'
                        );

                        dot.classList.add(
                            'bg-amber-500'
                        );
                    }


                    if (ping) {

                        ping.classList.remove(
                            'bg-red-400',
                            'bg-emerald-400'
                        );

                        ping.classList.add(
                            'bg-amber-400'
                        );
                    }


                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | ERROR
                |--------------------------------------------------------------------------
                */

                if (status === 'error') {

                    text.textContent =
                        'Connection issue';


                    container.classList.remove(
                        'border-emerald-200',
                        'bg-emerald-50',
                        'text-emerald-700',
                        'border-amber-200',
                        'bg-amber-50',
                        'text-amber-700'
                    );


                    container.classList.add(
                        'border-red-200',
                        'bg-red-50',
                        'text-red-700'
                    );


                    if (dot) {

                        dot.classList.remove(
                            'bg-emerald-500',
                            'bg-amber-500'
                        );

                        dot.classList.add(
                            'bg-red-500'
                        );
                    }


                    if (ping) {

                        ping.classList.remove(
                            'bg-emerald-400',
                            'bg-amber-400'
                        );

                        ping.classList.add(
                            'bg-red-400'
                        );
                    }


                    if (systemText) {

                        systemText.textContent =
                            'Connection issue';

                        systemText.classList.remove(
                            'text-emerald-300'
                        );

                        systemText.classList.add(
                            'text-red-300'
                        );
                    }


                    if (systemDot) {

                        systemDot.classList.remove(
                            'bg-emerald-400'
                        );

                        systemDot.classList.add(
                            'bg-red-400'
                        );
                    }


                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | LIVE
                |--------------------------------------------------------------------------
                */

                text.textContent =
                    'Live';


                container.classList.remove(
                    'border-red-200',
                    'bg-red-50',
                    'text-red-700',
                    'border-amber-200',
                    'bg-amber-50',
                    'text-amber-700'
                );


                container.classList.add(
                    'border-emerald-200',
                    'bg-emerald-50',
                    'text-emerald-700'
                );


                if (dot) {

                    dot.classList.remove(
                        'bg-red-500',
                        'bg-amber-500'
                    );

                    dot.classList.add(
                        'bg-emerald-500'
                    );
                }


                if (ping) {

                    ping.classList.remove(
                        'bg-red-400',
                        'bg-amber-400'
                    );

                    ping.classList.add(
                        'bg-emerald-400'
                    );
                }


                if (systemText) {

                    systemText.textContent =
                        'Connected';

                    systemText.classList.remove(
                        'text-red-300'
                    );

                    systemText.classList.add(
                        'text-emerald-300'
                    );
                }


                if (systemDot) {

                    systemDot.classList.remove(
                        'bg-red-400'
                    );

                    systemDot.classList.add(
                        'bg-emerald-400'
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Fetch latest dashboard metrics
            |--------------------------------------------------------------------------
            */

            async function refreshDashboardMetrics() {

                if (isRefreshing) {
                    return;
                }


                isRefreshing = true;

                setLiveStatus(
                    'updating'
                );


                try {

                    const response =
                        await fetch(
                            metricsUrl,
                            {
                                method: 'GET',

                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },

                                credentials:
                                    'same-origin',

                                cache:
                                    'no-store'
                            }
                        );


                    if (!response.ok) {

                        throw new Error(
                            'Dashboard metrics request failed with HTTP '
                            + response.status
                        );
                    }


                    const data =
                        await response.json();



                    /*
                    |--------------------------------------------------------------------------
                    | Metric Cards
                    |--------------------------------------------------------------------------
                    */

                    updateMetric(
                        'metric-today-opd',
                        data.todayOpd
                    );

                    updateMetric(
                        'metric-awaiting-vitals',
                        data.awaitingVitals
                    );

                    updateMetric(
                        'metric-waiting-doctor',
                        data.waitingForDoctor
                    );

                    updateMetric(
                        'metric-laboratory',
                        data.pendingLaboratory
                    );

                    updateMetric(
                        'metric-imaging',
                        data.pendingImaging
                    );

                    updateMetric(
                        'metric-awaiting-payment',
                        data.awaitingPayment
                    );



                    /*
                    |--------------------------------------------------------------------------
                    | Operational Status
                    |--------------------------------------------------------------------------
                    */

                    setText(
                        'status-awaiting-vitals',
                        data.awaitingVitals
                    );

                    setText(
                        'status-laboratory',
                        data.pendingLaboratory
                    );

                    setText(
                        'status-imaging',
                        data.pendingImaging
                    );

                    setText(
                        'status-awaiting-payment',
                        data.awaitingPayment
                    );

                    setText(
                        'status-waiting-doctor',
                        data.waitingForDoctor
                    );



                    /*
                    |--------------------------------------------------------------------------
                    | Progress Bars
                    |--------------------------------------------------------------------------
                    */

                    updateBar(
                        'bar-awaiting-vitals',
                        data.awaitingVitals,
                        10
                    );

                    updateBar(
                        'bar-laboratory',
                        data.pendingLaboratory,
                        8
                    );

                    updateBar(
                        'bar-imaging',
                        data.pendingImaging,
                        8
                    );

                    updateBar(
                        'bar-awaiting-payment',
                        data.awaitingPayment,
                        8
                    );

                    updateBar(
                        'bar-waiting-doctor',
                        data.waitingForDoctor,
                        10
                    );



                    /*
                    |--------------------------------------------------------------------------
                    | Updated Time
                    |--------------------------------------------------------------------------
                    */

                    const updatedTime =
                        document.getElementById(
                            'dashboard-updated-time'
                        );


                    if (
                        updatedTime
                        && data.updated_at
                    ) {

                        updatedTime.textContent =
                            data.updated_at;
                    }


                    setLiveStatus(
                        'live'
                    );


                } catch (error) {

                    console.error(
                        'TCH dashboard live refresh error:',
                        error
                    );


                    setLiveStatus(
                        'error'
                    );


                } finally {

                    isRefreshing =
                        false;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Initial Refresh
            |--------------------------------------------------------------------------
            */

            refreshDashboardMetrics();


            /*
            |--------------------------------------------------------------------------
            | Refresh every 15 seconds
            |--------------------------------------------------------------------------
            */

            window.setInterval(
                refreshDashboardMetrics,
                15000
            );


            /*
            |--------------------------------------------------------------------------
            | Refresh when returning to browser tab
            |--------------------------------------------------------------------------
            */

            document.addEventListener(
                'visibilitychange',
                function () {

                    if (
                        document.visibilityState
                        === 'visible'
                    ) {

                        refreshDashboardMetrics();
                    }
                }
            );


            /*
            |--------------------------------------------------------------------------
            | Refresh when browser regains focus
            |--------------------------------------------------------------------------
            */

            window.addEventListener(
                'focus',
                function () {

                    refreshDashboardMetrics();
                }
            );

        });

    </script>

</x-app-layout>