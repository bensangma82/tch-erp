
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Journal Entry Details
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $journal->journal_no }}
                </p>
            </div>

            <a
                href="{{ route('finance.journals.index') }}"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Back to Journal Entries
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Journal Header --}}
            <div class="rounded-lg bg-white p-6 shadow">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Journal Number
                        </p>
                        <p class="mt-1 text-lg font-bold text-gray-900">
                            {{ $journal->journal_no }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Accounting Date
                        </p>
                        <p class="mt-1 text-sm font-semibold text-gray-800">
                            {{ $journal->journal_date?->format('d M Y') ?? '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Status
                        </p>

                        <div class="mt-2">
                            @if ($journal->status === 'posted')
                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800">
                                    Posted
                                </span>
                            @elseif ($journal->status === 'draft')
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                                    Draft
                                </span>
                            @else
                                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700">
                                    {{ ucfirst($journal->status) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-5 border-t border-gray-100 pt-5 md:grid-cols-3">
                    <div>
                        <p class="text-xs font-semibold uppercase text-gray-500">
                            Source Type
                        </p>
                        <p class="mt-1 text-sm text-gray-800">
                            {{ $journal->source_type ?: '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase text-gray-500">
                            Source ID / Event
                        </p>
                        <p class="mt-1 text-sm text-gray-800">
                            {{ $journal->source_id ?: '—' }}
                            @if ($journal->source_event)
                                / {{ $journal->source_event }}
                            @endif
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase text-gray-500">
                            Reference Number
                        </p>
                        <p class="mt-1 text-sm text-gray-800">
                            {{ $journal->reference_no ?: '—' }}
                        </p>
                    </div>
                </div>

                <div class="mt-5">
                    <p class="text-xs font-semibold uppercase text-gray-500">
                        Narration
                    </p>
                    <p class="mt-1 whitespace-pre-line text-sm text-gray-800">
                        {{ $journal->narration ?: 'No narration recorded.' }}
                    </p>
                </div>
            </div>

            {{-- Journal Lines --}}
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="text-base font-semibold text-gray-900">
                        Debit and Credit Entries
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                    Line
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                    GL Account
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                    Department / Description
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">
                                    Debit (₹)
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">
                                    Credit (₹)
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($journal->lines as $line)
                                <tr class="align-top">
                                    <td class="px-4 py-4 text-sm text-gray-600">
                                        {{ $line->line_no }}
                                    </td>

                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-gray-900">
                                            {{ $line->generalLedgerAccount?->account_code ?? '—' }}
                                        </div>
                                        <div class="mt-1 text-sm text-gray-700">
                                            {{ $line->generalLedgerAccount?->account_name ?? 'Account unavailable' }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-4">
                                        @if ($line->department)
                                            <div class="text-sm font-medium text-gray-700">
                                                {{ $line->department->name }}
                                            </div>
                                        @endif

                                        @if ($line->description)
                                            <div class="mt-1 text-sm text-gray-600">
                                                {{ $line->description }}
                                            </div>
                                        @endif

                                        @if ($line->reference_no)
                                            <div class="mt-1 text-xs text-gray-500">
                                                Ref: {{ $line->reference_no }}
                                            </div>
                                        @endif

                                        @if (! $line->department && ! $line->description && ! $line->reference_no)
                                            <span class="text-sm text-gray-400">—</span>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-medium text-gray-800">
                                        {{ (float) $line->debit > 0
                                            ? number_format((float) $line->debit, 2)
                                            : '—' }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-4 text-right text-sm font-medium text-gray-800">
                                        {{ (float) $line->credit > 0
                                            ? number_format((float) $line->credit, 2)
                                            : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">
                                        No journal lines recorded.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        <tfoot class="bg-slate-50">
                            <tr>
                                <th colspan="3" class="px-4 py-4 text-right text-sm font-semibold text-gray-800">
                                    Total
                                </th>
                                <th class="px-4 py-4 text-right text-sm font-bold text-gray-900">
                                    {{ number_format((float) $totalDebit, 2) }}
                                </th>
                                <th class="px-4 py-4 text-right text-sm font-bold text-gray-900">
                                    {{ number_format((float) $totalCredit, 2) }}
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if (abs((float) $totalDebit - (float) $totalCredit) >= 0.005)
                    <div class="border-t border-red-200 bg-red-50 px-6 py-3 text-sm font-semibold text-red-800">
                        Warning: This journal is not balanced.
                    </div>
                @endif
            </div>

            {{-- Posting and Tally Tracking --}}
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="text-base font-semibold text-gray-900">
                        Posting Information
                    </h3>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Created By</dt>
                            <dd class="text-right font-medium text-gray-800">
                                {{ $journal->createdBy?->name ?? '—' }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Posted By</dt>
                            <dd class="text-right font-medium text-gray-800">
                                {{ $journal->postedBy?->name ?? '—' }}
                            </dd>
                        </div>

                        <div class="flex justify-between gap-4">
                            <dt class="text-gray-500">Posted At</dt>
                            <dd class="text-right font-medium text-gray-800">
                                {{ $journal->posted_at?->format('d M Y, h:i A') ?? '—' }}
                            </dd>
                        </div>

                        @if ($journal->reversalOf)
                            <div class="flex justify-between gap-4">
                                <dt class="text-gray-500">Reversal Of</dt>
                                <dd>
                                    <a href="{{ route('finance.journals.show', $journal->reversalOf) }}"
                                       class="font-semibold text-indigo-700 hover:underline">
                                        {{ $journal->reversalOf->journal_no }}
                                    </a>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="text-base font-semibold text-gray-900">
                        Tally Export Status
                    </h3>

                    @if ($export)
                        <div class="mt-4">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold
                                {{ $export->status === 'confirmed'
                                    ? 'bg-green-100 text-green-800'
                                    : ($export->status === 'failed'
                                        ? 'bg-red-100 text-red-800'
                                        : 'bg-amber-100 text-amber-800') }}">
                                {{ ucfirst($export->status) }}
                            </span>
                        </div>

                        <dl class="mt-5 space-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500">Export Reference</dt>
                                <dd class="mt-1 break-all font-medium text-gray-800">
                                    {{ $export->export_reference ?: '—' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">Tally GUID</dt>
                                <dd class="mt-1 break-all font-medium text-gray-800">
                                    {{ $export->remote_id ?: '—' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-gray-500">Confirmed At</dt>
                                <dd class="mt-1 font-medium text-gray-800">
                                    {{ $export->confirmed_at?->format('d M Y, h:i A') ?? '—' }}
                                </dd>
                            </div>

                            @if ($export->remarks)
                                <div>
                                    <dt class="text-gray-500">Remarks</dt>
                                    <dd class="mt-1 text-gray-800">
                                        {{ $export->remarks }}
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    @else
                        <p class="mt-4 text-sm text-gray-600">
                            This journal has no Tally export tracking record.
                        </p>
                    @endif
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-xs text-gray-600">
                This page is read-only. Posted journals must be corrected through
                authorized reversing entries rather than direct modification.
            </div>

        </div>
    </div>
</x-app-layout>
