<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- PAGE HEADER --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">
                Tally Journal Export
            </h1>

            <p class="mt-2 text-sm text-gray-600">
                Read-only overview of posted ERP journal entries
                and their Tally export status.
            </p>
        </div>

        {{-- TALLY COMPANY --}}
        <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
            <p class="text-sm text-blue-900">
                <strong>Configured Tally Company:</strong>
                {{ $tallyCompany ?: 'Not configured' }}
            </p>

            <p class="mt-2 text-xs text-blue-700">
                This page supports individual and bulk journal exports to the configured Tally company. Verify journal details before sending.
            </p>
        </div>

        {{-- JOURNAL FILTERS --}}
        <form id="tally-journal-filters"
              method="GET"
              action="{{ route('finance.tally.journals.index') }}"
              class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">

            <div class="grid gap-4 md:grid-cols-4">
                <div>
                    <label for="tally-status" class="block text-sm font-medium">Export Status</label>
                    <select id="tally-status" name="status"
                            class="mt-1 w-full rounded-lg border-gray-300">
                        @foreach ([
                            'not_exported' => 'Not Exported',
                            'in_progress' => 'In Progress',
                            'confirmed' => 'Confirmed',
                            'needs_attention' => 'Needs Attention',
                            'all' => 'All Journals'
                        ] as $value => $label)
                            <option value="{{ $value }}"
                                @selected($selectedStatus === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="tally-search" class="block text-sm font-medium">Journal Number</label>
                    <input id="tally-search" type="search" name="search"
                           value="{{ request('search') }}"
                           placeholder="Search journal..."
                           class="mt-1 w-full rounded-lg border-gray-300">
                </div>

                <div>
                    <label for="tally-from" class="block text-sm font-medium">From Date</label>
                    <input id="tally-from" type="date" name="date_from"
                           value="{{ request('date_from') }}"
                           class="mt-1 w-full rounded-lg border-gray-300">
                </div>

                <div>
                    <label for="tally-to" class="block text-sm font-medium">To Date</label>
                    <input id="tally-to" type="date" name="date_to"
                           value="{{ request('date_to') }}"
                           class="mt-1 w-full rounded-lg border-gray-300">
                </div>
            </div>

            <div class="mt-4 flex items-center gap-3">
                <button type="submit"
                        class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white">
                    Apply Filters
                </button>

                <a href="{{ route('finance.tally.journals.index') }}"
                   class="rounded-lg border px-5 py-2 text-sm font-semibold">
                    Reset
                </a>
            </div>
        </form>


        {{-- BULK EXPORT RESULTS --}}
        @if (session('tally_bulk_results'))
            <div class="mb-5 rounded-lg border bg-white p-4 shadow-sm">
                <h2 class="mb-3 font-semibold">Bulk Export Results</h2>
                <div class="space-y-2 text-sm">
                    @foreach (session('tally_bulk_results') as $result)
                        <div class="flex flex-wrap justify-between gap-2 border-b pb-2">
                            <span>{{ $result['journal'] }}</span>
                            <span>
                                <strong>{{ ucfirst(str_replace('_', ' ', $result['status'])) }}</strong>
                                — {{ $result['message'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- BULK EXPORT CONTROLS --}}
        <form
            id="tally-bulk-export-form"
            method="POST"
            action="{{ route('finance.tally.journals.send-selected') }}"
            class="mb-4 flex flex-wrap items-center gap-4 rounded-lg border bg-white p-4"
        >
            @csrf
            <button
                type="submit"
                id="tally-bulk-submit"
                disabled
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-40"
            >
                Send Selected to Tally
            </button>
            <span id="tally-bulk-count" class="text-sm text-gray-600">
                0 selected — maximum 20
            </span>
        </form>

        {{-- JOURNAL TABLE --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">

                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">
                                <input
                                    type="checkbox"
                                    id="tally-select-all"
                                    aria-label="Select all eligible journals on this page"
                                >
                            </th>

                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                Journal
                            </th>

                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                Date
                            </th>

                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                Narration
                            </th>

                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                Tally Status
                            </th>

                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                Tally Voucher ID
                            </th>

                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">

                        @forelse ($journals as $journal)

                            @php
                                /*
                                |--------------------------------------------------------------------------
                                | Export Status
                                |--------------------------------------------------------------------------
                                */

                                $export = $exports->get($journal->id);

                                $status = $export
                                    ? $export->status
                                    : 'not_exported';

                                $statusLabels = [
                                    'not_exported' => 'Not Exported',
                                    'sending' => 'Sending',
                                    'pending' => 'Pending',
                                    'exported' => 'Exported',
                                    'confirmed' => 'Confirmed',
                                    'failed' => 'Failed - Review Required',
                                    'unknown' => 'Unknown - Reconcile',
                                ];

                                $statusLabel = $statusLabels[$status]
                                    ?? ucfirst(str_replace('_', ' ', $status));

                                $statusClasses = match ($status) {
                                    'confirmed' =>
                                        'bg-green-100 text-green-800',

                                    'exported' =>
                                        'bg-blue-100 text-blue-800',

                                    'sending', 'pending' =>
                                        'bg-yellow-100 text-yellow-800',

                                    'failed', 'unknown' =>
                                        'bg-red-100 text-red-800',

                                    default =>
                                        'bg-gray-100 text-gray-700',
                                };

                                /*
                                |--------------------------------------------------------------------------
                                | Tally Voucher Number
                                |--------------------------------------------------------------------------
                                | Prefer the recorded Tally voucher ID.
                                | For historical manually imported EDU vouchers,
                                | look up the number using the stored Tally GUID.
                                |--------------------------------------------------------------------------
                                */

                                $tallyVoucherNumber = $export?->tally_voucher_id;

                                if (
                                    blank($tallyVoucherNumber)
                                    && filled($export?->remote_id)
                                ) {
                                    $tallyVoucherNumber =
                                        \App\Models\TallyImportedVoucher::query()
                                            ->where(
                                                'tally_company',
                                                $tallyCompany
                                            )
                                            ->where(function ($query) use ($export) {
                                                $query
                                                    ->where(
                                                        'guid',
                                                        $export->remote_id
                                                    )
                                                    ->orWhere(
                                                        'remote_id',
                                                        $export->remote_id
                                                    );
                                            })
                                            ->value('voucher_number');
                                }
                            @endphp

                            <tr class="transition hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    @if (! $export)
                                        <input
                                            type="checkbox"
                                            class="tally-bulk-checkbox"
                                            name="journal_ids[]"
                                            value="{{ $journal->id }}"
                                            form="tally-bulk-export-form"
                                            aria-label="Select {{ $journal->journal_no }}"
                                        >
                                    @endif
                                </td>


                                {{-- JOURNAL NUMBER --}}
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-900">
                                    {{ $journal->journal_no }}
                                </td>

                                {{-- JOURNAL DATE --}}
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                                    {{ $journal->journal_date?->format('d M Y') }}
                                </td>

                                {{-- NARRATION --}}
                                <td class="max-w-xs px-4 py-3 text-gray-600">
                                    {{ $journal->narration ?: '—' }}
                                </td>

                                {{-- TALLY STATUS --}}
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span
                                        class="inline-flex rounded-md px-2 py-1 text-xs font-semibold {{ $statusClasses }}"
                                    >
                                        {{ $statusLabel }}
                                    </span>
                                </td>

                                {{-- TALLY VOUCHER NUMBER --}}
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                                    {{ $tallyVoucherNumber ?: '—' }}
                                </td>

                                {{-- ACTION --}}
                                <td class="whitespace-nowrap px-4 py-3">
                                    <a
                                        href="{{ route('finance.tally.journals.view', $journal->id) }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center rounded-md border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100"
                                    >
                                        Preview
                                    </a>



{{-- Audited Tally recovery --}}
@if ($journal->tallyExport && $journal->tallyExport->status === 'unknown')
    <a
        href="{{ route('finance.tally.journals.recovery.show', $journal->tallyExport->id) }}"
        class="ml-2 inline-flex items-center rounded-md border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100"
    >
        Resolve / Resend
    </a>
@endif

{{-- Controlled manual journal export --}}
      @if (! $journal->tallyExport)

    <form
        method="POST"
        action="{{ route('finance.tally.journals.send', $journal->id) }}"
        class="inline-flex flex-col gap-2"
        onsubmit="return confirm('Send this journal to TallyPrime Educational Edition? Verify the company, journal and accounting date before continuing.');"
    >
        @csrf

        <input
            type="date"
            name="tally_accounting_date"
            value="{{ $journal->journal_date->format('Y-m-d') }}"
            required
            class="rounded-md border border-gray-300 px-2 py-1 text-xs"
        >

       <input
    type="text"
    name="date_override_reason"
    placeholder="Reason if accounting date is changed"
    maxlength="500"
    class="rounded-md border border-gray-300 px-2 py-1 text-xs"
>
       <button
    type="submit"
    class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700"
>
    Send to Tally
</button>
    </form>
@endif


                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="7"
                                    class="px-4 py-10 text-center text-gray-500"
                                >
                                    No posted journal entries found.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>
                </table>
            </div>
        </div>


        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const boxes = Array.from(
                    document.querySelectorAll('.tally-bulk-checkbox')
                );
                const all = document.getElementById('tally-select-all');
                const form = document.getElementById('tally-bulk-export-form');
                const button = document.getElementById('tally-bulk-submit');
                const count = document.getElementById('tally-bulk-count');

                function update() {
                    const n = boxes.filter(box => box.checked).length;
                    count.textContent = n + ' selected — maximum 20';
                    button.disabled = n === 0 || n > 20;
                    all.checked = boxes.length > 0 && n === boxes.length;
                    all.indeterminate = n > 0 && n < boxes.length;
                }

                all.addEventListener('change', function () {
                    boxes.forEach((box, index) => {
                        box.checked = all.checked && index < 20;
                    });
                    update();
                });

                boxes.forEach(box => {
                    box.addEventListener('change', update);
                });

                form.addEventListener('submit', function (event) {
                    const n = boxes.filter(box => box.checked).length;

                    if (
                        n === 0 || n > 20 ||
                        !confirm(
                            'Send ' + n + ' selected journals to the configured Tally company? ' +
                            'Each journal will be transmitted once. ' +
                            'Uncertain outcomes require manual reconciliation.'
                        )
                    ) {
                        event.preventDefault();
                        return;
                    }

                    button.disabled = true;
                });

                update();
            });
        </script>

        {{-- PAGINATION --}}
        <div class="mt-5">
            {{ $journals->links() }}
        </div>

        {{-- ACCOUNTING SAFETY NOTICE --}}
        <div class="mt-5 rounded-lg border border-gray-200 bg-gray-50 p-4">
            <p class="text-xs leading-5 text-gray-600">
                <strong>Accounting Safety:</strong>
                An existing Tally export record prevents a journal
                from being treated as newly exportable.

                Failed, pending or uncertain exports must be
                reviewed and reconciled before any retransmission.

                Journal preview is read-only and does not transmit
                accounting data to TallyPrime.
            </p>
        </div>

    </div>
</x-app-layout>
