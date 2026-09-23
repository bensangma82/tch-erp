<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">
                    Human Resources
                </div>

                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                    Manage Contract
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $contract->contract_no }}
                    ·
                    {{ $contract->employee?->full_name ?? 'Employee' }}
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

        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">


            @if ($errors->any())

                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-4">

                    <div class="font-semibold text-red-800">
                        Unable to complete the action:
                    </div>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

                </div>

            @endif



            {{-- CURRENT CONTRACT --}}

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div>
                            <h3 class="font-semibold text-slate-900">
                                Current Contract
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Edit contract details while the contract is active or expired.
                            </p>
                        </div>

                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                            {{ ucfirst($contract->status) }}
                        </span>

                    </div>

                </div>


                <form
                    method="POST"
                    action="{{ route('admin.hr.contracts.update', $contract) }}"
                    class="space-y-6 p-6"
                >

                    @csrf
                    @method('PUT')


                    <div class="grid gap-5 md:grid-cols-2">


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                Contract Type
                            </label>

                            <select
                                name="contract_type"
                                required
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >
                                @foreach (['contractual', 'temporary', 'consultant', 'probation', 'other'] as $type)
                                    <option
                                        value="{{ $type }}"
                                        @selected(old('contract_type', $contract->contract_type) === $type)
                                    >
                                        {{ ucfirst($type) }}
                                    </option>
                                @endforeach
                            </select>

                        </div>


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                Designation
                            </label>

                            <input
                                type="text"
                                name="designation"
                                value="{{ old('designation', $contract->designation) }}"
                                maxlength="150"
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
                                <option value="">No department</option>

                                @foreach ($departments as $department)
                                    <option
                                        value="{{ $department->id }}"
                                        @selected((string) old('department_id', $contract->department_id) === (string) $department->id)
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
                                value="{{ old('reference_no', $contract->reference_no) }}"
                                maxlength="100"
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
                                value="{{ old('start_date', $contract->start_date?->format('Y-m-d')) }}"
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
                                value="{{ old('end_date', $contract->end_date?->format('Y-m-d')) }}"
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
                                value="{{ old('renewal_due_date', $contract->renewal_due_date?->format('Y-m-d')) }}"
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
                                value="{{ old('monthly_remuneration', $contract->monthly_remuneration) }}"
                                min="0"
                                step="0.01"
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
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >{{ old('terms_summary', $contract->terms_summary) }}</textarea>

                        </div>


                        <div class="md:col-span-2">

                            <label class="block text-sm font-semibold text-slate-700">
                                Remarks
                            </label>

                            <textarea
                                name="remarks"
                                rows="3"
                                maxlength="5000"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >{{ old('remarks', $contract->remarks) }}</textarea>

                        </div>

                    </div>


                    <div class="flex justify-end">

                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                        >
                            Save Contract Changes
                        </button>

                    </div>

                </form>

            </section>



            {{-- RENEW --}}

            <section class="overflow-hidden rounded-2xl border border-blue-200 bg-blue-50/40 shadow-sm">

                <div class="border-b border-blue-100 px-6 py-5">

                    <h3 class="font-semibold text-blue-950">
                        Renew Contract
                    </h3>

                    <p class="mt-1 text-xs text-blue-700">
                        Renewal creates a new contract record and preserves this contract in history.
                    </p>

                </div>


                <form
                    method="POST"
                    action="{{ route('admin.hr.contracts.renew', $contract) }}"
                    class="space-y-5 p-6"
                >

                    @csrf


                    <div class="grid gap-5 md:grid-cols-2">

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">New Start Date</label>
                            <input
                                type="date"
                                name="start_date"
                                required
                                class="mt-2 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">New End Date</label>
                            <input
                                type="date"
                                name="end_date"
                                required
                                class="mt-2 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Renewal Due Date</label>
                            <input
                                type="date"
                                name="renewal_due_date"
                                class="mt-2 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Monthly Remuneration</label>
                            <input
                                type="number"
                                name="monthly_remuneration"
                                value="{{ $contract->monthly_remuneration }}"
                                min="0"
                                step="0.01"
                                class="mt-2 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">New Reference No.</label>
                            <input
                                type="text"
                                name="reference_no"
                                maxlength="100"
                                class="mt-2 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700">Renewal Remarks</label>
                            <input
                                type="text"
                                name="remarks"
                                maxlength="5000"
                                class="mt-2 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                        </div>

                    </div>


                    <div class="flex justify-end">

                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
                        >
                            Renew Contract
                        </button>

                    </div>

                </form>

            </section>



            {{-- TERMINATE --}}

            @if ($contract->status === 'active')

                <section class="overflow-hidden rounded-2xl border border-red-200 bg-red-50/40 shadow-sm">

                    <div class="border-b border-red-100 px-6 py-5">

                        <h3 class="font-semibold text-red-900">
                            Terminate Contract
                        </h3>

                        <p class="mt-1 text-xs text-red-700">
                            Use only when a contract ends before normal expiry.
                        </p>

                    </div>


                    <form
                        method="POST"
                        action="{{ route('admin.hr.contracts.terminate', $contract) }}"
                        class="space-y-5 p-6"
                    >

                        @csrf


                        <div class="grid gap-5 md:grid-cols-2">

                            <div>

                                <label class="block text-sm font-semibold text-slate-700">
                                    Termination Date
                                </label>

                                <input
                                    type="date"
                                    name="terminated_on"
                                    value="{{ today()->format('Y-m-d') }}"
                                    required
                                    class="mt-2 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
                                >

                            </div>


                            <div>

                                <label class="block text-sm font-semibold text-slate-700">
                                    Termination Reason
                                </label>

                                <textarea
                                    name="termination_reason"
                                    rows="3"
                                    maxlength="5000"
                                    required
                                    class="mt-2 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
                                ></textarea>

                            </div>

                        </div>


                        <div class="flex justify-end">

                            <button
                                type="submit"
                                class="inline-flex items-center rounded-lg bg-red-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-800"
                            >
                                Terminate Contract
                            </button>

                        </div>

                    </form>

                </section>

            @endif


        </div>

    </div>

</x-app-layout>
