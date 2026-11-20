<section id="basic-pay-rates" class="scroll-mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h3 class="font-semibold text-slate-900">Basic Pay Rates</h3>
    <p class="mt-1 text-sm text-slate-600">
        Set a monthly amount for each staff group and pay grade. Saving the same combination updates its rate.
        Salary structures save the amount entered for each employee.
    </p>

    @if ($errors->basicPayRate->any())
        <ul role="alert" class="mt-4 list-disc space-y-1 pl-5 text-sm text-red-700">
            @foreach ($errors->basicPayRate->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('admin.hr.payroll.salary-components.basic-pay-rates.save') }}"
        class="mt-4 grid items-end gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @csrf
        @method('PUT')

        <label class="block text-sm font-medium text-slate-700">
            Staff Group
            <input name="staff_group" type="text" list="rate-staff-groups" maxlength="100" required
                value="{{ old('staff_group') }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
            <datalist id="rate-staff-groups">
                @foreach ($staffGroups as $label)
                    <option value="{{ $label }}"></option>
                @endforeach
            </datalist>
        </label>

        <label class="block text-sm font-medium text-slate-700">
            Pay Grade
            <input name="pay_grade" type="text" maxlength="60" required value="{{ old('pay_grade') }}"
                class="mt-1 w-full rounded-lg border-slate-300 text-sm">
        </label>

        <label class="block text-sm font-medium text-slate-700">
            Monthly Basic Pay (₹)
            <input name="amount" type="number" min="0.01" max="9999999999.99" step="0.01" required
                value="{{ old('amount') }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
        </label>

        <button type="submit" class="rounded-lg px-4 py-2 text-sm font-semibold text-white"
            style="background-color: #0e7490; color: #ffffff;">
            Save Rate
        </button>
    </form>

    @if ($basicPayRates->isNotEmpty())
        <div class="mt-5 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th scope="col" class="px-3 py-2">Staff Group or Employment Type</th>
                        <th scope="col" class="px-3 py-2">Pay Grade</th>
                        <th scope="col" class="px-3 py-2 text-right">Basic Pay</th>
                        <th scope="col" class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($basicPayRates as $rate)
                        <tr>
                            @php
                                $rateLabel = $staffGroups->get($rate->staff_group)
                                    ?? 'Employment type · ' . $rate->employee_category;
                            @endphp
                            <td class="px-3 py-3">{{ $rateLabel }}</td>
                            <td class="px-3 py-3">{{ $rate->pay_grade }}</td>
                            <td class="px-3 py-3 text-right">₹{{ number_format((float) $rate->amount, 2) }}</td>
                            <td class="px-3 py-3 text-right">
                                <form method="POST"
                                    action="{{ route('admin.hr.payroll.salary-components.basic-pay-rates.remove', $rate) }}"
                                    onsubmit="return confirm('Remove this Basic Pay rate?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-medium text-red-700 hover:underline"
                                        aria-label="Remove Basic Pay rate for {{ $rateLabel }}, {{ $rate->pay_grade }}">
                                        Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
