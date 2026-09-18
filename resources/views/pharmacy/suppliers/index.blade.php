<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Supplier Master
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Manage pharmacy suppliers and vendor details
                </p>
            </div>

            <a
                href="{{ route('pharmacy.suppliers.create') }}"
                class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
            >
                Add Supplier
            </a>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif


            <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <form
                    method="GET"
                    action="{{ route('pharmacy.suppliers.index') }}"
                    class="grid gap-4 md:grid-cols-4"
                >

                    <div class="md:col-span-2">

                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Search
                        </label>

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Code, supplier, phone, GSTIN, drug licence..."
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div>

                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Status
                        </label>

                        <select
                            name="status"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                            <option value="">
                                All
                            </option>

                            <option value="active" @selected(request('status') === 'active')>
                                Active
                            </option>

                            <option value="inactive" @selected(request('status') === 'inactive')>
                                Inactive
                            </option>

                        </select>

                    </div>


                    <div class="flex items-end gap-2">

                        <button
                            type="submit"
                            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Filter
                        </button>

                        <a
                            href="{{ route('pharmacy.suppliers.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Reset
                        </a>

                    </div>

                </form>

            </div>


            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Code
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Supplier
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Contact
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    GSTIN / Licence
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Credit
                                </th>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Status
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100 bg-white">

                            @forelse ($suppliers as $supplier)

                                <tr class="hover:bg-slate-50">

                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-bold text-slate-900">
                                        {{ $supplier->code }}
                                    </td>


                                    <td class="px-5 py-4">

                                        <div class="font-semibold text-slate-900">
                                            {{ $supplier->name }}
                                        </div>

                                        @if ($supplier->contact_person)
                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ $supplier->contact_person }}
                                            </div>
                                        @endif

                                        @if ($supplier->city || $supplier->state)
                                            <div class="mt-1 text-xs text-slate-400">
                                                {{ collect([$supplier->city, $supplier->state])->filter()->implode(', ') }}
                                            </div>
                                        @endif

                                    </td>


                                    <td class="px-5 py-4 text-sm text-slate-600">

                                        <div>
                                            {{ $supplier->phone ?: '—' }}
                                        </div>

                                        @if ($supplier->email)
                                            <div class="mt-1 text-xs text-slate-400">
                                                {{ $supplier->email }}
                                            </div>
                                        @endif

                                    </td>


                                    <td class="px-5 py-4 text-sm text-slate-600">

                                        <div>
                                            GSTIN:
                                            <span class="font-medium text-slate-800">
                                                {{ $supplier->gstin ?: '—' }}
                                            </span>
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">
                                            DL:
                                            {{ $supplier->drug_license_no ?: '—' }}
                                        </div>

                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-700">
                                        {{ number_format($supplier->credit_days) }} days
                                    </td>


                                    <td class="px-5 py-4">

                                        @if ($supplier->is_active)

                                            <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                Active
                                            </span>

                                        @else

                                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                                                Inactive
                                            </span>

                                        @endif

                                    </td>


                                    <td class="whitespace-nowrap px-5 py-4 text-right">

                                        <div class="flex justify-end gap-2">

                                            <a
                                                href="{{ route('pharmacy.suppliers.edit', $supplier) }}"
                                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                            >
                                                Edit
                                            </a>


                                            <form
                                                method="POST"
                                                action="{{ route('pharmacy.suppliers.status', $supplier) }}"
                                            >

                                                @csrf
                                                @method('PATCH')

                                                <button
                                                    type="submit"
                                                    class="rounded-lg border px-3 py-2 text-xs font-semibold
                                                        {{ $supplier->is_active
                                                            ? 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'
                                                            : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}"
                                                >
                                                    {{ $supplier->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>

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
                                        No suppliers found.
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>


                @if ($suppliers->hasPages())

                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $suppliers->links() }}
                    </div>

                @endif

            </div>

        </div>

    </div>

</x-app-layout>