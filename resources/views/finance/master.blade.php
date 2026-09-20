<x-app-layout>

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mb-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        Finance Master
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Manage hospital cash/bank accounts and income/expense heads
                    </p>
                </div>

                <a
                    href="{{ route('finance.dashboard') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50"
                >
                    ← Finance Dashboard
                </a>
            </div>
        </div>


        {{-- Success Message --}}
        @if(session('success'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                {{ session('success') }}
            </div>
        @endif


        {{-- Validation Errors --}}
        @if($errors->any())
            <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3">
                <div class="font-semibold text-rose-700">
                    Please correct the following:
                </div>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-700">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- ========================================================= --}}
        {{-- FINANCE ACCOUNTS --}}
        {{-- ========================================================= --}}

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-lg font-semibold text-slate-900">
                    Finance Accounts
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Cash counters and bank accounts where hospital funds are held
                </p>
            </div>


            {{-- Create Account --}}
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-5">

                <h3 class="mb-4 font-semibold text-slate-800">
                    Add Finance Account
                </h3>

                <form
                    method="POST"
                    action="{{ route('finance.master.accounts.store') }}"
                >
                    @csrf

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Code
                            </label>

                            <input
                                type="text"
                                name="code"
                                value="{{ old('code') }}"
                                placeholder="BANK-SBI"
                                required
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Account Name
                            </label>

                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="SBI Current Account"
                                required
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Type
                            </label>

                            <select
                                name="account_type"
                                required
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="cash" @selected(old('account_type') === 'cash')>
                                    Cash
                                </option>

                                <option value="bank" @selected(old('account_type') === 'bank')>
                                    Bank
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Opening Balance
                            </label>

                            <input
                                type="number"
                                name="opening_balance"
                                value="{{ old('opening_balance', 0) }}"
                                min="0"
                                step="0.01"
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Opening Balance Date
                            </label>

                            <input
                                type="date"
                                name="opening_balance_date"
                                value="{{ old('opening_balance_date') }}"
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Bank Name
                            </label>

                            <input
                                type="text"
                                name="bank_name"
                                value="{{ old('bank_name') }}"
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Account Number
                            </label>

                            <input
                                type="text"
                                name="account_number"
                                value="{{ old('account_number') }}"
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Branch
                            </label>

                            <input
                                type="text"
                                name="branch_name"
                                value="{{ old('branch_name') }}"
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                IFSC
                            </label>

                            <input
                                type="text"
                                name="ifsc_code"
                                value="{{ old('ifsc_code') }}"
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                        <div class="md:col-span-3">
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Remarks
                            </label>

                            <input
                                type="text"
                                name="remarks"
                                value="{{ old('remarks') }}"
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                    </div>

                    <div class="mt-4">
                        <button
                            type="submit"
                            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Add Account
                        </button>
                    </div>

                </form>

            </div>


            {{-- Account List --}}
            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-white">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Code
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Account
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Type
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                Bank Details
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-500">
                                Opening Balance
                            </th>

                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-slate-500">
                                Status
                            </th>

                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-slate-500">
                                Action
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        @forelse($accounts as $account)

                            {{-- Display Row --}}
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-slate-900">
                                    {{ $account->code }}
                                </td>

                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ $account->name }}
                                </td>

                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ ucfirst($account->account_type) }}
                                </td>

                                <td class="px-4 py-3 text-sm text-slate-600">
                                    @if($account->account_type === 'bank')

                                        <div>
                                            {{ $account->bank_name ?: '—' }}
                                        </div>

                                        @if($account->account_number)
                                            <div class="text-xs text-slate-500">
                                                A/C {{ $account->account_number }}
                                            </div>
                                        @endif

                                        @if($account->branch_name)
                                            <div class="text-xs text-slate-500">
                                                {{ $account->branch_name }}
                                            </div>
                                        @endif

                                        @if($account->ifsc_code)
                                            <div class="text-xs text-slate-500">
                                                IFSC {{ $account->ifsc_code }}
                                            </div>
                                        @endif

                                    @else
                                        —
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-right text-sm font-medium text-slate-900">
                                    ₹{{ number_format((float) $account->opening_balance, 2) }}

                                    @if($account->opening_balance_date)
                                        <div class="mt-1 text-xs font-normal text-slate-500">
                                            {{ $account->opening_balance_date->format('d M Y') }}
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-center">
                                    @if($account->is_active)
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                            Inactive
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <button
                                        type="button"
                                        onclick="document.getElementById('account-edit-{{ $account->id }}').classList.toggle('hidden')"
                                        class="inline-flex rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                                    >
                                        Edit
                                    </button>
                                </td>
                            </tr>


                            {{-- Edit Row --}}
                            <tr
                                id="account-edit-{{ $account->id }}"
                                class="hidden bg-slate-50"
                            >
                                <td colspan="7" class="px-5 py-5">

                                    <form
                                        method="POST"
                                        action="{{ route('finance.master.accounts.update', $account) }}"
                                    >
                                        @csrf
                                        @method('PUT')

                                        <div class="mb-4 flex items-center justify-between">
                                            <div>
                                                <h4 class="font-semibold text-slate-900">
                                                    Edit Finance Account
                                                </h4>

                                                <p class="text-xs text-slate-500">
                                                    {{ $account->code }} — {{ $account->name }}
                                                </p>
                                            </div>

                                            <button
                                                type="button"
                                                onclick="document.getElementById('account-edit-{{ $account->id }}').classList.add('hidden')"
                                                class="text-sm font-medium text-slate-500 hover:text-slate-900"
                                            >
                                                Close
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 md:grid-cols-4">

                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">
                                                    Code
                                                </label>

                                                <input
                                                    type="text"
                                                    name="code"
                                                    value="{{ $account->code }}"
                                                    required
                                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                            </div>

                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">
                                                    Account Name
                                                </label>

                                                <input
                                                    type="text"
                                                    name="name"
                                                    value="{{ $account->name }}"
                                                    required
                                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                            </div>

                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">
                                                    Type
                                                </label>

                                                <select
                                                    name="account_type"
                                                    required
                                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                                    <option
                                                        value="cash"
                                                        @selected($account->account_type === 'cash')
                                                    >
                                                        Cash
                                                    </option>

                                                    <option
                                                        value="bank"
                                                        @selected($account->account_type === 'bank')
                                                    >
                                                        Bank
                                                    </option>
                                                </select>
                                            </div>

                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">
                                                    Opening Balance
                                                </label>

                                                <input
                                                    type="number"
                                                    name="opening_balance"
                                                    value="{{ $account->opening_balance }}"
                                                    min="0"
                                                    step="0.01"
                                                    required
                                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                            </div>

                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">
                                                    Opening Balance Date
                                                </label>

                                                <input
                                                    type="date"
                                                    name="opening_balance_date"
                                                    value="{{ $account->opening_balance_date?->format('Y-m-d') }}"
                                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                            </div>

                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">
                                                    Bank Name
                                                </label>

                                                <input
                                                    type="text"
                                                    name="bank_name"
                                                    value="{{ $account->bank_name }}"
                                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                            </div>

                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">
                                                    Account Number
                                                </label>

                                                <input
                                                    type="text"
                                                    name="account_number"
                                                    value="{{ $account->account_number }}"
                                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                            </div>

                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">
                                                    Branch
                                                </label>

                                                <input
                                                    type="text"
                                                    name="branch_name"
                                                    value="{{ $account->branch_name }}"
                                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                            </div>

                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">
                                                    IFSC
                                                </label>

                                                <input
                                                    type="text"
                                                    name="ifsc_code"
                                                    value="{{ $account->ifsc_code }}"
                                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                            </div>

                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-slate-700">
                                                    Status
                                                </label>

                                                <select
                                                    name="is_active"
                                                    required
                                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                                    <option
                                                        value="1"
                                                        @selected($account->is_active)
                                                    >
                                                        Active
                                                    </option>

                                                    <option
                                                        value="0"
                                                        @selected(!$account->is_active)
                                                    >
                                                        Inactive
                                                    </option>
                                                </select>
                                            </div>

                                            <div class="md:col-span-3">
                                                <label class="mb-1 block text-sm font-medium text-slate-700">
                                                    Remarks
                                                </label>

                                                <input
                                                    type="text"
                                                    name="remarks"
                                                    value="{{ $account->remarks }}"
                                                    class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                            </div>

                                        </div>

                                        <div class="mt-4">
                                            <button
                                                type="submit"
                                                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                                            >
                                                Save Changes
                                            </button>
                                        </div>

                                    </form>

                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="7"
                                    class="px-4 py-8 text-center text-sm text-slate-500"
                                >
                                    No finance accounts found.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- FINANCE HEADS --}}
        {{-- ========================================================= --}}

        <div class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-lg font-semibold text-slate-900">
                    Income & Expense Heads
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Classification used for hospital receipts and expenditure
                </p>
            </div>


            {{-- Create Head --}}
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-5">

                <h3 class="mb-4 font-semibold text-slate-800">
                    Add Finance Head
                </h3>

                <form
                    method="POST"
                    action="{{ route('finance.master.heads.store') }}"
                >
                    @csrf

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-5">

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Code
                            </label>

                            <input
                                type="text"
                                name="code"
                                value="{{ old('code') }}"
                                placeholder="INC-LAB"
                                required
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Name
                            </label>

                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="Laboratory"
                                required
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Type
                            </label>

                            <select
                                name="head_type"
                                required
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option
                                    value="income"
                                    @selected(old('head_type') === 'income')
                                >
                                    Income
                                </option>

                                <option
                                    value="expense"
                                    @selected(old('head_type') === 'expense')
                                >
                                    Expense
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Category
                            </label>

                            <input
                                type="text"
                                name="category"
                                value="{{ old('category') }}"
                                placeholder="Diagnostics"
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Parent Head
                            </label>

                            <select
                                name="parent_id"
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">
                                    None
                                </option>

                                @foreach($parentHeads as $parent)
                                    <option
                                        value="{{ $parent->id }}"
                                        @selected((string) old('parent_id') === (string) $parent->id)
                                    >
                                        {{ ucfirst($parent->head_type) }}
                                        — {{ $parent->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-5">
                            <label class="mb-1 block text-sm font-medium text-slate-700">
                                Remarks
                            </label>

                            <input
                                type="text"
                                name="remarks"
                                value="{{ old('remarks') }}"
                                class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>

                    </div>

                    <div class="mt-4">
                        <button
                            type="submit"
                            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Add Finance Head
                        </button>
                    </div>

                </form>

            </div>


            {{-- Income Heads --}}
                        {{-- Income Heads --}}
            <div class="border-b border-slate-200">

                <div class="bg-emerald-50 px-5 py-3">
                    <h3 class="font-semibold text-emerald-800">
                        Income Heads
                    </h3>
                </div>

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead>
                            <tr class="bg-white">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                    Code
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                    Name
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                    Category
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                    Parent
                                </th>

                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-slate-500">
                                    Status
                                </th>

                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-slate-500">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @forelse($incomeHeads as $head)

                                {{-- Display Row --}}
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-slate-900">
                                        {{ $head->code }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        {{ $head->name }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        {{ $head->category ?: '—' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        {{ $head->parent?->name ?? '—' }}
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        @if($head->is_active)
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <button
                                            type="button"
                                            onclick="document.getElementById('head-edit-{{ $head->id }}').classList.toggle('hidden')"
                                            class="inline-flex rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                                        >
                                            Edit
                                        </button>
                                    </td>
                                </tr>


                                {{-- Edit Row --}}
                                <tr
                                    id="head-edit-{{ $head->id }}"
                                    class="hidden bg-emerald-50/40"
                                >
                                    <td colspan="6" class="px-5 py-5">

                                        <form
                                            method="POST"
                                            action="{{ route('finance.master.heads.update', $head) }}"
                                        >
                                            @csrf
                                            @method('PUT')

                                            <div class="mb-4 flex items-center justify-between">
                                                <div>
                                                    <h4 class="font-semibold text-slate-900">
                                                        Edit Income Head
                                                    </h4>

                                                    <p class="text-xs text-slate-500">
                                                        {{ $head->code }} — {{ $head->name }}
                                                    </p>
                                                </div>

                                                <button
                                                    type="button"
                                                    onclick="document.getElementById('head-edit-{{ $head->id }}').classList.add('hidden')"
                                                    class="text-sm font-medium text-slate-500 hover:text-slate-900"
                                                >
                                                    Close
                                                </button>
                                            </div>

                                            <div class="grid grid-cols-1 gap-4 md:grid-cols-5">

                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">
                                                        Code
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="code"
                                                        value="{{ $head->code }}"
                                                        required
                                                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">
                                                        Name
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="name"
                                                        value="{{ $head->name }}"
                                                        required
                                                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">
                                                        Type
                                                    </label>

                                                    <select
                                                        name="head_type"
                                                        required
                                                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                        <option value="income" @selected($head->head_type === 'income')>
                                                            Income
                                                        </option>

                                                        <option value="expense" @selected($head->head_type === 'expense')>
                                                            Expense
                                                        </option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">
                                                        Category
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="category"
                                                        value="{{ $head->category }}"
                                                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">
                                                        Parent Head
                                                    </label>

                                                    <select
                                                        name="parent_id"
                                                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                        <option value="">
                                                            None
                                                        </option>

                                                        @foreach($parentHeads as $parent)
                                                            @if(
                                                                $parent->head_type === 'income'
                                                                && $parent->id !== $head->id
                                                            )
                                                                <option
                                                                    value="{{ $parent->id }}"
                                                                    @selected($head->parent_id === $parent->id)
                                                                >
                                                                    {{ $parent->name }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">
                                                        Status
                                                    </label>

                                                    <select
                                                        name="is_active"
                                                        required
                                                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                        <option value="1" @selected($head->is_active)>
                                                            Active
                                                        </option>

                                                        <option value="0" @selected(!$head->is_active)>
                                                            Inactive
                                                        </option>
                                                    </select>
                                                </div>

                                                <div class="md:col-span-4">
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">
                                                        Remarks
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="remarks"
                                                        value="{{ $head->remarks }}"
                                                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                </div>

                                            </div>

                                            <div class="mt-4">
                                                <button
                                                    type="submit"
                                                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                                                >
                                                    Save Changes
                                                </button>
                                            </div>

                                        </form>

                                    </td>
                                </tr>

                            @empty

                                <tr>
                                    <td
                                        colspan="6"
                                        class="px-4 py-8 text-center text-sm text-slate-500"
                                    >
                                        No income heads found.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- Expense Heads --}}
            <div>

                <div class="bg-rose-50 px-5 py-3">
                    <h3 class="font-semibold text-rose-800">
                        Expense Heads
                    </h3>
                </div>

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead>
                            <tr class="bg-white">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                    Code
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                    Name
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                    Category
                                </th>

                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">
                                    Parent
                                </th>

                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-slate-500">
                                    Status
                                </th>

                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-slate-500">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @forelse($expenseHeads as $head)

                                {{-- Display Row --}}
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-slate-900">
                                        {{ $head->code }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        {{ $head->name }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        {{ $head->category ?: '—' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-slate-600">
                                        {{ $head->parent?->name ?? '—' }}
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        @if($head->is_active)
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <button
                                            type="button"
                                            onclick="document.getElementById('head-edit-{{ $head->id }}').classList.toggle('hidden')"
                                            class="inline-flex rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                                        >
                                            Edit
                                        </button>
                                    </td>
                                </tr>


                                {{-- Edit Row --}}
                                <tr
                                    id="head-edit-{{ $head->id }}"
                                    class="hidden bg-rose-50/40"
                                >
                                    <td colspan="6" class="px-5 py-5">

                                        <form
                                            method="POST"
                                            action="{{ route('finance.master.heads.update', $head) }}"
                                        >
                                            @csrf
                                            @method('PUT')

                                            <div class="mb-4 flex items-center justify-between">
                                                <div>
                                                    <h4 class="font-semibold text-slate-900">
                                                        Edit Expense Head
                                                    </h4>

                                                    <p class="text-xs text-slate-500">
                                                        {{ $head->code }} — {{ $head->name }}
                                                    </p>
                                                </div>

                                                <button
                                                    type="button"
                                                    onclick="document.getElementById('head-edit-{{ $head->id }}').classList.add('hidden')"
                                                    class="text-sm font-medium text-slate-500 hover:text-slate-900"
                                                >
                                                    Close
                                                </button>
                                            </div>

                                            <div class="grid grid-cols-1 gap-4 md:grid-cols-5">

                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">
                                                        Code
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="code"
                                                        value="{{ $head->code }}"
                                                        required
                                                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">
                                                        Name
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="name"
                                                        value="{{ $head->name }}"
                                                        required
                                                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">
                                                        Type
                                                    </label>

                                                    <select
                                                        name="head_type"
                                                        required
                                                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                        <option value="income" @selected($head->head_type === 'income')>
                                                            Income
                                                        </option>

                                                        <option value="expense" @selected($head->head_type === 'expense')>
                                                            Expense
                                                        </option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">
                                                        Category
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="category"
                                                        value="{{ $head->category }}"
                                                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">
                                                        Parent Head
                                                    </label>

                                                    <select
                                                        name="parent_id"
                                                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                        <option value="">
                                                            None
                                                        </option>

                                                        @foreach($parentHeads as $parent)
                                                            @if(
                                                                $parent->head_type === 'expense'
                                                                && $parent->id !== $head->id
                                                            )
                                                                <option
                                                                    value="{{ $parent->id }}"
                                                                    @selected($head->parent_id === $parent->id)
                                                                >
                                                                    {{ $parent->name }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">
                                                        Status
                                                    </label>

                                                    <select
                                                        name="is_active"
                                                        required
                                                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                        <option value="1" @selected($head->is_active)>
                                                            Active
                                                        </option>

                                                        <option value="0" @selected(!$head->is_active)>
                                                            Inactive
                                                        </option>
                                                    </select>
                                                </div>

                                                <div class="md:col-span-4">
                                                    <label class="mb-1 block text-sm font-medium text-slate-700">
                                                        Remarks
                                                    </label>

                                                    <input
                                                        type="text"
                                                        name="remarks"
                                                        value="{{ $head->remarks }}"
                                                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    >
                                                </div>

                                            </div>

                                            <div class="mt-4">
                                                <button
                                                    type="submit"
                                                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                                                >
                                                    Save Changes
                                                </button>
                                            </div>

                                        </form>

                                    </td>
                                </tr>

                            @empty

                                <tr>
                                    <td
                                        colspan="6"
                                        class="px-4 py-8 text-center text-sm text-slate-500"
                                    >
                                        No expense heads found.
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>