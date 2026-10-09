<x-app-layout>
<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">
            Department-wise OPD Consultation Revenue
        </h1>

        <p class="mt-1 text-sm text-gray-600">
            Consultation charges by clinical department.
            OPD registration fees are excluded.
        </p>
    </div>

    {{-- Date filters --}}
    <form method="GET"
          action="{{ route('finance.reports.opd-department') }}"
          class="mb-6 flex flex-wrap items-end gap-4 rounded-xl bg-white p-5 shadow">

        <div>
            <label class="block text-sm font-medium text-gray-700">
                From Date
            </label>
            <input type="date"
                   name="date_from"
                   value="{{ $dateFrom }}"
                   class="mt-1 rounded-md border-gray-300">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">
                To Date
            </label>
            <input type="date"
                   name="date_to"
                   value="{{ $dateTo }}"
                   class="mt-1 rounded-md border-gray-300">
        </div>

        <button type="submit"
                class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            Generate Report
        </button>

        <a href="{{ route('finance.reports.index') }}"
           class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
            Back to Finance Reports
        </a>
    </form>

    {{-- Summary --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach ([
            ['Consultation Billed', $totals['billed']],
            ['Consultation Collected', $totals['collected']],
            ['Consultation Outstanding', $totals['outstanding']],
        ] as [$label, $amount])
            <div class="rounded-xl bg-white p-5 shadow">
                <p class="text-sm text-gray-500">
                    {{ $label }}
                </p>
                <p class="mt-2 text-2xl font-bold text-gray-900">
                    ₹{{ number_format($amount, 2) }}
                </p>
            </div>
        @endforeach
    </div>

    {{-- Department report --}}
    <div class="overflow-hidden rounded-xl bg-white shadow">
        <div class="border-b border-gray-200 px-5 py-4">
            <h2 class="text-lg font-semibold text-gray-900">
                Department Revenue Breakdown
            </h2>
            <p class="mt-1 text-xs text-gray-500">
                Invoices dated {{ $dateFrom }} to {{ $dateTo }}.
                Payments counted through {{ $dateTo }}.
                Partial payments allocated proportionally.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                            Department
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">
                            Invoices
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">
                            Billed
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">
                            Collected
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">
                            Outstanding
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @foreach ($report as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                {{ $row['department'] }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">
                                {{ $row['invoice_count'] }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">
                                ₹{{ number_format($row['billed'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">
                                ₹{{ number_format($row['collected'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700">
                                ₹{{ number_format($row['outstanding'], 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot class="bg-gray-100 font-bold">
                    <tr>
                        <td class="px-4 py-3">Total</td>
                        <td class="px-4 py-3 text-right">
                            {{ $totals['invoice_count'] }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            ₹{{ number_format($totals['billed'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            ₹{{ number_format($totals['collected'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            ₹{{ number_format($totals['outstanding'], 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
</x-app-layout>
