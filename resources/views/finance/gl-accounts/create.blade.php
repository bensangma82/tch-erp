
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">
                    Add General Ledger Account
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Create an account in the TCH ERP Chart of Accounts.
                </p>
            </div>

            <a href="{{ route('finance.gl-accounts.index') }}"
               class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Back to Chart of Accounts
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
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

            <form method="POST"
                  action="{{ route('finance.gl-accounts.store') }}"
                  class="space-y-6">
                @csrf

                {{-- Account Identity --}}
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-5 text-base font-semibold text-gray-900">
                        Account Information
                    </h3>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label for="account_code"
                                   class="block text-sm font-medium text-gray-700">
                                Account Code *
                            </label>
                            <input id="account_code"
                                   name="account_code"
                                   type="text"
                                   maxlength="30"
                                   required
                                   value="{{ old('account_code') }}"
                                   placeholder="e.g. 2100"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div>
                            <label for="account_name"
                                   class="block text-sm font-medium text-gray-700">
                                Account Name *
                            </label>
                            <input id="account_name"
                                   name="account_name"
                                   type="text"
                                   maxlength="150"
                                   required
                                   value="{{ old('account_name') }}"
                                   placeholder="e.g. Patient Advances / Deposits"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div>
                            <label for="account_type"
                                   class="block text-sm font-medium text-gray-700">
                                Account Type *
                            </label>
                            <select id="account_type"
                                    name="account_type"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">Select account type</option>
                                @foreach ([
                                    'asset' => 'Asset',
                                    'liability' => 'Liability',
                                    'equity' => 'Equity',
                                    'income' => 'Income',
                                    'expense' => 'Expense',
                                ] as $value => $label)
                                    <option value="{{ $value }}"
                                        @selected(old('account_type') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="normal_balance"
                                   class="block text-sm font-medium text-gray-700">
                                Normal Balance *
                            </label>
                            <select id="normal_balance"
                                    name="normal_balance"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">Select balance</option>
                                <option value="debit" @selected(old('normal_balance') === 'debit')>
                                    Debit
                                </option>
                                <option value="credit" @selected(old('normal_balance') === 'credit')>
                                    Credit
                                </option>
                            </select>
                        </div>

                        <div>
                            <label for="account_group"
                                   class="block text-sm font-medium text-gray-700">
                                Account Group
                            </label>
                            <input id="account_group"
                                   name="account_group"
                                   type="text"
                                   maxlength="100"
                                   value="{{ old('account_group') }}"
                                   placeholder="e.g. Current Liabilities"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div>
                            <label for="parent_id"
                                   class="block text-sm font-medium text-gray-700">
                                Parent Account
                            </label>
                            <select id="parent_id"
                                    name="parent_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">No parent account</option>
                                @foreach ($parents as $parent)
                                    <option value="{{ $parent->id }}"
                                        @selected((string) old('parent_id') === (string) $parent->id)>
                                        {{ $parent->account_code }} — {{ $parent->account_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Finance Master Links --}}
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-5 text-base font-semibold text-gray-900">
                        Finance Master Integration
                    </h3>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label for="finance_head_id"
                                   class="block text-sm font-medium text-gray-700">
                                Linked Finance Head
                            </label>
                            <select id="finance_head_id"
                                    name="finance_head_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">No Finance Head</option>
                                @foreach ($financeHeads as $head)
                                    <option value="{{ $head->id }}"
                                        @selected((string) old('finance_head_id') === (string) $head->id)>
                                        {{ $head->code }} — {{ $head->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="finance_account_id"
                                   class="block text-sm font-medium text-gray-700">
                                Linked Finance Account
                            </label>
                            <select id="finance_account_id"
                                    name="finance_account_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">No Finance Account</option>
                                @foreach ($financeAccounts as $financeAccount)
                                    <option value="{{ $financeAccount->id }}"
                                        @selected((string) old('finance_account_id') === (string) $financeAccount->id)>
                                        {{ $financeAccount->code }} — {{ $financeAccount->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <p class="mt-3 text-xs text-gray-500">
                        Finance Master links are optional. Assign only the relevant
                        income/expense head or cash/bank account.
                    </p>
                </div>

                {{-- Tally Integration --}}
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-5 text-base font-semibold text-gray-900">
                        Tally Ledger Mapping
                    </h3>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label for="tally_ledger_name"
                                   class="block text-sm font-medium text-gray-700">
                                Tally Ledger Name
                            </label>
                            <input id="tally_ledger_name"
                                   name="tally_ledger_name"
                                   type="text"
                                   maxlength="200"
                                   value="{{ old('tally_ledger_name') }}"
                                   placeholder="Exact ledger name in Tally"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div>
                            <label for="tally_group_name"
                                   class="block text-sm font-medium text-gray-700">
                                Tally Group Name
                            </label>
                            <input id="tally_group_name"
                                   name="tally_group_name"
                                   type="text"
                                   maxlength="150"
                                   value="{{ old('tally_group_name') }}"
                                   placeholder="e.g. Sundry Debtors"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>

                    <p class="mt-3 text-xs text-gray-500">
                        The Tally ledger name must exactly match the ledger
                        in the destination Tally company.
                    </p>
                </div>

                {{-- Controls --}}
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-5 text-base font-semibold text-gray-900">
                        Account Controls
                    </h3>

                    <div class="space-y-4">
                        <label class="flex items-center gap-3">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox"
                                   name="is_active"
                                   value="1"
                                   @checked(old('is_active', '1') == '1')
                                   class="rounded border-gray-300 text-indigo-600">
                            <span class="text-sm font-medium text-gray-700">
                                Active Account
                            </span>
                        </label>

                        <label class="flex items-center gap-3">
                            <input type="hidden" name="allow_posting" value="0">
                            <input type="checkbox"
                                   name="allow_posting"
                                   value="1"
                                   @checked(old('allow_posting', '1') == '1')
                                   class="rounded border-gray-300 text-indigo-600">
                            <span class="text-sm font-medium text-gray-700">
                                Allow Journal Posting
                            </span>
                        </label>
                    </div>
                </div>

                {{-- Description --}}
                <div class="rounded-lg bg-white p-6 shadow">
                    <label for="description"
                           class="block text-sm font-semibold text-gray-900">
                        Description / Remarks
                    </label>

                    <textarea id="description"
                              name="description"
                              rows="3"
                              maxlength="2000"
                              class="mt-2 block w-full rounded-md border-gray-300 shadow-sm"
                              placeholder="Purpose and accounting treatment of this ledger">{{ old('description') }}</textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('finance.gl-accounts.index') }}"
                       class="rounded-md border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>

                    <button type="submit"
                            class="rounded-md bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Save GL Account
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
