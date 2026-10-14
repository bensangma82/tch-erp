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
                This page does not transmit any journal entries to Tally.
            </p>
        </div>

        {{-- JOURNAL TABLE --}}
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">

                    <thead class="bg-gray-50">
                        <tr>
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
                                    colspan="6"
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
