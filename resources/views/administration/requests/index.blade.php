<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-2xl font-bold text-slate-900">Administration</h2>
                <p class="mt-1 text-sm text-slate-500">Decisions, responsibility and completion</p></div>
            @if (\App\Support\AdministrativeRequestAccess::allows(auth()->user(), 'create'))
<a href="{{ route('administration.requests.create') }}" class="rounded-lg bg-slate-900 px-4 py-3 text-sm font-semibold text-white">New Administrative Request</a>
            @endif
        </div>
    </x-slot>
    @php

        $statusLabels = [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'verified' => 'Verified',
            'pending_ms_approval' => 'Pending MS Approval',
            'ms_approved' => 'MS Approved',
            'ms_rejected' => 'MS Rejected',
            'higher_approval_required' => 'Higher Approval Required',
            'higher_approved' => 'Higher Approved',
            'execution_in_progress' => 'Execution In Progress',
            'executed' => 'Awaiting Completion Verification',
            'execution_verified' => 'Completion Verified',
            'closed' => 'Closed',
            'cancelled' => 'Cancelled',
        ];

        $statusClasses = [
            'draft' => 'bg-slate-100 text-slate-700',
            'submitted' => 'bg-blue-50 text-blue-700',
            'verified' => 'bg-cyan-50 text-cyan-700',
            'pending_ms_approval' => 'bg-amber-50 text-amber-700',
            'ms_approved' => 'bg-emerald-50 text-emerald-700',
            'ms_rejected' => 'bg-red-50 text-red-700',
            'higher_approval_required' => 'bg-orange-50 text-orange-700',
            'higher_approved' => 'bg-teal-50 text-teal-700',
            'execution_in_progress' => 'bg-violet-50 text-violet-700',
            'executed' => 'bg-indigo-50 text-indigo-700',
            'execution_verified' => 'bg-emerald-50 text-emerald-700',
            'closed' => 'bg-slate-200 text-slate-700',
            'cancelled' => 'bg-slate-100 text-slate-500',
        ];

        $typeLabels = [
            'purchase' => 'Purchase',
            'recruitment' => 'Recruitment',
            'finance' => 'Finance',
            'contract' => 'Contract',
            'project' => 'Project',
            'maintenance' => 'Maintenance',
            'hr' => 'HR',
            'other' => 'Other',
        ];

        $priorityClasses = [
            'low' => 'bg-slate-100 text-slate-600',
            'normal' => 'bg-blue-50 text-blue-700',
            'high' => 'bg-amber-50 text-amber-700',
            'urgent' => 'bg-red-50 text-red-700',
        ];

    @endphp
    <div class="bg-slate-50 py-6">
    <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6">
        @foreach (['success', 'error'] as $message)
            @if (session($message))<div role="alert" class="rounded-xl border bg-white p-4">{{ session($message) }}</div>@endif
        @endforeach
        @if ($errors->any())<div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4">{{ $errors->first() }}</div>@endif
        <div class="flex flex-wrap items-center justify-between gap-2 text-sm text-slate-600">
            <span>{{ number_format($summary['total']) }} total requests · Counts cover all requests; filters apply to the register.</span>
            <a href="{{ route('administration.my-work.index') }}" class="font-semibold text-indigo-700">My Work →</a>
        </div>
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['mine', 'Assigned to me', 'mine'],
                ['overdue', 'Overdue', 'overdue'],
                ['verification', 'Awaiting verification', 'verification'],
                ['ms', 'Awaiting MS decision', 'pending_ms'],
                ['higher', 'Awaiting higher authority', 'higher_approval'],
                ['execution', 'Awaiting / under execution', 'pending_execution'],
                ['closure', 'Completion review / closure', 'closure'],
                ['closed', 'Closed', 'closed'],
            ] as [$queue, $label, $key])
                <a href="{{ route('administration.requests.index', ['queue' => $queue]) }}"
                    @if (request('queue') === $queue) aria-current="page" @endif
                    class="rounded-xl border p-4 shadow-sm hover:border-indigo-400 focus:ring-2 focus:ring-indigo-500 {{ request('queue') === $queue ? 'border-indigo-400 bg-indigo-50' : 'border-slate-200 bg-white' }}">
                    <div class="text-xs font-semibold uppercase tracking-wide {{ $queue === 'overdue' ? 'text-red-700' : 'text-slate-500' }}">{{ $label }}</div>
                    <div class="mt-2 text-2xl font-bold text-slate-900">{{ number_format($summary[$key]) }}</div>
                </a>
            @endforeach
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <form method="GET" action="{{ route('administration.requests.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @if (request('queue'))<input type="hidden" name="queue" value="{{ request('queue') }}">@endif
                <div><label for="search" class="mb-1 block text-sm font-semibold">Search</label>
                    <input id="search" name="search" value="{{ request('search') }}" placeholder="Number, title or description" class="w-full rounded-lg border-slate-300"></div>
                @foreach (['request_type' => ['Type', $typeLabels], 'status' => ['Status', $statusLabels], 'priority' => ['Priority', ['low'=>'Low','normal'=>'Normal','high'=>'High','urgent'=>'Urgent']]] as $field => [$label, $options])
                    <div><label for="{{ $field }}" class="mb-1 block text-sm font-semibold">{{ $label }}</label>
                        <select id="{{ $field }}" name="{{ $field }}" class="w-full rounded-lg border-slate-300">
                            <option value="">All</option>
                            @foreach ($options as $value => $text)<option value="{{ $value }}" @selected(request($field) === $value)>{{ $text }}</option>@endforeach
                        </select></div>
                @endforeach
                <div class="flex items-center gap-3 sm:col-span-2 lg:col-span-4">
                    <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Apply filters</button>
                    <a href="{{ route('administration.requests.index') }}" class="text-sm font-semibold text-slate-600">Clear all</a>
                    @if (request('queue'))<span class="text-xs text-indigo-700">Queue: {{ ucwords(str_replace('_', ' ', request('queue'))) }}</span>@endif
                </div>
            </form>
        </div>
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" aria-label="Administrative request register">
            <div class="flex items-center justify-between gap-3 border-b px-4 py-4">
                <h3 class="font-semibold text-slate-900">Administrative Request Register</h3>
                <span class="text-sm text-slate-500">{{ number_format($requests->total()) }} results</span>
            </div>
            <div class="divide-y divide-slate-100">
            @forelse ($requests as $item)
                <article class="grid gap-4 p-4 hover:bg-slate-50 md:grid-cols-2 xl:grid-cols-3">
                    <div class="min-w-0">
                        <a href="{{ route('administration.requests.show', $item) }}" class="break-words font-semibold text-indigo-700 hover:underline">{{ $item->title }}</a>
                        <div class="mt-1 text-xs text-slate-500">{{ $item->request_no }} · {{ $typeLabels[$item->request_type] ?? $item->request_type }}</div>
                        <div class="mt-1 break-words text-xs text-slate-500">{{ $item->department?->name ?? 'Hospital-wide' }} · {{ $item->createdBy?->name ?? '—' }}</div>
                    </div>
                    <div class="min-w-0">
                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $statusClasses[$item->status] ?? 'bg-slate-100 text-slate-700' }}">{{ $statusLabels[$item->status] ?? $item->status }}</span>
                        <div class="mt-2 break-words text-sm text-slate-700">Pending with: <strong>{{ $item->pending_with }}</strong></div>
                        <div class="mt-1 text-xs text-slate-500">Created {{ $item->created_at?->format('d M Y') }} · {{ (int) $item->created_at?->diffInDays(now()) }} days ago</div>
                    </div>
                    <div class="min-w-0">
                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $priorityClasses[$item->priority] ?? 'bg-slate-100 text-slate-600' }}">{{ ucfirst($item->priority) }}</span>
                        @if ($item->is_overdue)<span class="ml-2 text-xs font-bold text-red-700">Overdue</span>@endif
                        <div class="mt-2 text-sm text-slate-700">Required by: {{ $item->required_by?->format('d M Y') ?? 'Not set' }}</div>
                        <div class="mt-1 text-xs text-slate-500">Estimated: {{ $item->estimated_amount !== null ? '₹'.number_format((float) $item->estimated_amount, 2) : '—' }} · MS-approved: {{ $item->approved_amount !== null ? '₹'.number_format((float) $item->approved_amount, 2) : '—' }}</div>
                    </div>
                </article>
            @empty
                <div class="px-6 py-12 text-center">
                    <p class="font-semibold text-slate-700">{{ $summary['total'] ? 'No requests match this queue and filters.' : 'No administrative requests yet.' }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ $summary['total'] ? 'Clear the filters or select another work queue.' : 'Create a request, add supporting documents, then submit it for verification.' }}</p>
                </div>
            @endforelse
            </div>
            <div class="border-t p-4">{{ $requests->links() }}</div>
        </section>
        <details class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600">
            <summary class="cursor-pointer font-semibold text-slate-800">Roles and authority</summary>
            <p class="mt-3">The Administrator verifies documentation, coordinates assignment and reviews closure. The Medical Superintendent records institutional decisions within delegated powers. Named higher authorities provide any additional approval required.</p>
            <p class="mt-2">Administrative approval does not itself authorize a payment or post an accounting entry.</p>
        </details>
    </div>
    </div>
</x-app-layout>
