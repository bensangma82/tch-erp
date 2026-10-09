<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Tally Integration
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    Map TCH ERP Finance Heads and Accounts to existing Tally ledgers.
                </p>
            </div>

           <div class="flex flex-wrap items-center gap-2">
    <a
        href="{{ route('finance.tally.trial-balance') }}"
        class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700"
    >
        Trial Balance
    </a>

    <a
        href="{{ route('finance.tally.income-expenditure') }}"
        class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700"
    >
        Income &amp; Expenditure
    </a>

    <a
        href="{{ route('finance.tally.balance-sheet') }}"
        class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700"
    >
        Balance Sheet
    </a>

    <a
        href="{{ route('finance.tally.cash-flow') }}"
        class="rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700"
    >
        Cash Flow
    </a>

    <a
        href="{{ route('finance.tally.reconciliation') }}"
        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
    >
        Reconciliation
    </a>
</div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-5 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
        {{ session('error') }}
    </div>
@endif

            @if ($errors->any())
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <div class="font-semibold">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif


{{-- Tally Voucher Synchronization --}}
<div class="mb-6 rounded-xl bg-white p-6 shadow">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">
                Tally Voucher Synchronization
            </h3>

            <p class="mt-1 text-sm text-gray-600">
                Import accounting vouchers from Tally into ERP
                for reconciliation. Existing ERP finance vouchers
                will not be modified.
            </p>

            <p class="mt-2 text-xs text-green-700">
                Synchronization is available for controlled testing
                with the configured Tally company.
            </p>
        </div>

        <form
            method="POST"
            action="{{ route('finance.tally.import.sync') }}"
        >
            @csrf

            <button
                type="submit"
                class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700"
            >
                Sync Vouchers from Tally
            </button>
        </form>
    </div>
</div>

{{-- Tally Ledger Master Synchronization --}}
<div class="mb-6 rounded-xl bg-white p-6 shadow">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">
                Tally Ledger Master Synchronization
            </h3>

            <p class="mt-1 text-sm text-gray-600">
                Retrieve ledger names, groups and balances from the
                configured TallyPrime company.
            </p>

            <p class="mt-2 text-xs text-gray-500">
                Existing ERP ledger mappings will not be modified.
            </p>
        </div>

        <form
            method="POST"
            action="{{ route('finance.tally.ledgers.sync') }}"
        >
            @csrf

            <button
                type="submit"
                onclick="return confirm('Synchronize the Tally Ledger Master into ERP?')"
                class="rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-800"
            >
                Sync Ledger Master
            </button>
        </form>
    </div>
</div>




{{-- Tally Ledger Master Directory --}}
<div class="mb-6 overflow-hidden rounded-xl bg-white shadow">
    <div class="border-b border-gray-200 px-6 py-4">
        <h3 class="text-lg font-semibold text-gray-800">
            Tally Ledger Master Directory
        </h3>

        <p class="mt-1 text-sm text-gray-500">
            Company: {{ $tallyCompany }}
        </p>

        <p class="mt-1 text-sm text-gray-500">
            {{ $ledgerMasters->count() }} synchronized ledgers.
            Assign an ERP department to each relevant income ledger.
        </p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                        Tally Ledger
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                        Tally Group
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                        ERP Mapping
                    </th>

                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-600">
                        Mapping Status
                    </th>

                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                        ERP Department
                    </th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($ledgerMasters as $ledger)
                    @php
                        $mappings = $activeMappings->get(
                            $ledger->ledger_name,
                            collect()
                        );
                    @endphp

                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">
                            {{ $ledger->ledger_name }}
                        </td>

                        <td class="px-4 py-3 text-sm text-gray-600">
                            {{ $ledger->parent_group ?: '—' }}
                        </td>

                        <td class="px-4 py-3 text-sm text-gray-700">
                            @forelse ($mappings as $mapping)
                                <div>
                                    {{ $mapping->financeHead?->name
                                        ?? $mapping->financeAccount?->name
                                        ?? 'Unassigned' }}
                                </div>
                            @empty
                                —
                            @endforelse
                        </td>

                        <td class="px-4 py-3 text-center">
                            @if ($mappings->isNotEmpty())
                                <span class="inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">
                                    Mapped
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                    Unmapped
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <form
                                method="POST"
                                action="{{ route('finance.tally.ledger-masters.department.update', $ledger) }}"
                                class="flex items-center gap-2"
                            >
                                @csrf
                                @method('PUT')

                                <select
                                    name="department_id"
                                    class="min-w-[170px] rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Not assigned</option>

                                    @foreach ($departments as $department)
                                        <option
                                            value="{{ $department->id }}"
                                            @selected($ledger->department_id == $department->id)
                                        >
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>

                                <button
                                    type="submit"
                                    class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700"
                                >
                                    Save
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                            No ledger masters synchronized yet.
                            Click Sync Ledger Master above.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

               {{-- Finance Heads --}}
            <div class="overflow-hidden rounded-xl bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Finance Head → Tally Ledger
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        Income and expense classifications used by the ERP.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                                    ERP Code
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                                    ERP Finance Head
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                                    Type
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                                    Tally Ledger
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                                    Tally Group
                                </th>

                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-600">
                                    Active
                                </th>

                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($financeHeads as $head)
                                @php
                                    $mapping = $head->tallyMapping;
                                    $formId = 'tally-head-' . $head->id;
                                @endphp

                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-700">
                                        {{ $head->code }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $head->name }}
                                        </div>

                                        @if ($head->category)
                                            <div class="text-xs text-gray-500">
                                                {{ $head->category }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
                                        {{ ucfirst($head->head_type) }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <input
                                            form="{{ $formId }}"
                                            type="text"
                                            name="tally_ledger_name"
                                            value="{{ $mapping?->tally_ledger_name }}"
                                            placeholder="Exact Tally ledger name"
                                            class="w-56 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            required
                                        >
                                    </td>

                                    <td class="px-4 py-3">
                                        <input
                                            form="{{ $formId }}"
                                            type="text"
                                            name="tally_group_name"
                                            value="{{ $mapping?->tally_group_name }}"
                                            placeholder="Tally group"
                                            class="w-48 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <input
                                            form="{{ $formId }}"
                                            type="checkbox"
                                            name="is_active"
                                            value="1"
                                            @checked($mapping ? $mapping->is_active : true)
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        >
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <form
                                            id="{{ $formId }}"
                                            method="POST"
                                            action="{{ route('finance.tally.heads.update', $head) }}"
                                        >
                                            @csrf
                                            @method('PUT')

                                            <button
                                                type="submit"
                                                class="rounded-md bg-gray-800 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-700"
                                            >
                                                Save
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Finance Accounts --}}
            <div class="mt-6 overflow-hidden rounded-xl bg-white shadow">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Finance Account → Tally Ledger
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        Cash and bank accounts used by the ERP.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                                    ERP Code
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                                    ERP Account
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                                    Type
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                                    Tally Ledger
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">
                                    Tally Group
                                </th>

                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-600">
                                    Active
                                </th>

                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($financeAccounts as $account)
                                @php
                                    $mapping = $account->tallyMapping;
                                    $formId = 'tally-account-' . $account->id;
                                @endphp

                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-700">
                                        {{ $account->code }}
                                    </td>

                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $account->name }}
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600">
                                        {{ ucfirst($account->account_type) }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <input
                                            form="{{ $formId }}"
                                            type="text"
                                            name="tally_ledger_name"
                                            value="{{ $mapping?->tally_ledger_name }}"
                                            placeholder="Exact Tally ledger name"
                                            class="w-56 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            required
                                        >
                                    </td>

                                    <td class="px-4 py-3">
                                        <input
                                            form="{{ $formId }}"
                                            type="text"
                                            name="tally_group_name"
                                            value="{{ $mapping?->tally_group_name }}"
                                            placeholder="Tally group"
                                            class="w-48 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <input
                                            form="{{ $formId }}"
                                            type="checkbox"
                                            name="is_active"
                                            value="1"
                                            @checked($mapping ? $mapping->is_active : true)
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        >
                                    </td>

                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <form
                                            id="{{ $formId }}"
                                            method="POST"
                                            action="{{ route('finance.tally.accounts.update', $account) }}"
                                        >
                                            @csrf
                                            @method('PUT')

                                            <button
                                                type="submit"
                                                class="rounded-md bg-gray-800 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-700"
                                            >
                                                Save
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>