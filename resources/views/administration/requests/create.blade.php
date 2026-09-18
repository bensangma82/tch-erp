<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    New Administrative Request
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Create a draft request for administrative review and approval
                </p>
            </div>

            <a
                href="{{ route('administration.requests.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
            >
                Back to Administration
            </a>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">


            @if ($errors->any())

                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4">

                    <div class="text-sm font-bold text-red-800">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

                </div>

            @endif


            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Request Details
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        This request will be saved as Draft. It can be submitted for verification after review.
                    </p>

                </div>


                <form
                    method="POST"
                    action="{{ route('administration.requests.store') }}"
                    class="space-y-6 p-6"
                >

                    @csrf


                    <div class="grid gap-6 md:grid-cols-2">


                        {{-- REQUEST TYPE --}}

                        <div>

                            <label
                                for="request_type"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Request Type
                                <span class="text-red-600">*</span>
                            </label>

                            <select
                                id="request_type"
                                name="request_type"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option value="">
                                    Select request type
                                </option>

                                <option value="purchase" @selected(old('request_type') === 'purchase')>
                                    Purchase
                                </option>

                                <option value="recruitment" @selected(old('request_type') === 'recruitment')>
                                    Recruitment
                                </option>

                                <option value="finance" @selected(old('request_type') === 'finance')>
                                    Finance
                                </option>

                                <option value="contract" @selected(old('request_type') === 'contract')>
                                    Contract
                                </option>

                                <option value="project" @selected(old('request_type') === 'project')>
                                    Project
                                </option>

                                <option value="maintenance" @selected(old('request_type') === 'maintenance')>
                                    Maintenance
                                </option>

                                <option value="hr" @selected(old('request_type') === 'hr')>
                                    HR
                                </option>

                                <option value="other" @selected(old('request_type') === 'other')>
                                    Other
                                </option>

                            </select>

                        </div>


                        {{-- PRIORITY --}}

                        <div>

                            <label
                                for="priority"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Priority
                                <span class="text-red-600">*</span>
                            </label>

                            <select
                                id="priority"
                                name="priority"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option value="low" @selected(old('priority') === 'low')>
                                    Low
                                </option>

                                <option
                                    value="normal"
                                    @selected(old('priority', 'normal') === 'normal')
                                >
                                    Normal
                                </option>

                                <option value="high" @selected(old('priority') === 'high')>
                                    High
                                </option>

                                <option value="urgent" @selected(old('priority') === 'urgent')>
                                    Urgent
                                </option>

                            </select>

                        </div>


                        {{-- TITLE --}}

                        <div class="md:col-span-2">

                            <label
                                for="title"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Title
                                <span class="text-red-600">*</span>
                            </label>

                            <input
                                id="title"
                                name="title"
                                type="text"
                                value="{{ old('title') }}"
                                required
                                maxlength="255"
                                placeholder="Example: Procurement of ICU ventilator"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>


                        {{-- DEPARTMENT --}}

                        <div>

                            <label
                                for="department_id"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Department / Unit
                            </label>

                            <select
                                id="department_id"
                                name="department_id"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option value="">
                                    Hospital-wide / Not specific
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


                        {{-- ESTIMATED AMOUNT --}}

                        <div>

                            <label
                                for="estimated_amount"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Estimated Amount
                            </label>

                            <div class="relative">

                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">
                                    ₹
                                </div>

                                <input
                                    id="estimated_amount"
                                    name="estimated_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value="{{ old('estimated_amount') }}"
                                    placeholder="0.00"
                                    class="w-full rounded-lg border-slate-300 pl-8 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >

                            </div>

                            <p class="mt-1 text-xs text-slate-500">
                                Leave blank if the request has no direct financial value.
                            </p>

                        </div>


                        {{-- DESCRIPTION --}}

                        <div class="md:col-span-2">

                            <label
                                for="description"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Description / Justification
                            </label>

                            <textarea
                                id="description"
                                name="description"
                                rows="6"
                                placeholder="Describe the requirement, justification, expected benefit, urgency and relevant background..."
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >{{ old('description') }}</textarea>

                        </div>


                        {{-- REMARKS --}}

                        <div class="md:col-span-2">

                            <label
                                for="remarks"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Internal Remarks
                            </label>

                            <textarea
                                id="remarks"
                                name="remarks"
                                rows="3"
                                placeholder="Optional internal notes..."
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >{{ old('remarks') }}</textarea>

                        </div>

                    </div>


                    {{-- GOVERNANCE NOTE --}}

                    <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-5">

                        <div class="text-sm font-bold text-indigo-900">
                            Administrative workflow
                        </div>

                        <div class="mt-2 text-sm leading-6 text-indigo-700">
                            The request is created as a Draft. Once submitted, the Administrator verifies documentation and completeness.
                            The Medical Superintendent then takes the institutional decision within delegated authority.
                            Requests requiring higher approval can subsequently be escalated to the appropriate authority.
                        </div>

                    </div>


                    {{-- ACTIONS --}}

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-6 sm:flex-row sm:items-center sm:justify-end">

                        <a
                            href="{{ route('administration.requests.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                        >
                            Cancel
                        </a>


                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                        >
                            Save Draft
                        </button>

                    </div>

                </form>

            </div>


        </div>

    </div>

</x-app-layout>
