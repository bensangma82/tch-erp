<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">
                    Human Resources
                </div>

                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                    Employee Documents
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Secure HR document register with verification and expiry tracking.
                </p>
            </div>


            <a
                href="{{ route('admin.hr.documents.create') }}"
                class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
            >
                Upload Document
            </a>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif


            @if ($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-4">
                    <div class="font-semibold text-red-800">
                        Unable to complete the action:
                    </div>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif



            {{-- SUMMARY CARDS --}}

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                <a
                    href="{{ route('admin.hr.documents.index', ['verification_status' => 'pending']) }}"
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm transition hover:border-amber-300"
                >
                    <div class="text-xs font-semibold uppercase tracking-wide text-amber-700">
                        Pending Verification
                    </div>

                    <div class="mt-2 text-3xl font-bold text-amber-900">
                        {{ $pendingVerificationCount }}
                    </div>
                </a>


                <a
                    href="{{ route('admin.hr.documents.index', ['verification_status' => 'verified']) }}"
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm transition hover:border-emerald-300"
                >
                    <div class="text-xs font-semibold uppercase tracking-wide text-emerald-700">
                        Verified
                    </div>

                    <div class="mt-2 text-3xl font-bold text-emerald-900">
                        {{ $verifiedCount }}
                    </div>
                </a>


                <a
                    href="{{ route('admin.hr.documents.index', ['expiry' => '30']) }}"
                    class="rounded-2xl border border-orange-200 bg-orange-50 p-5 shadow-sm transition hover:border-orange-300"
                >
                    <div class="text-xs font-semibold uppercase tracking-wide text-orange-700">
                        Expiring ≤30 Days
                    </div>

                    <div class="mt-2 text-3xl font-bold text-orange-900">
                        {{ $expiringSoonCount }}
                    </div>
                </a>


                <a
                    href="{{ route('admin.hr.documents.index', ['expiry' => 'expired']) }}"
                    class="rounded-2xl border border-red-200 bg-red-50 p-5 shadow-sm transition hover:border-red-300"
                >
                    <div class="text-xs font-semibold uppercase tracking-wide text-red-700">
                        Expired
                    </div>

                    <div class="mt-2 text-3xl font-bold text-red-900">
                        {{ $expiredCount }}
                    </div>
                </a>

            </div>



            {{-- FILTERS --}}

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="font-semibold text-slate-900">
                        Filters
                    </h3>
                </div>


                <form
                    method="GET"
                    action="{{ route('admin.hr.documents.index') }}"
                    class="grid gap-4 p-6 md:grid-cols-5"
                >

                    <div>
                        <label class="block text-sm font-semibold text-slate-700">
                            Employee
                        </label>

                        <select
                            name="employee_id"
                            class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        >
                            <option value="">All Employees</option>

                            @foreach ($employees as $employee)
                                <option
                                    value="{{ $employee->id }}"
                                    @selected((string) request('employee_id') === (string) $employee->id)
                                >
                                    {{ $employee->employee_code }} - {{ $employee->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div>
                        <label class="block text-sm font-semibold text-slate-700">
                            Document Type
                        </label>

                        <select
                            name="document_type"
                            class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        >
                            <option value="">All Types</option>

                            @foreach ($documentTypes as $type)
                                <option
                                    value="{{ $type }}"
                                    @selected(request('document_type') === $type)
                                >
                                    {{ ucwords(str_replace('_', ' ', $type)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div>
                        <label class="block text-sm font-semibold text-slate-700">
                            Verification
                        </label>

                        <select
                            name="verification_status"
                            class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        >
                            <option value="">Any Status</option>
                            <option value="pending" @selected(request('verification_status') === 'pending')>Pending</option>
                            <option value="verified" @selected(request('verification_status') === 'verified')>Verified</option>
                            <option value="rejected" @selected(request('verification_status') === 'rejected')>Rejected</option>
                        </select>
                    </div>


                    <div>
                        <label class="block text-sm font-semibold text-slate-700">
                            Expiry
                        </label>

                        <select
                            name="expiry"
                            class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        >
                            <option value="">Any</option>
                            <option value="30" @selected(request('expiry') === '30')>Within 30 Days</option>
                            <option value="expired" @selected(request('expiry') === 'expired')>Expired</option>
                            <option value="none" @selected(request('expiry') === 'none')>No Expiry Date</option>
                        </select>
                    </div>


                    <div class="flex items-end gap-2">

                        <button
                            type="submit"
                            class="inline-flex flex-1 items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                        >
                            Apply
                        </button>

                        <a
                            href="{{ route('admin.hr.documents.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                        >
                            Reset
                        </a>

                    </div>

                </form>

            </section>



            {{-- DOCUMENT REGISTER --}}

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="font-semibold text-slate-900">
                        Document Register
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        {{ $documents->total() }} document{{ $documents->total() === 1 ? '' : 's' }} found.
                    </p>
                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Employee</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Document</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Dates</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Expiry</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Verification</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">File</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100 bg-white">

                            @forelse ($documents as $document)

                                @php
                                    $days = $document->days_until_expiry;

                                    $verificationClass = match ($document->verification_status) {
                                        'verified' => 'bg-emerald-100 text-emerald-700',
                                        'rejected' => 'bg-red-100 text-red-700',
                                        default => 'bg-amber-100 text-amber-700',
                                    };
                                @endphp

                                <tr>

                                    <td class="px-5 py-4 align-top">

                                        <div class="font-semibold text-slate-900">
                                            {{ $document->employee?->full_name ?? '—' }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $document->employee?->employee_code ?? '—' }}

                                            @if ($document->employee?->department)
                                                · {{ $document->employee->department->name }}
                                            @endif
                                        </div>

                                    </td>


                                    <td class="px-5 py-4 align-top">

                                        <div class="font-semibold text-slate-900">
                                            {{ $document->title }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $document->document_type_label }}
                                        </div>

                                        @if ($document->reference_no)
                                            <div class="mt-1 text-xs text-slate-400">
                                                Ref: {{ $document->reference_no }}
                                            </div>
                                        @endif

                                    </td>


                                    <td class="px-5 py-4 align-top text-sm text-slate-700">

                                        <div>
                                            Issue:
                                            {{ $document->issue_date?->format('d M Y') ?? '—' }}
                                        </div>

                                        <div class="mt-1">
                                            Expiry:
                                            {{ $document->expiry_date?->format('d M Y') ?? '—' }}
                                        </div>

                                    </td>


                                    <td class="px-5 py-4 align-top">

                                        @if ($days === null)

                                            <span class="text-sm text-slate-400">
                                                No expiry
                                            </span>

                                        @elseif ($days < 0)

                                            <span class="font-semibold text-red-700">
                                                Expired {{ abs($days) }} day(s) ago
                                            </span>

                                        @elseif ($days <= 30)

                                            <span class="font-semibold text-orange-700">
                                                {{ $days }} day(s) remaining
                                            </span>

                                        @else

                                            <span class="text-sm font-medium text-slate-700">
                                                {{ $days }} day(s)
                                            </span>

                                        @endif

                                    </td>


                                    <td class="px-5 py-4 align-top">

                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $verificationClass }}">
                                            {{ ucfirst($document->verification_status) }}
                                        </span>

                                        @if ($document->verifiedBy)
                                            <div class="mt-2 text-xs text-slate-500">
                                                By {{ $document->verifiedBy->name ?? 'User' }}
                                            </div>
                                        @endif

                                        @if ($document->verification_remarks)
                                            <div class="mt-1 max-w-xs text-xs text-slate-400">
                                                {{ $document->verification_remarks }}
                                            </div>
                                        @endif

                                    </td>


                                    <td class="px-5 py-4 align-top">

                                        <div class="max-w-[220px] truncate text-sm font-medium text-slate-700">
                                            {{ $document->original_filename }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $document->formatted_file_size ?? 'Size unavailable' }}
                                        </div>

                                    </td>


                                    <td class="px-5 py-4 align-top">

                                        <div class="flex min-w-[190px] flex-wrap gap-2">

                                            <a
                                                href="{{ route('admin.hr.documents.view', $document) }}"
                                                target="_blank"
                                                rel="noopener"
                                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                            >
                                                View
                                            </a>

                                            <a
                                                href="{{ route('admin.hr.documents.download', $document) }}"
                                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                            >
                                                Download
                                            </a>


                                            @if ($document->verification_status !== 'verified')

                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.hr.documents.verify', $document) }}"
                                                >
                                                    @csrf
                                                    @method('PATCH')

                                                    <button
                                                        type="submit"
                                                        class="inline-flex items-center rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-800"
                                                    >
                                                        Verify
                                                    </button>
                                                </form>

                                            @endif

                                        </div>


                                        @if ($document->verification_status !== 'rejected')

                                            <form
                                                method="POST"
                                                action="{{ route('admin.hr.documents.reject', $document) }}"
                                                class="mt-3 space-y-2"
                                            >
                                                @csrf
                                                @method('PATCH')

                                                <input
                                                    type="text"
                                                    name="verification_remarks"
                                                    required
                                                    maxlength="5000"
                                                    placeholder="Reason for rejection"
                                                    class="block w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-red-500 focus:ring-red-500"
                                                >

                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100"
                                                >
                                                    Reject
                                                </button>

                                            </form>

                                        @endif

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td
                                        colspan="7"
                                        class="px-6 py-12 text-center text-sm text-slate-500"
                                    >
                                        No employee documents found.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                @if ($documents->hasPages())
                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $documents->links() }}
                    </div>
                @endif

            </section>

        </div>

    </div>

</x-app-layout>
