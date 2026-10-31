<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            MHIS Package Master — IPD
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="bg-white shadow rounded-lg p-5 mb-5">
                <form method="GET" action="{{ url()->current() }}"
                      class="flex flex-wrap gap-3 items-end">

                    <div class="flex-1 min-w-64">
                        <label class="block text-sm font-medium mb-1">
                            Search procedures
                        </label>
                        <input type="text" name="search"
                               value="{{ $search }}"
                               placeholder="Code, package or procedure"
                               class="w-full rounded-md border-gray-300">
                    </div>

                    <div class="min-w-64">
                        <label class="block text-sm font-medium mb-1">
                            Specialty
                        </label>
                        <select name="specialty"
                                class="w-full rounded-md border-gray-300">
                            <option value="">All specialties</option>
                            @foreach($specialties as $item)
                                <option value="{{ $item }}"
                                    @selected($specialty === $item)>
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit"
                            class="px-5 py-2 bg-blue-700 text-white rounded-md">
                        Search
                    </button>

                    <a href="{{ url()->current() }}"
                       class="px-4 py-2 bg-gray-200 rounded-md">
                        Reset
                    </a>
                </form>
            </div>

            <div class="bg-white shadow rounded-lg overflow-hidden">
                <div class="p-4 border-b flex justify-between">
                    <h3 class="font-semibold">IPD Procedures</h3>
                    <span class="text-sm text-gray-600">
                        {{ $procedures->total() }} records found
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-3 text-left">Code</th>
                                <th class="p-3 text-left">Specialty</th>
                                <th class="p-3 text-left">Package</th>
                                <th class="p-3 text-left">Procedure</th>
                                <th class="p-3 text-right">Base tariff</th>
                                <th class="p-3 text-center">Details</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse($procedures as $procedure)
                                <tr class="hover:bg-gray-50">
                                    <td class="p-3 font-semibold">
                                        {{ $procedure->procedure_code }}
                                    </td>
                                    <td class="p-3">
                                        {{ $procedure->specialty }}
                                    </td>
                                    <td class="p-3">
                                        {{ $procedure->package_name }}
                                    </td>
                                    <td class="p-3">
                                        {{ $procedure->procedure_name }}
                                    </td>
                                    <td class="p-3 text-right whitespace-nowrap">
                                        @if($procedure->procedure_price !== null)
                                            ₹{{ number_format((float) $procedure->procedure_price, 2) }}
                                        @else
                                            {{ $procedure->source_price_text ?: 'Not specified' }}
                                        @endif
                                    </td>
                                    <td class="p-3 text-center">
                                        <a href="{{ route('mhis.procedures.show', $procedure->id) }}"
                                           class="text-blue-700 font-semibold hover:underline">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="p-6 text-center text-gray-500">
                                        No matching procedures found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t">
                    {{ $procedures->links() }}
                </div>
            </div>

            <p class="mt-4 text-sm text-gray-500">
                Reference tariff only. Actual MHIS eligibility,
                stratification and approved amounts require verification.
            </p>

        </div>
    </div>
</x-app-layout>
