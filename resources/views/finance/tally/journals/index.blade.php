<x-app-layout>
<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">
            Tally Journal Export
        </h1>

        <p class="mt-2 text-sm text-gray-600">
            Read-only overview of posted ERP journal entries
            and their Tally export status.
        </p>
    </div>

    <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
        <p class="text-sm text-blue-900">
            <strong>Configured Tally Company:</strong>
            {{ $tallyCompany ?: 'Not configured' }}
        </p>

        <p class="mt-2 text-xs text-blue-700">
            This page does not transmit any journal entries to Tally.
        </p>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">

                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold">
                            Journal
                        </th>

                        <th class="px-4 py-3 text-left font-semibold">
                            Date
                        </th>

                        <th class="px-4 py-3 text-left font-semibold">
                            Narration
                        </th>

                        <th class="px-4 py-3 text-left font-semibold">
                            Tally Status
                        </th>

                        <th class="px-4 py-3 text-left font-semibold">
                            Tally Voucher ID
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">

                    @forelse ($journals as $journal)

                        @php
                            $export = $exports->get($journal->id);

                            $status = $export
                                ? $export->status
                                : 'not_exported';

                            $statusLabels = [
                                'not_exported' => 'Not Exported',
                                'sending' => 'Sending',
                                'exported' => 'Exported',
                                'confirmed' => 'Confirmed',
                                'failed' => 'Failed - Review Required',
                                'unknown' => 'Unknown - Reconcile',
                            ];

                            $statusLabel = $statusLabels[$status]
                                ?? ucfirst(str_replace('_', ' ', $status));
                        @endphp

                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $journal->journal_no }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                                {{ $journal->journal_date?->format('d M Y') }}
                            </td>

                            <td class="max-w-xs px-4 py-3 text-gray-600">
                                {{ $journal->narration ?: '—' }}
                            </td>

                            <td class="px-4 py-3">
                                <span class="rounded-md bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">
                                    {{ $statusLabel }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ $export?->tally_voucher_id ?: '—' }}
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="5"
                                class="px-4 py-10 text-center text-gray-500">
                                No posted journal entries found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>
            </table>
        </div>

    </div>

    <div class="mt-5">
        {{ $journals->links() }}
    </div>

    <p class="mt-4 text-xs text-gray-500">
        An existing export record prevents a journal from being
        treated as newly exportable. Failed or uncertain records
        must be reconciled before any further action.
    </p>

</div>
</x-app-layout>
