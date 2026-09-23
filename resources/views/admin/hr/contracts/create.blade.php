<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">
                    Human Resources
                </div>

                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                    New Employee Contract
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Record a new employee contract period.
                </p>

            </div>

            <a
                href="{{ route('admin.hr.contracts.index') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
            >
                Contract Register
            </a>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">

            @if ($errors->any())

                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-4">
                    <div class="font-semibold text-red-800">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>

            @endif


            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="font-semibold text-slate-900">
                        Contract Details
                    </h3>
                </div>


                <form
                    method="POST"
                    action="{{ route('admin.hr.contracts.store') }}"
                    class="space-y-6 p-6"
                >

                    @csrf


                    <div class="grid gap-5 md:grid-cols-2">


                        <div class="md:col-span-2">

                            <label class="block text-sm font-semibold text-slate-700">
                                Employee
                            </label>

                            <select
                                name="employee_id"
                                required
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >
                                <option value="">Select employee</option>

                                @foreach ($employees as $employee)
                                    <option
                                        value="{{ $employee->id }}"
                                        @selected((string) old('employee_id') === (string) $employee->id)
                                    >
                                        {{ $employee->employee_code }}
                                        -
                                        {{ $employee->full_name }}

                                        @if ($employee->department)
                                            ({{ $employee->department->name }})
                                        @endif
                                    </option>
                                @endforeach
                            </select>

                        </div>


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                Contract Type
                            </label>

                            <select
                                name="contract_type"
                                required
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >
                                <option value="contractual" @selected(old('contract_type', 'contractual') === 'contractual')>Contractual</option>
                                <option value="temporary" @selected(old('contract_type') === 'temporary')>Temporary</option>
                                <option value="consultant" @selected(old('contract_type') === 'consultant')>Consultant</option>
                                <option value="probation" @selected(old('contract_type') === 'probation')>Probation</option>
                                <option value="other" @selected(old('contract_type') === 'other')>Other</option>
                            </select>

                        </div>


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                Designation
                            </label>

                            <input
                                type="text"
                                name="designation"
                                value="{{ old('designation') }}"
                                maxlength="150"
                                placeholder="Optional - defaults to employee designation"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                        </div>


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                Department
                            </label>

                            <select
                                name="department_id"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >
                                <option value="">
                                    Use employee's current department
                                </option>

                                @foreach ($departments as $department)
                                    <option
                                        value="{{ $department->id }}"
                                        @selected((string) old('department_id') === (string) $department->id)
                                    >
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>

                        </div>


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                Reference No.
                            </label>

                            <input
                                type="text"
                                name="reference_no"
                                value="{{ old('reference_no') }}"
                                maxlength="100"
                                placeholder="Appointment / office order reference"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                        </div>


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                Start Date
                            </label>

                            <input
                                type="date"
                                name="start_date"
                                value="{{ old('start_date') }}"
                                required
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                        </div>


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                End Date
                            </label>

                            <input
                                type="date"
                                name="end_date"
                                value="{{ old('end_date') }}"
                                required
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                        </div>


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                Renewal Due Date
                            </label>

                            <input
                                type="date"
                                name="renewal_due_date"
                                value="{{ old('renewal_due_date') }}"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                        </div>


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                Monthly Remuneration
                            </label>

                            <input
                                type="number"
                                name="monthly_remuneration"
                                value="{{ old('monthly_remuneration') }}"
                                min="0"
                                step="0.01"
                                placeholder="Optional"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                        </div>


                        <div class="md:col-span-2">

                            <label class="block text-sm font-semibold text-slate-700">
                                Terms Summary
                            </label>

                            <textarea
                                name="terms_summary"
                                rows="4"
                                maxlength="5000"
                                placeholder="Optional summary of key contract terms"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >{{ old('terms_summary') }}</textarea>

                        </div>


                        <div class="md:col-span-2">

                            <label class="block text-sm font-semibold text-slate-700">
                                Remarks
                            </label>

                            <textarea
                                name="remarks"
                                rows="3"
                                maxlength="5000"
                                placeholder="Optional internal HR remarks"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >{{ old('remarks') }}</textarea>

                        </div>


                    </div>


                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                        <a
                            href="{{ route('admin.hr.contracts.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                        >
                            Create Contract
                        </button>

                    </div>

                </form>

            </section>

        </div>

    </div>

</x-app-layout>
