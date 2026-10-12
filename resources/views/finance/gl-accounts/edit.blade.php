
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Edit General Ledger Account
                </h2>

                <p class="mt-1 text-sm text-gray-500">
                    {{ $account->account_code }} —
                    {{ $account->account_name }}
                </p>
            </div>

            <a
                href="{{ route('finance.gl-accounts.index') }}"
                class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Back to Chart of Accounts
            </a>
        </div>
    </x-slot>

    @php
        $hasJournals = $account->journalLines()->exists();
    @endphp

    <div class="py-6">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                    <p class="text-sm font-semibold text-red-800">
                        Please correct the following errors:
                    </p>

                    <ul class="mt-2 list-disc pl-5 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($hasJournals)
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <p class="text-sm font-semibold text-amber-900">
                        This account has existing journal entries.
                    </p>
                    <p class="mt-1 text-sm text-amber-800">
                        Its accounting code, type, normal balance, parent account
                        and Finance Master links cannot be changed.
                        Tally mappings and operational status can still be edited,
                        subject to accounting controls.
                    </p>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('finance.gl-accounts.update', $account) }}"
                class="space-y-6"
            >
                @csrf
                @method('PUT')

                {{-- Account Information --}}
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-5 text-base font-semibold text-gray-900">
                        Account Information
                    </h3>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                        <div>
                            <label for="account_code" class="block text-sm font-medium text-gray-700">
                                Account Code *
                            </label>
                            <input
                                id="account_code"
                                name="account_code"
                                type="text"
                                maxlength="30"
                                required
                                value="{{ old('account_code', $account->account_code) }}"
                                @readonly($hasJournals)
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm {{ $hasJournals ? 'bg-gray-100' : '' }}"
                            >
                        </div>

                        <div>
                            <label for="account_name" class="block text-sm font-medium text-gray-700">
                                Account Name *
                            </label>
                            <input
                                id="account_name"
                                name="account_name"
                                type="text"
                                maxlength="150"
                                required
                                value="{{ old('account_name', $account->account_name) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                        </div>

                        <div>
                            <label for="account_type" class="block text-sm font-medium text-gray-700">
                                Account Type *
                            </label>
                            <select
                                id="account_type"
                                name="account_type"
                                required
                                @disabled($hasJournals)
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm {{ $hasJournals ? 'bg-gray-100' : '' }}"
                            >
                                @foreach ([
                                    'asset' => 'Asset',
                                    'liability' => 'Liability',
                                    'equity' => 'Equity',
                                    'income' => 'Income',
                                    'expense' => 'Expense',
                                ] as $value => $label)
                                    <option value="{{ $value }}"
                                        @selected(old('account_type', $account->account_type) === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>

                            @if ($hasJournals)
                                <input type="hidden" name="account_type" value="{{ $account->account_type }}">
                            @endif
                        </div>

                        <div>
                            <label for="normal_balance" class="block text-sm font-medium text-gray-700">
                                Normal Balance *
                            </label>
                            <select
                                id="normal_balance"
                                name="normal_balance"
                                required
                                @disabled($hasJournals)
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm {{ $hasJournals ? 'bg-gray-100' : '' }}"
                            >
                                <option value="debit"
                                    @selected(old('normal_balance', $account->normal_balance) === 'debit')>
                                    Debit
                                </option>
                                <option value="credit"
                                    @selected(old('normal_balance', $account->normal_balance) === 'credit')>
                                    Credit
                                </option>
                            </select>

                            @if ($hasJournals)
                                <input type="hidden" name="normal_balance" value="{{ $account->normal_balance }}">
                            @endif
                        </div>

                        <div>
                            <label for="account_group" class="block text-sm font-medium text-gray-700">
                                Account Group
                            </label>
                            <input
                                id="account_group"
                                name="account_group"
                                type="text"
                                maxlength="100"
                                value="{{ old('account_group', $account->account_group) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                        </div>

                        <div>
                            <label for="parent_id" class="block text-sm font-medium text-gray-700">
                                Parent Account
                            </label>
                            <select
                                id="parent_id"
                                name="parent_id"
                                @disabled($hasJournals)
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm {{ $hasJournals ? 'bg-gray-100' : '' }}"
                            >
                                <option value="">No parent account</option>

                                @foreach ($parents as $parent)
                                    <option value="{{ $parent->id }}"
                                        @selected((string) old('parent_id', $account->parent_id) === (string) $parent->id)>
                                        {{ $parent->account_code }} — {{ $parent->account_name }}
                                    </option>
                                @endforeach
                            </select>

                            @if ($hasJournals)
                                <input type="hidden" name="parent_id" value="{{ $account->parent_id }}">
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Finance Master Integration --}}
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-5 text-base font-semibold text-gray-900">
                        Finance Master Integration
                    </h3>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label for="finance_head_id" class="block text-sm font-medium text-gray-700">
                                Linked Finance Head
                            </label>

                            <select
                                id="finance_head_id"
                                name="finance_head_id"
                                @disabled($hasJournals)
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm {{ $hasJournals ? 'bg-gray-100' : '' }}"
                            >
                                <option value="">No Finance Head</option>

                                @foreach ($financeHeads as $head)
                                    <option value="{{ $head->id }}"
                                        @selected((string) old('finance_head_id', $account->finance_head_id) === (string) $head->id)>
                                        {{ $head->code }} — {{ $head->name }}
                                    </option>
                                @endforeach
                            </select>

                            @if ($hasJournals)
                                <input type="hidden" name="finance_head_id" value="{{ $account->finance_head_id }}">
                            @endif
                        </div>

                        <div>
                            <label for="finance_account_id" class="block text-sm font-medium text-gray-700">
                                Linked Finance Account
                            </label>

                            <select
                                id="finance_account_id"
                                name="finance_account_id"
                                @disabled($hasJournals)
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm {{ $hasJournals ? 'bg-gray-100' : '' }}"
                            >
                                <option value="">No Finance Account</option>

                                @foreach ($financeAccounts as $financeAccount)
                                    <option value="{{ $financeAccount->id }}"
                                        @selected((string) old('finance_account_id', $account->finance_account_id) === (string) $financeAccount->id)>
                                        {{ $financeAccount->code }} — {{ $financeAccount->name }}
                                    </option>
                                @endforeach
                            </select>

                            @if ($hasJournals)
                                <input type="hidden" name="finance_account_id" value="{{ $account->finance_account_id }}">
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Tally Mapping --}}
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-5 text-base font-semibold text-gray-900">
                        Tally Ledger Mapping
                    </h3>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label for="tally_ledger_name" class="block text-sm font-medium text-gray-700">
                                Tally Ledger Name
                            </label>

                            <input
                                id="tally_ledger_name"
                                name="tally_ledger_name"
                                type="text"
                                maxlength="200"
                                value="{{ old('tally_ledger_name', $account->tally_ledger_name) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                        </div>

                        <div>
                            <label for="tally_group_name" class="block text-sm font-medium text-gray-700">
                                Tally Group Name
                            </label>

                            <input
                                id="tally_group_name"
                                name="tally_group_name"
                                type="text"
                                maxlength="150"
                                value="{{ old('tally_group_name', $account->tally_group_name) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                        </div>
                    </div>
                </div>

                {{-- Account Controls --}}
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-5 text-base font-semibold text-gray-900">
                        Account Controls
                    </h3>

                    <div class="space-y-4">
                        <label class="flex items-center gap-3">
                            <input type="hidden" name="is_active" value="0">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                @checked(old('is_active', $account->is_active) == 1)
                                class="rounded border-gray-300 text-indigo-600"
                            >
                            <span class="text-sm font-medium text-gray-700">
                                Active Account
                            </span>
                        </label>

                        <label class="flex items-center gap-3">
                            <input type="hidden" name="allow_posting" value="0">
                            <input
                                type="checkbox"
                                name="allow_posting"
                                value="1"
                                @checked(old('allow_posting', $account->allow_posting) == 1)
                                class="rounded border-gray-300 text-indigo-600"
                            >
                            <span class="text-sm font-medium text-gray-700">
                                Allow Journal Posting
                            </span>
                        </label>
                    </div>
                </div>

                {{-- Description --}}
                <div class="rounded-lg bg-white p-6 shadow">
                    <label for="description" class="block text-sm font-semibold text-gray-900">
                        Description / Remarks
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        maxlength="2000"
                        class="mt-2 block w-full rounded-md border-gray-300 shadow-sm"
                    >{{ old('description', $account->description) }}</textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <a
                        href="{{ route('finance.gl-accounts.index') }}"
                        class="rounded-md border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        Update GL Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
