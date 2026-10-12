
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    General Ledger Account Master
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Manage the hospital Chart of Accounts and Tally ledger mappings.
                </p>
            </div>

            <a href="{{ route('finance.gl-accounts.create') }}"
               class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                + Add GL Account
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Filters --}}
            <div class="rounded-lg bg-white p-5 shadow">
                <form method="GET"
                      action="{{ route('finance.gl-accounts.index') }}"
                      class="grid grid-cols-1 gap-4 md:grid-cols-4">

                    <div class="md:col-span-2">
                        <label for="search"
                               class="block text-sm font-medium text-gray-700">
                            Search Accounts
                        </label>
                        <input
                            id="search"
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Account code or account name"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    </div>

                    <div>
                        <label for="account_type"
                               class="block text-sm font-medium text-gray-700">
                            Account Type
                        </label>
                        <select
                            id="account_type"
                            name="account_type"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All Types</option>

                            @foreach (['asset', 'liability', 'equity', 'income', 'expense'] as $type)
                                <option value="{{ $type }}"
                                    @selected(request('account_type') === $type)>
                                    {{ ucfirst($type) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button
                            type="submit"
                            class="rounded-md bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
                        >
                            Filter
                        </button>

                        <a href="{{ route('finance.gl-accounts.index') }}"
                           class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Clear
                        </a>
                    </div>
                </form>
            </div>

            {{-- Chart of Accounts --}}
            <div class="overflow-hidden rounded-lg bg-white shadow">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                    Code / Account
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                    Classification
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                    Normal Balance
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                    Tally Mapping
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                    Status
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($accounts as $account)
                                <tr class="align-top hover:bg-slate-50">
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-gray-900">
                                            {{ $account->account_code }}
                                        </div>
                                        <div class="mt-1 text-sm text-gray-700">
                                            {{ $account->account_name }}
                                        </div>

                                        @if ($account->parent)
                                            <div class="mt-1 text-xs text-gray-500">
                                                Parent:
                                                {{ $account->parent->account_code }}
                                                —
                                                {{ $account->parent->account_name }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4 text-sm">
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                            {{ ucfirst($account->account_type) }}
                                        </span>

                                        @if ($account->account_group)
                                            <div class="mt-2 text-xs text-gray-500">
                                                {{ $account->account_group }}
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4 text-sm text-gray-700">
                                        {{ ucfirst($account->normal_balance) }}
                                    </td>

                                    <td class="px-4 py-4 text-sm">
                                        @if ($account->tally_ledger_name)
                                            <div class="font-medium text-gray-800">
                                                {{ $account->tally_ledger_name }}
                                            </div>

                                            @if ($account->tally_group_name)
                                                <div class="mt-1 text-xs text-gray-500">
                                                    {{ $account->tally_group_name }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-xs text-amber-700">
                                                Not mapped
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4">
                                        <div class="flex flex-col items-start gap-2">
                                            @if ($account->is_active)
                                                <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">
                                                    Active
                                                </span>
                                            @else
                                                <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">
                                                    Inactive
                                                </span>
                                            @endif

                                            @if ($account->allow_posting)
                                                <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800">
                                                    Posting Enabled
                                                </span>
                                            @else
                                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">
                                                    Posting Disabled
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 text-right">
                                        <a
                                            href="{{ route('finance.gl-accounts.edit', $account) }}"
                                            class="inline-flex rounded-md border border-indigo-200 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50"
                                        >
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"
                                        class="px-6 py-12 text-center text-sm text-gray-500">
                                        No General Ledger accounts found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($accounts->hasPages())
                    <div class="border-t border-gray-200 px-4 py-4">
                        {{ $accounts->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
