<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">
                    Human Resources
                </div>

                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                    HR Dashboard
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Workforce oversight, staffing distribution, leave monitoring and contract expiry tracking.
                </p>

            </div>


            <div class="flex flex-wrap gap-2">

                <a
                    href="{{ route('admin.hr.leave-requests.create') }}"
                    class="inline-flex items-center rounded-lg border border-cyan-200 bg-cyan-50 px-4 py-2 text-sm font-semibold text-cyan-800 shadow-sm hover:bg-cyan-100"
                >
                    New Leave Request
                </a>

                <a
                    href="{{ route('admin.employees.index') }}"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                >
                    Employee Directory
                </a>

                <a
                    href="{{ route('admin.employees.create') }}"
                    class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                >
                    Add Employee
                </a>

            </div>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- HERO --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-cyan-100 bg-gradient-to-br from-cyan-50 via-white to-slate-50 shadow-sm">

                <div class="grid gap-6 px-6 py-7 lg:grid-cols-[1.5fr_1fr] lg:items-center">

                    <div>

                        <div class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-700">
                            HR Command Center
                        </div>

                        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
                            Workforce Overview
                        </h1>

                        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                            Monitor staff strength, employment categories, department allocation,
                            employee master quality, leave activity, contract expiry and HR document compliance from one place.
                        </p>

                    </div>


                    <div class="grid grid-cols-2 gap-3">

                        <div class="rounded-xl border border-white/70 bg-white/80 p-4 shadow-sm">

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Active Departments
                            </div>

                            <div class="mt-1 text-2xl font-bold text-slate-900">
                                {{ $activeDepartmentCount }}
                            </div>

                        </div>


                        <div class="rounded-xl border border-white/70 bg-white/80 p-4 shadow-sm">

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Joined This Year
                            </div>

                            <div class="mt-1 text-2xl font-bold text-slate-900">
                                {{ $joinedThisYear }}
                            </div>

                        </div>

                    </div>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- LEAVE MANAGEMENT LIVE METRICS --}}
            {{-- ========================================================= --}}

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">

                <a
                    href="{{ route('admin.hr.leave-requests.index', ['status' => 'pending']) }}"
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm transition hover:border-amber-300 hover:shadow"
                >
                    <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">
                        Pending Leave
                    </div>

                    <div class="mt-2 text-3xl font-bold text-amber-900">
                        {{ $pendingLeaveCount }}
                    </div>

                    <div class="mt-2 text-xs text-amber-700">
                        Requests awaiting HR action
                    </div>
                </a>


                <a
                    href="{{ route('admin.hr.leave-requests.index', ['status' => 'approved', 'year' => now()->year]) }}"
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm transition hover:border-emerald-300 hover:shadow"
                >
                    <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                        Approved This Month
                    </div>

                    <div class="mt-2 text-3xl font-bold text-emerald-900">
                        {{ $approvedLeaveThisMonth }}
                    </div>

                    <div class="mt-2 text-xs text-emerald-700">
                        Approved requests starting this month
                    </div>
                </a>


                <a
                    href="{{ route('admin.hr.leave-requests.index', ['status' => 'approved']) }}"
                    class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm transition hover:border-blue-300 hover:shadow"
                >
                    <div class="text-xs font-semibold uppercase tracking-wide text-blue-700">
                        On Leave Today
                    </div>

                    <div class="mt-2 text-3xl font-bold text-blue-900">
                        {{ $employeesOnLeaveToday }}
                    </div>

                    <div class="mt-2 text-xs text-blue-700">
                        Employees with approved leave today
                    </div>
                </a>

            </div>





            {{-- ========================================================= --}}
            {{-- CONTRACT MANAGEMENT LIVE METRICS --}}
            {{-- ========================================================= --}}

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">

                <a
                    href="{{ route('admin.hr.contracts.index', ['status' => 'active']) }}"
                    class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 shadow-sm transition hover:border-indigo-300 hover:shadow"
                >
                    <div class="text-xs font-semibold uppercase tracking-wide text-indigo-700">
                        Active Contracts
                    </div>

                    <div class="mt-2 text-3xl font-bold text-indigo-900">
                        {{ $activeContractCount }}
                    </div>

                    <div class="mt-2 text-xs text-indigo-700">
                        Current active contract records
                    </div>
                </a>


                <a
                    href="{{ route('admin.hr.contracts.index', ['expiring' => '30']) }}"
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm transition hover:border-amber-300 hover:shadow"
                >
                    <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">
                        Contracts Expiring ≤30 Days
                    </div>

                    <div class="mt-2 text-3xl font-bold text-amber-900">
                        {{ $expiringContractCount }}
                    </div>

                    <div class="mt-2 text-xs text-amber-700">
                        Contracts requiring early renewal review
                    </div>
                </a>


                <a
                    href="{{ route('admin.hr.contracts.index', ['expiring' => 'expired']) }}"
                    class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm transition hover:border-red-300 hover:shadow"
                >
                    <div class="text-xs font-semibold uppercase tracking-wide text-red-700">
                        Expired Contracts
                    </div>

                    <div class="mt-2 text-3xl font-bold text-red-900">
                        {{ $expiredContractCount }}
                    </div>

                    <div class="mt-2 text-xs text-red-700">
                        Past end date and not yet closed
                    </div>
                </a>

            </div>



            {{-- ========================================================= --}}
            {{-- CONTRACTS REQUIRING ATTENTION --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                    <div>
                        <h3 class="font-semibold text-slate-900">
                            Contracts Requiring Attention
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Expired contracts and active contracts due to end within the next 30 days.
                        </p>
                    </div>

                    <a
                        href="{{ route('admin.hr.contracts.index') }}"
                        class="text-sm font-semibold text-blue-600 hover:text-blue-800"
                    >
                        Open Contract Register
                    </a>

                </div>


                <div class="divide-y divide-slate-100">

                    @forelse ($contractsRequiringAttention as $contract)

                        @php
                            $daysRemaining = $contract->days_remaining;
                        @endphp

                        <div class="flex flex-col gap-3 px-6 py-4 lg:flex-row lg:items-center lg:justify-between">

                            <div>

                                <div class="flex flex-wrap items-center gap-2">

                                    <div class="font-semibold text-slate-900">
                                        {{ $contract->employee?->full_name ?? 'Unknown Employee' }}
                                    </div>

                                    @if ($daysRemaining !== null && $daysRemaining < 0)

                                        <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                            Expired
                                        </span>

                                    @elseif ($daysRemaining !== null)

                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                            {{ $daysRemaining }} day(s) remaining
                                        </span>

                                    @endif

                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $contract->contract_no }}
                                    ·
                                    Ends {{ $contract->end_date?->format('d M Y') ?? '—' }}

                                    @if ($contract->department)
                                        · {{ $contract->department->name }}
                                    @elseif ($contract->employee?->department)
                                        · {{ $contract->employee->department->name }}
                                    @endif
                                </div>

                            </div>


                            <a
                                href="{{ route('admin.hr.contracts.edit', $contract) }}"
                                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                            >
                                Manage
                            </a>

                        </div>

                    @empty

                        <div class="px-6 py-10 text-center text-sm text-slate-500">
                            No contracts currently require attention.
                        </div>

                    @endforelse

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- EMPLOYEE DOCUMENT LIVE METRICS --}}
            {{-- ========================================================= --}}

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">

                <a
                    href="{{ route('admin.hr.documents.index', ['verification_status' => 'pending']) }}"
                    class="rounded-2xl border border-violet-200 bg-violet-50 p-5 shadow-sm transition hover:border-violet-300 hover:shadow"
                >
                    <div class="text-xs font-semibold uppercase tracking-wide text-violet-700">
                        Documents Pending Verification
                    </div>

                    <div class="mt-2 text-3xl font-bold text-violet-900">
                        {{ $pendingDocumentVerificationCount }}
                    </div>

                    <div class="mt-2 text-xs text-violet-700">
                        Uploaded records awaiting HR verification
                    </div>
                </a>


                <a
                    href="{{ route('admin.hr.documents.index', ['expiry' => '30']) }}"
                    class="rounded-2xl border border-orange-200 bg-orange-50 p-5 shadow-sm transition hover:border-orange-300 hover:shadow"
                >
                    <div class="text-xs font-semibold uppercase tracking-wide text-orange-700">
                        Documents Expiring ≤30 Days
                    </div>

                    <div class="mt-2 text-3xl font-bold text-orange-900">
                        {{ $expiringDocumentCount }}
                    </div>

                    <div class="mt-2 text-xs text-orange-700">
                        Registration or credential renewals due soon
                    </div>
                </a>


                <a
                    href="{{ route('admin.hr.documents.index', ['expiry' => 'expired']) }}"
                    class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm transition hover:border-red-300 hover:shadow"
                >
                    <div class="text-xs font-semibold uppercase tracking-wide text-red-700">
                        Expired Documents
                    </div>

                    <div class="mt-2 text-3xl font-bold text-red-900">
                        {{ $expiredDocumentCount }}
                    </div>

                    <div class="mt-2 text-xs text-red-700">
                        Documents already past their recorded expiry date
                    </div>
                </a>

            </div>



            {{-- ========================================================= --}}
            {{-- DOCUMENTS REQUIRING ATTENTION --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                    <div>
                        <h3 class="font-semibold text-slate-900">
                            Documents Requiring Attention
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Pending verification, expired documents and documents expiring within 30 days.
                        </p>
                    </div>

                    <a
                        href="{{ route('admin.hr.documents.index') }}"
                        class="text-sm font-semibold text-blue-600 hover:text-blue-800"
                    >
                        Open Document Register
                    </a>

                </div>


                <div class="divide-y divide-slate-100">

                    @forelse ($documentsRequiringAttention as $document)

                        @php
                            $daysUntilExpiry = $document->days_until_expiry;
                        @endphp

                        <div class="flex flex-col gap-3 px-6 py-4 lg:flex-row lg:items-center lg:justify-between">

                            <div>

                                <div class="flex flex-wrap items-center gap-2">

                                    <div class="font-semibold text-slate-900">
                                        {{ $document->employee?->full_name ?? 'Unknown Employee' }}
                                    </div>


                                    @if ($daysUntilExpiry !== null && $daysUntilExpiry < 0)

                                        <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                            Expired
                                        </span>

                                    @elseif ($daysUntilExpiry !== null && $daysUntilExpiry <= 30)

                                        <span class="rounded-full bg-orange-100 px-2.5 py-1 text-xs font-semibold text-orange-700">
                                            {{ $daysUntilExpiry }} day(s) remaining
                                        </span>

                                    @endif


                                    @if ($document->verification_status === 'pending')

                                        <span class="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-700">
                                            Pending verification
                                        </span>

                                    @endif

                                </div>


                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $document->title }}
                                    ·
                                    {{ $document->document_type_label }}

                                    @if ($document->expiry_date)
                                        · Expires {{ $document->expiry_date->format('d M Y') }}
                                    @endif

                                    @if ($document->employee?->department)
                                        · {{ $document->employee->department->name }}
                                    @endif
                                </div>

                            </div>


                            <div class="flex flex-wrap gap-2">

                                <a
                                    href="{{ route('admin.hr.documents.view', $document) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                >
                                    View
                                </a>

                                <a
                                    href="{{ route('admin.hr.documents.index', ['employee_id' => $document->employee_id]) }}"
                                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                >
                                    Manage
                                </a>

                            </div>

                        </div>

                    @empty

                        <div class="px-6 py-10 text-center text-sm text-slate-500">
                            No employee documents currently require attention.
                        </div>

                    @endforelse

                </div>

            </div>

            {{-- ========================================================= --}}
            {{-- MAIN KPI GRID --}}
            {{-- ========================================================= --}}

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Total Employees
                    </div>
                    <div class="mt-2 text-3xl font-bold text-slate-900">
                        {{ $totalEmployees }}
                    </div>
                    <div class="mt-2 text-xs text-slate-500">
                        All employee master records
                    </div>
                </div>

                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                        Active Employees
                    </div>
                    <div class="mt-2 text-3xl font-bold text-emerald-900">
                        {{ $activeEmployees }}
                    </div>
                    <div class="mt-2 text-xs text-emerald-700">
                        Currently active workforce
                    </div>
                </div>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">
                        Inactive Employees
                    </div>
                    <div class="mt-2 text-3xl font-bold text-amber-900">
                        {{ $inactiveEmployees }}
                    </div>
                    <div class="mt-2 text-xs text-amber-700">
                        Inactive employee records
                    </div>
                </div>

                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-blue-700">
                        Joined This Month
                    </div>
                    <div class="mt-2 text-3xl font-bold text-blue-900">
                        {{ $joinedThisMonth }}
                    </div>
                    <div class="mt-2 text-xs text-blue-700">
                        New joining records this month
                    </div>
                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- PENDING LEAVE ACTIONS --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                    <div>
                        <h3 class="font-semibold text-slate-900">
                            Leave Requests Needing Action
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Next pending requests awaiting review.
                        </p>
                    </div>

                    <a
                        href="{{ route('admin.hr.leave-requests.index', ['status' => 'pending']) }}"
                        class="text-sm font-semibold text-blue-600 hover:text-blue-800"
                    >
                        Open Leave Register
                    </a>

                </div>


                <div class="divide-y divide-slate-100">

                    @forelse ($pendingLeaveRequests as $leaveRequest)

                        <div class="flex flex-col gap-3 px-6 py-4 lg:flex-row lg:items-center lg:justify-between">

                            <div>

                                <div class="flex flex-wrap items-center gap-2">

                                    <div class="font-semibold text-slate-900">
                                        {{ $leaveRequest->employee?->full_name ?? 'Unknown Employee' }}
                                    </div>

                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                        Pending
                                    </span>

                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $leaveRequest->leaveType?->name ?? 'Leave' }}
                                    ·
                                    {{ $leaveRequest->date_range_label }}
                                    ·
                                    {{ number_format((float) $leaveRequest->total_days, 1) }} day(s)

                                    @if ($leaveRequest->employee?->department)
                                        · {{ $leaveRequest->employee->department->name }}
                                    @endif
                                </div>

                            </div>


                            <div class="text-xs font-semibold text-slate-400">
                                {{ $leaveRequest->request_no }}
                            </div>

                        </div>

                    @empty

                        <div class="px-6 py-10 text-center text-sm text-slate-500">
                            No pending leave requests.
                        </div>

                    @endforelse

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- EMPLOYMENT / CLINICAL MIX --}}
            {{-- ========================================================= --}}

            <div class="grid gap-6 lg:grid-cols-2">

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">
                        <h3 class="font-semibold text-slate-900">
                            Employment Type
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Current employee records grouped by employment category.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 p-6 sm:grid-cols-4">

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Permanent</div>
                            <div class="mt-2 text-2xl font-bold text-slate-900">{{ $permanentEmployees }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Temporary</div>
                            <div class="mt-2 text-2xl font-bold text-slate-900">{{ $temporaryEmployees }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Contractual</div>
                            <div class="mt-2 text-2xl font-bold text-slate-900">{{ $contractualEmployees }}</div>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Unspecified</div>
                            <div class="mt-2 text-2xl font-bold text-slate-900">{{ $unspecifiedEmploymentType }}</div>
                        </div>

                    </div>

                </div>


                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">
                        <h3 class="font-semibold text-slate-900">
                            Clinical Workforce Mix
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Doctor and non-doctor staff classification.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 p-6">

                        <div class="rounded-xl border border-violet-200 bg-violet-50 p-5">
                            <div class="text-xs font-semibold uppercase tracking-wide text-violet-700">Doctors</div>
                            <div class="mt-2 text-3xl font-bold text-violet-900">{{ $doctorCount }}</div>
                        </div>

                        <div class="rounded-xl border border-cyan-200 bg-cyan-50 p-5">
                            <div class="text-xs font-semibold uppercase tracking-wide text-cyan-700">Other Staff</div>
                            <div class="mt-2 text-3xl font-bold text-cyan-900">{{ $nonDoctorCount }}</div>
                        </div>

                    </div>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- DEPARTMENT STRENGTH --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                    <div>
                        <h3 class="font-semibold text-slate-900">
                            Department-wise Staff Strength
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Workforce distribution based on employee department assignment.
                        </p>
                    </div>

                    <a
                        href="{{ route('admin.departments.index') }}"
                        class="text-sm font-semibold text-blue-600 hover:text-blue-800"
                    >
                        Manage Departments
                    </a>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Department</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Total Staff</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Active</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Inactive</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 bg-white">

                            @forelse ($departmentStrength as $row)

                                @php
                                    $totalStaff = (int) $row->total_staff;
                                    $activeStaff = (int) $row->active_staff;
                                    $inactiveStaff = max(0, $totalStaff - $activeStaff);
                                @endphp

                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900">
                                            {{ $row->department?->name ?? 'Unknown Department' }}
                                        </div>

                                        @if ($row->department?->code)
                                            <div class="mt-1 text-xs text-slate-400">
                                                {{ $row->department->code }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-right font-semibold text-slate-900">{{ $totalStaff }}</td>
                                    <td class="px-6 py-4 text-right text-emerald-700">{{ $activeStaff }}</td>
                                    <td class="px-6 py-4 text-right text-slate-500">{{ $inactiveStaff }}</td>
                                </tr>

                            @empty

                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-500">
                                        No department staffing data is available yet.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- RECENT JOINERS / DATA QUALITY --}}
            {{-- ========================================================= --}}

            <div class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5">

                        <div>
                            <h3 class="font-semibold text-slate-900">
                                Recent Joiners
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                Latest employees based on date of joining.
                            </p>
                        </div>

                        <a
                            href="{{ route('admin.employees.index') }}"
                            class="text-sm font-semibold text-blue-600 hover:text-blue-800"
                        >
                            View All
                        </a>

                    </div>


                    <div class="divide-y divide-slate-100">

                        @forelse ($recentEmployees as $employee)

                            <div class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">

                                <div>
                                    <div class="font-semibold text-slate-900">
                                        {{ $employee->full_name }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $employee->designation ?: 'No designation' }}

                                        @if ($employee->department)
                                            · {{ $employee->department->name }}
                                        @endif
                                    </div>
                                </div>

                                <div class="text-sm font-medium text-slate-600">
                                    {{ $employee->date_of_joining?->format('d M Y') ?? '—' }}
                                </div>

                            </div>

                        @empty

                            <div class="px-6 py-10 text-center text-sm text-slate-500">
                                No joining dates have been recorded yet.
                            </div>

                        @endforelse

                    </div>

                </div>


                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">
                        <h3 class="font-semibold text-slate-900">
                            HR Data Quality
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            Employee records requiring master-data attention.
                        </p>
                    </div>

                    <div class="space-y-3 p-6">

                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-sm text-slate-700">No Department Assigned</div>
                            <div class="font-bold {{ $employeesWithoutDepartment > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                                {{ $employeesWithoutDepartment }}
                            </div>
                        </div>

                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-sm text-slate-700">Missing Joining Date</div>
                            <div class="font-bold {{ $employeesWithoutJoiningDate > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                                {{ $employeesWithoutJoiningDate }}
                            </div>
                        </div>

                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-sm text-slate-700">Missing Designation</div>
                            <div class="font-bold {{ $employeesWithoutDesignation > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                                {{ $employeesWithoutDesignation }}
                            </div>
                        </div>

                        <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-sm text-slate-700">Employment Type Unspecified</div>
                            <div class="font-bold {{ $unspecifiedEmploymentType > 0 ? 'text-amber-700' : 'text-emerald-700' }}">
                                {{ $unspecifiedEmploymentType }}
                            </div>
                        </div>

                    </div>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- HR MODULE QUICK LINKS --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        HR Module
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Employee administration and leave-management tools.
                    </p>

                </div>


                <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7">

                    <a
                        href="{{ route('admin.employees.index') }}"
                        class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-blue-300 hover:bg-blue-50"
                    >
                        <div class="font-semibold text-slate-900">
                            Employee Master
                        </div>
                        <div class="mt-1 text-xs leading-5 text-slate-500">
                            Manage staff records and department assignment.
                        </div>
                    </a>


                    <a
                        href="{{ route('admin.departments.index') }}"
                        class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-blue-300 hover:bg-blue-50"
                    >
                        <div class="font-semibold text-slate-900">
                            Department Master
                        </div>
                        <div class="mt-1 text-xs leading-5 text-slate-500">
                            Maintain hospital department structure.
                        </div>
                    </a>


                    <a
                        href="{{ route('admin.hr.leave-requests.index') }}"
                        class="rounded-xl border border-amber-200 bg-amber-50 p-4 transition hover:border-amber-300"
                    >
                        <div class="font-semibold text-amber-900">
                            Leave Requests
                        </div>
                        <div class="mt-1 text-xs leading-5 text-amber-700">
                            Review applications and approval status.
                        </div>
                    </a>


                    <a
                        href="{{ route('admin.hr.leave-balances.index') }}"
                        class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 transition hover:border-emerald-300"
                    >
                        <div class="font-semibold text-emerald-900">
                            Leave Balances
                        </div>
                        <div class="mt-1 text-xs leading-5 text-emerald-700">
                            Maintain entitlement and remaining balances.
                        </div>
                    </a>


                    <a
                        href="{{ route('admin.hr.leave-types.index') }}"
                        class="rounded-xl border border-cyan-200 bg-cyan-50 p-4 transition hover:border-cyan-300"
                    >
                        <div class="font-semibold text-cyan-900">
                            Leave Type Master
                        </div>
                        <div class="mt-1 text-xs leading-5 text-cyan-700">
                            Configure leave categories and rules.
                        </div>
                    </a>


                    <a
                        href="{{ route('admin.hr.contracts.index') }}"
                        class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 transition hover:border-indigo-300"
                    >
                        <div class="font-semibold text-indigo-900">
                            Contract Management
                        </div>
                        <div class="mt-1 text-xs leading-5 text-indigo-700">
                            Track contract periods, renewals and expiry alerts.
                        </div>
                    </a>


                    <a
                        href="{{ route('admin.hr.documents.index') }}"
                        class="rounded-xl border border-violet-200 bg-violet-50 p-4 transition hover:border-violet-300"
                    >
                        <div class="font-semibold text-violet-900">
                            Employee Documents
                        </div>
                        <div class="mt-1 text-xs leading-5 text-violet-700">
                            Manage credentials, files, verification and expiry alerts.
                        </div>
                    </a>

                </div>

            </div>


        </div>

    </div>

</x-app-layout>
