
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Review Tally Voucher
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6">

            <div class="rounded-lg bg-white p-6 shadow">
                <h3 class="mb-4 text-lg font-semibold">
                    Imported Tally Voucher
                </h3>

                <div class="grid gap-4 text-sm md:grid-cols-2">
                    <div>
                        <strong>Company:</strong>
                        {{ $tallyVoucher->tally_company }}
                    </div>
                    <div>
                        <strong>Date:</strong>
                        {{ $tallyVoucher->voucher_date?->format('d M Y') }}
                    </div>
                    <div>
                        <strong>Voucher:</strong>
                        {{ $tallyVoucher->voucher_type }}
                        {{ $tallyVoucher->voucher_number }}
                    </div>
                    <div>
                        <strong>Amount:</strong>
                        ₹{{ number_format($amount, 2) }}
                    </div>
                    <div class="md:col-span-2">
                        <strong>Narration:</strong>
                        {{ $tallyVoucher->narration ?: '—' }}
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow">
                <h3 class="mb-4 text-lg font-semibold">
                    Tally Ledger Entries
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left">
                                <th class="py-2">Ledger</th>
                                <th class="py-2 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tallyVoucher->entries as $entry)
                                <tr class="border-b">
                                    <td class="py-2">
                                        {{ $entry->ledger_name }}
                                    </td>
                                    <td class="py-2 text-right">
                                        ₹{{ number_format((float) $entry->amount, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <h4 class="mt-5 font-semibold">Active Ledger Mappings</h4>
                @forelse ($mappings as $mapping)
                    <div class="mt-2 text-sm">
                        {{ $mapping->tally_ledger_name }}
                        →
                        {{ $mapping->financeHead?->name
                            ?? $mapping->financeAccount?->name
                            ?? 'Unassigned' }}
                    </div>
                @empty
                    <p class="mt-2 text-sm text-amber-700">
                        No active ledger mappings found.
                    </p>
                @endforelse
            </div>

            @if ($errors->any())
                <div class="rounded-lg bg-red-50 p-4 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="rounded-lg bg-white p-6 shadow">
                <h3 class="mb-2 text-lg font-semibold">
                    Create ERP Finance Draft
                </h3>

                @if ($type === null)
                    <p class="text-sm text-red-700">
                        This voucher type is not supported for automatic
                        draft creation. Please review it manually.
                    </p>
                @else
                    <p class="mb-5 text-sm text-gray-600">
                        Confirm the ERP classification below.
                        Creating a draft will not post it to Finance.
                    </p>

                    <form method="POST"
                          action="{{ route('finance.tally.import.create-draft', $tallyVoucher) }}"
                          class="space-y-4">
                        @csrf

                        <input type="hidden"
                               name="voucher_type"
                               value="{{ $type }}">

                        <div>
                            <label class="block text-sm font-medium">
                                Voucher Type
                            </label>
                            <input type="text"
                                   value="{{ ucfirst($type) }}"
                                   disabled
                                   class="mt-1 w-full rounded-md border-gray-300 bg-gray-100">
                        </div>

                        @if ($type !== 'transfer')
                            <div>
                                <label class="block text-sm font-medium">
                                    Finance Head
                                </label>
                                <select name="finance_head_id"
                                        required
                                        class="mt-1 w-full rounded-md border-gray-300">
                                    <option value="">Select finance head</option>
                                    @foreach ($heads as $head)
                                        <option value="{{ $head->id }}"
                                            @selected(old('finance_head_id') == $head->id)>
                                            {{ $head->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium">
                                {{ $type === 'transfer'
                                    ? 'Source Account'
                                    : 'Cash / Bank Account' }}
                            </label>
                            <select name="finance_account_id"
                                    required
                                    class="mt-1 w-full rounded-md border-gray-300">
                                <option value="">Select account</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}"
                                        @selected(old('finance_account_id') == $account->id)>
                                        {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if ($type === 'transfer')
                            <div>
                                <label class="block text-sm font-medium">
                                    Destination Account
                                </label>
                                <select name="destination_account_id"
                                        required
                                        class="mt-1 w-full rounded-md border-gray-300">
                                    <option value="">Select destination</option>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}"
                                            @selected(old('destination_account_id') == $account->id)>
                                            {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium">
                                Reference Number
                            </label>
                            <input name="reference_no"
                                   maxlength="100"
                                   value="{{ old('reference_no', $tallyVoucher->voucher_number) }}"
                                   class="mt-1 w-full rounded-md border-gray-300">
                        </div>

                        <div>
                            <label class="block text-sm font-medium">
                                Narration
                            </label>
                            <textarea name="narration"
                                      rows="3"
                                      class="mt-1 w-full rounded-md border-gray-300">{{ old('narration', $tallyVoucher->narration) }}</textarea>
                        </div>

                        <label class="flex items-start gap-2 text-sm">
                            <input type="checkbox"
                                   name="confirm_review"
                                   value="1"
                                   required
                                   class="mt-1 rounded">
                            <span>
                                I have verified the Tally voucher, ledger
                                mappings, amount, and ERP classification.
                            </span>
                        </label>

                        <button
    type="submit"
    onclick="return confirm('Create an ERP draft from this Tally voucher?')"
    class="rounded-lg bg-indigo-600 px-5 py-2 font-semibold text-white hover:bg-indigo-700"
>
    Create ERP Draft
</button>


                    </form>
                @endif
            </div>

            <a href="{{ route('finance.tally.reconciliation') }}"
               class="inline-block text-sm text-indigo-700 hover:underline">
                ← Back to Reconciliation
            </a>

        </div>
    </div>
</x-app-layout>