<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Medicine Master
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Manage pharmacy medicines and selling prices
                </p>

            </div>


            <a
                href="{{ route('pharmacy.medicines.create') }}"
                class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
            >
                Add Medicine
            </a>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif


            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">


                <div class="border-b border-slate-100 px-6 py-5">

                    <form
                        method="GET"
                        action="{{ route('pharmacy.medicines.index') }}"
                        class="flex flex-col gap-3 sm:flex-row sm:items-end"
                    >

                        <div class="flex-1">

                            <label
                                for="search"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Search Medicines
                            </label>

                            <input
                                id="search"
                                name="search"
                                type="text"
                                value="{{ $search }}"
                                placeholder="Code, generic, brand, strength or manufacturer"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>


                        <div class="flex gap-2">

                            <button
                                type="submit"
                                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                            >
                                Search
                            </button>


                            @if ($search !== '')

                                <a
                                    href="{{ route('pharmacy.medicines.index') }}"
                                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    Clear
                                </a>

                            @endif

                        </div>

                    </form>

                </div>



                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Code
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Medicine
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Strength / Form
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Manufacturer
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Selling Price
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Status
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100 bg-white">

                            @forelse ($medicines as $medicine)

                                <tr class="transition hover:bg-slate-50">

                                    <td class="px-6 py-4 text-sm font-semibold text-slate-700">
                                        {{ $medicine->code }}
                                    </td>


                                    <td class="px-6 py-4">

                                        <div class="font-semibold text-slate-900">
                                            {{ $medicine->generic_name }}
                                        </div>

                                        @if ($medicine->brand_name)

                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ $medicine->brand_name }}
                                            </div>

                                        @endif

                                    </td>


                                    <td class="px-6 py-4 text-sm text-slate-600">

                                        <div>
                                            {{ $medicine->strength ?: '—' }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ $medicine->dosage_form ?: '—' }}
                                        </div>

                                    </td>


                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        {{ $medicine->manufacturer ?: '—' }}
                                    </td>


                                    <td class="px-6 py-4 text-right text-sm font-semibold text-slate-900">
                                        ₹{{ number_format((float) $medicine->default_selling_price, 2) }}
                                    </td>


                                    <td class="px-6 py-4">

                                        @if ($medicine->is_active)

                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">

                                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>

                                                Active

                                            </span>

                                        @else

                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">

                                                <span class="h-2 w-2 rounded-full bg-red-500"></span>

                                                Inactive

                                            </span>

                                        @endif

                                    </td>


                                    <td class="px-6 py-4">

                                        <div class="flex items-center justify-end gap-2">


                                            <a
                                                href="{{ route('pharmacy.medicines.edit', $medicine) }}"
                                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                            >
                                                Edit
                                            </a>


                                            <form
                                                method="POST"
                                                action="{{ route('pharmacy.medicines.status', $medicine) }}"
                                                onsubmit="return confirm('{{ $medicine->is_active ? 'Deactivate this medicine?' : 'Activate this medicine?' }}');"
                                            >

                                                @csrf
                                                @method('PATCH')


                                                @if ($medicine->is_active)

                                                    <button
                                                        type="submit"
                                                        class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100"
                                                    >
                                                        Deactivate
                                                    </button>

                                                @else

                                                    <button
                                                        type="submit"
                                                        class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                                    >
                                                        Activate
                                                    </button>

                                                @endif

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="7"
                                        class="px-6 py-12 text-center text-sm text-slate-500"
                                    >
                                        No medicines found.
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>



                @if ($medicines->hasPages())

                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $medicines->links() }}
                    </div>

                @endif

            </div>

        </div>

    </div>

</x-app-layout>