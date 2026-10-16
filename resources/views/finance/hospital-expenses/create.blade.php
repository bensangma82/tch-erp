<x-app-layout>
<div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">

    <div class="mb-6">
        <a href="{{ route('finance.hospital-expenses.index') }}"
           class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
            ← Back to Expense Register
        </a>

        <h1 class="mt-3 text-2xl font-bold text-gray-900">
            Register Hospital Expense
        </h1>

        <p class="mt-1 text-sm text-gray-500">
            Clinical, non-clinical and shared expenditure
        </p>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4">
            <h2 class="font-semibold text-red-800">
                Please correct the following:
            </h2>
            <ul class="mt-2 list-disc pl-5 text-sm text-red-700">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('finance.hospital-expenses.store') }}"
          x-data="expenseForm()"
          class="space-y-6">

        @csrf

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-lg font-semibold text-gray-900">
                Expense Information
            </h2>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Expense Date
                    </label>
                    <input type="date" name="expense_date"
                           value="{{ old('expense_date', now()->toDateString()) }}"
                           required
                           class="mt-1 w-full rounded-lg border-gray-300">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Accounting Date
                    </label>
                    <input type="date" name="accounting_date"
                           value="{{ old('accounting_date', now()->toDateString()) }}"
                           required
                           class="mt-1 w-full rounded-lg border-gray-300">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Classification
                    </label>
                    <select name="expense_classification" required
                            class="mt-1 w-full rounded-lg border-gray-300">
                        <option value="">Select classification</option>
                        @foreach([
                            'clinical' => 'Clinical',
                            'non_clinical' => 'Non-Clinical',
                            'shared' => 'Shared',
                        ] as $value => $label)
                            <option value="{{ $value }}"
                                @selected(old('expense_classification') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Expense Type
                    </label>
                    <select name="expense_type" required
                            x-model="expenseType"
                            class="mt-1 w-full rounded-lg border-gray-300">
                        <option value="operating">Operating Expense</option>
                        <option value="inventory">Inventory Acquisition</option>
                        <option value="capital">Capital Expenditure</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Finance Head
                    </label>
                    <select name="finance_head_id" required
                            class="mt-1 w-full rounded-lg border-gray-300">
                        <option value="">Select Finance Head</option>
                        @foreach($financeHeads as $head)
                            <option value="{{ $head->id }}"
                                @selected(old('finance_head_id') == $head->id)>
                                {{ $head->code }} — {{ $head->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        General Ledger Account
                    </label>
                    <select name="general_ledger_account_id" required
                            class="mt-1 w-full rounded-lg border-gray-300">
                        <option value="">Select GL Account</option>
                        @foreach($glAccounts as $account)
                            <option value="{{ $account->id }}"
                                x-show="expenseType === 'operating'
                                    ? {{ \Illuminate\Support\Js::from($account->account_type === 'expense') }}
                                    : {{ \Illuminate\Support\Js::from($account->account_type === 'asset') }}"
                                @selected(old('general_ledger_account_id') == $account->id)>
                                {{ $account->account_code }} — {{ $account->account_name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">
                        Operating expenses use expense accounts; inventory
                        and capital acquisitions use asset accounts.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Supplier / Payee
                    </label>
                    <input type="text" name="supplier_name"
                           value="{{ old('supplier_name') }}"
                           maxlength="255"
                           class="mt-1 w-full rounded-lg border-gray-300">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Invoice Number
                    </label>
                    <input type="text" name="invoice_no"
                           value="{{ old('invoice_no') }}"
                           maxlength="100"
                           class="mt-1 w-full rounded-lg border-gray-300">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Invoice Date
                    </label>
                    <input type="date" name="invoice_date"
                           value="{{ old('invoice_date') }}"
                           class="mt-1 w-full rounded-lg border-gray-300">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">
                        Description
                    </label>
                    <textarea name="description" rows="3" required
                              maxlength="500"
                              class="mt-1 w-full rounded-lg border-gray-300">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-lg font-semibold text-gray-900">
                Expense Amount
            </h2>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Subtotal (₹)
                    </label>
                    <input type="number" name="subtotal"
                           x-model.number="subtotal"
                           step="0.01" min="0" required
                           class="mt-1 w-full rounded-lg border-gray-300">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Tax (₹)
                    </label>
                    <input type="number" name="tax_amount"
                           x-model.number="tax"
                           step="0.01" min="0" required
                           class="mt-1 w-full rounded-lg border-gray-300">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Total Amount (₹)
                    </label>
                    <input type="number" name="total_amount"
                           :value="total"
                           step="0.01" readonly
                           class="mt-1 w-full rounded-lg border-gray-300 bg-gray-50">
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="mb-5 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">
                        Department Allocation
                    </h2>
                    <p class="text-sm text-gray-500">
                        Allocate 100% of the expense across departments.
                    </p>
                </div>

                <button type="button" @click="addAllocation()"
                        class="rounded-lg border border-indigo-200 px-3 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50">
                    + Add Department
                </button>
            </div>

            <template x-for="(allocation, index) in allocations" :key="allocation.key">
                <div class="mb-3 grid grid-cols-1 gap-3 rounded-lg bg-gray-50 p-3 md:grid-cols-12">

                    <div class="md:col-span-6">
                        <label class="block text-xs font-medium text-gray-600">
                            Department
                        </label>
                        <select :name="`allocations[${index}][department_id]`"
                                x-model="allocation.department_id"
                                required
                                class="mt-1 w-full rounded-lg border-gray-300">
                            <option value="">Select department</option>
                            @foreach($departments as $department)
                                @if($department->is_active)
                                    <option value="{{ $department->id }}">
                                        {{ $department->name }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600">
                            Percentage
                        </label>
                        <input type="number"
                               :name="`allocations[${index}][percentage]`"
                               x-model.number="allocation.percentage"
                               min="0.0001" max="100" step="0.0001"
                               required
                               class="mt-1 w-full rounded-lg border-gray-300">
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-xs font-medium text-gray-600">
                            Amount (₹)
                        </label>
                        <input type="number"
                               :name="`allocations[${index}][allocated_amount]`"
                               :value="allocationAmount(index)"
                               readonly
                               step="0.01"
                               class="mt-1 w-full rounded-lg border-gray-300 bg-gray-100">
                    </div>

                    <div class="flex items-end md:col-span-1">
                        <button type="button"
                                @click="removeAllocation(index)"
                                :disabled="allocations.length === 1"
                                class="rounded-lg px-3 py-2 text-red-600 hover:bg-red-50 disabled:opacity-30">
                            Remove
                        </button>
                    </div>
                </div>
            </template>

            <div class="mt-4 text-sm">
                <span class="font-semibold">Allocated:</span>
                <span x-text="percentageTotal().toFixed(4) + '%'"></span>
                <span class="ml-3 text-gray-500">
                    Required: 100%
                </span>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <label class="block text-sm font-medium text-gray-700">
                Remarks
            </label>
            <textarea name="remarks" rows="2"
                      class="mt-1 w-full rounded-lg border-gray-300">{{ old('remarks') }}</textarea>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('finance.hospital-expenses.index') }}"
               class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-medium text-gray-700">
                Cancel
            </a>

            <button type="submit"
                    class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                Save Expense Draft
            </button>
        </div>
    </form>
</div>

<script>
function expenseForm() {
    return {
        expenseType: @js(old('expense_type', 'operating')),
        subtotal: @js((float) old('subtotal', 0)),
        tax: @js((float) old('tax_amount', 0)),
        nextKey: 1,
        allocations: @js(old('allocations', [
            ['department_id' => '', 'percentage' => 100]
        ])).map((a, i) => ({ ...a, key: i })),

        get total() {
            return (Math.round(
                (Number(this.subtotal) + Number(this.tax)) * 100
            ) / 100).toFixed(2);
        },

        percentageTotal() {
            return this.allocations.reduce(
                (sum, a) => sum + Number(a.percentage || 0), 0
            );
        },

        addAllocation() {
            this.allocations.push({
                key: this.nextKey++ + 1000,
                department_id: '',
                percentage: 0
            });
        },

        removeAllocation(index) {
            if (this.allocations.length > 1) {
                this.allocations.splice(index, 1);
            }
        },

        allocationAmount(index) {
            const totalPaise = Math.round(Number(this.total) * 100);
            const previous = this.allocations
                .slice(0, index)
                .reduce((sum, a) =>
                    sum + Math.round(
                        totalPaise * Number(a.percentage || 0) / 100
                    ), 0);

            if (index === this.allocations.length - 1) {
                return ((totalPaise - previous) / 100).toFixed(2);
            }

            return (Math.round(
                totalPaise * Number(
                    this.allocations[index].percentage || 0
                ) / 100
            ) / 100).toFixed(2);
        }
    };
}
</script>
</x-app-layout>
