<nav
    x-data="{ open: false }"
    class="bg-slate-900 text-white"
>

    @php
        $user = Auth::user();

        $isAdmin = $user->isAdmin();

        $canReception = $isAdmin || $user->hasRole('reception');
        $canNursing = $isAdmin || $user->hasRole('nursing');
        $canBilling = $isAdmin || $user->hasRole('billing');
        $canLaboratory = $isAdmin || $user->hasRole('laboratory');
        $canRadiology = $isAdmin || $user->hasRole('radiology');
        $canDoctor = $isAdmin || $user->hasRole('doctor');

        $roleLabels = [
            'admin' => 'Administrator',
            'reception' => 'Reception',
            'nursing' => 'Nursing',
            'billing' => 'Billing',
            'laboratory' => 'Laboratory',
            'radiology' => 'Radiology',
            'pharmacy' => 'Pharmacy',
            'doctor' => 'Doctor',
        ];

        $roleLabel = $roleLabels[$user->role] ?? ucfirst($user->role ?? 'Staff');
    @endphp


    {{-- ========================================================= --}}
    {{-- DESKTOP TOP BAR --}}
    {{-- ========================================================= --}}

    <div class="px-4 sm:px-6 lg:px-8">

        <div class="flex h-16 items-center justify-between">


            {{-- BRAND --}}
            <div class="flex items-center gap-3">

                <div
                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10 text-sm font-bold tracking-wide"
                >
                    TCH
                </div>

                <div>

                    <div class="text-sm font-semibold">
                        TCH Hospital ERP
                    </div>

                    <div class="text-xs text-slate-300">
                        Tura Christian Hospital
                    </div>

                </div>

            </div>



            {{-- USER MENU - DESKTOP --}}
            <div class="hidden items-center gap-3 sm:flex">


                {{-- ROLE BADGE --}}
                <div
                    class="hidden rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium text-slate-300 md:block"
                >
                    {{ $roleLabel }}
                </div>


                <x-dropdown align="right" width="48">

                    <x-slot name="trigger">

                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-slate-200 transition hover:bg-white/10 hover:text-white focus:outline-none"
                        >

                            <span>
                                {{ $user->name }}
                            </span>


                            <svg
                                class="h-4 w-4"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >

                                <path
                                    fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd"
                                />

                            </svg>

                        </button>

                    </x-slot>


                    <x-slot name="content">

                        <div
                            class="border-b border-slate-100 px-4 py-3"
                        >

                            <div class="text-sm font-semibold text-slate-800">
                                {{ $user->name }}
                            </div>

                            <div class="mt-0.5 text-xs text-slate-500">
                                {{ $roleLabel }}
                            </div>

                        </div>


                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>


                        <form
                            method="POST"
                            action="{{ route('logout') }}"
                        >

                            @csrf

                            <x-dropdown-link
                                :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();"
                            >
                                {{ __('Log Out') }}
                            </x-dropdown-link>

                        </form>

                    </x-slot>

                </x-dropdown>

            </div>



            {{-- MOBILE MENU BUTTON --}}
            <div class="flex sm:hidden">

                <button
                    type="button"
                    @click="open = ! open"
                    class="rounded-md p-2 text-slate-300 transition hover:bg-white/10 hover:text-white"
                >

                    <span class="sr-only">
                        Open navigation menu
                    </span>


                    <svg
                        class="h-6 w-6"
                        stroke="currentColor"
                        fill="none"
                        viewBox="0 0 24 24"
                    >

                        <path
                            :class="{ 'hidden': open, 'inline-flex': !open }"
                            class="inline-flex"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"
                        />


                        <path
                            :class="{ 'hidden': !open, 'inline-flex': open }"
                            class="hidden"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />

                    </svg>

                </button>

            </div>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- MOBILE NAVIGATION --}}
    {{-- ========================================================= --}}

    <div
        :class="{ 'block': open, 'hidden': !open }"
        class="hidden border-t border-white/10 sm:hidden"
    >

        <div class="px-4 py-4">


            {{-- USER --}}
            <div
                class="mb-4 rounded-xl border border-white/10 bg-white/5 px-4 py-3"
            >

                <div class="text-sm font-semibold text-white">
                    {{ $user->name }}
                </div>

                <div class="mt-1 text-xs text-slate-400">
                    {{ $roleLabel }}
                </div>

            </div>



            <div class="space-y-1">


                {{-- DASHBOARD --}}
                <a
                    href="{{ route('dashboard') }}"
                    class="block rounded-lg px-3 py-2.5 text-sm font-medium text-slate-200 transition hover:bg-white/10 hover:text-white"
                >
                    Dashboard
                </a>



                {{-- ===================================================== --}}
                {{-- RECEPTION --}}
                {{-- ===================================================== --}}

                @if ($canReception)

                    <div
                        class="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500"
                    >
                        Reception
                    </div>


                    <a
                        href="{{ route('patients.index') }}"
                        class="block rounded-lg px-3 py-2.5 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white"
                    >
                        Patients
                    </a>


                    <a
                        href="{{ route('opd.index') }}"
                        class="block rounded-lg px-3 py-2.5 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white"
                    >
                        OPD
                    </a>

                @endif



                {{-- ===================================================== --}}
                {{-- NURSING --}}
                {{-- ===================================================== --}}

                @if ($canNursing)

                    <div
                        class="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500"
                    >
                        Nursing
                    </div>


                    <a
                        href="{{ route('nursing.index') }}"
                        class="block rounded-lg px-3 py-2.5 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white"
                    >
                        Nursing Station
                    </a>

                @endif



                {{-- ===================================================== --}}
                {{-- BILLING --}}
                {{-- ===================================================== --}}

                @if ($canBilling)

                    <div
                        class="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500"
                    >
                        Billing
                    </div>


                    <a
                        href="{{ route('billing.index') }}"
                        class="block rounded-lg px-3 py-2.5 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white"
                    >
                        Billing Counter
                    </a>

                @endif



                {{-- ===================================================== --}}
                {{-- LABORATORY --}}
                {{-- ===================================================== --}}

                @if ($canLaboratory)

                    <div
                        class="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500"
                    >
                        Laboratory
                    </div>


                    <a
                        href="{{ route('laboratory.index') }}"
                        class="block rounded-lg px-3 py-2.5 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white"
                    >
                        Laboratory Worklist
                    </a>

                @endif



                {{-- ===================================================== --}}
                {{-- RADIOLOGY --}}
                {{-- ===================================================== --}}

                @if ($canRadiology)

                    <div
                        class="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500"
                    >
                        Radiology
                    </div>


                    <a
                        href="{{ route('imaging.index') }}"
                        class="block rounded-lg px-3 py-2.5 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white"
                    >
                        Imaging / Radiology
                    </a>

                @endif



                {{-- ===================================================== --}}
                {{-- DOCTOR --}}
                {{-- ===================================================== --}}

                @if ($canDoctor && ! $isAdmin)

                    <div
                        class="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500"
                    >
                        Clinical
                    </div>


                    <a
                        href="{{ route('patients.index') }}"
                        class="block rounded-lg px-3 py-2.5 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white"
                    >
                        Patients
                    </a>


                    <a
                        href="{{ route('opd.index') }}"
                        class="block rounded-lg px-3 py-2.5 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white"
                    >
                        OPD Queue
                    </a>

                @endif



                {{-- ===================================================== --}}
                {{-- ADMINISTRATION --}}
                {{-- ===================================================== --}}

                @if ($isAdmin)

                    <div
                        class="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500"
                    >
                        Administration
                    </div>


                    <a
                        href="{{ route('services.index') }}"
                        class="block rounded-lg px-3 py-2.5 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white"
                    >
                        Service Master
                    </a>


                    <div
                        class="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500"
                    >
                        System Administration
                    </div>


                    <a
                        href="{{ route('admin.users.index') }}"
                        class="block rounded-lg px-3 py-2.5 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white"
                    >
                        User Management
                    </a>


                    <div
                        class="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500"
                    >
                        Master Data
                    </div>


                    <a
                        href="{{ route('admin.departments.index') }}"
                        class="block rounded-lg px-3 py-2.5 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white"
                    >
                        Departments
                    </a>


                    <a
                        href="{{ route('admin.employees.index') }}"
                        class="block rounded-lg px-3 py-2.5 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white"
                    >
                        Staff / Employees
                    </a>


                    <a
                        href="{{ route('inpatient-master.index') }}"
                        class="block rounded-lg px-3 py-2.5 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white"
                    >
                        Inpatient Setup
                    </a>

                @endif



                {{-- PROFILE --}}
                <div
                    class="mt-4 border-t border-white/10 pt-3"
                >

                    <a
                        href="{{ route('profile.edit') }}"
                        class="block rounded-lg px-3 py-2.5 text-sm text-slate-200 transition hover:bg-white/10 hover:text-white"
                    >
                        Profile
                    </a>


                    <form
                        method="POST"
                        action="{{ route('logout') }}"
                    >

                        @csrf

                        <button
                            type="submit"
                            class="block w-full rounded-lg px-3 py-2.5 text-left text-sm text-slate-200 transition hover:bg-white/10 hover:text-white"
                        >
                            Log Out
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</nav>
