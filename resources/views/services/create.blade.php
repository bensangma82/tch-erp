<x-app-layout>

    <x-slot name="header">

        <div>

            <h2 class="text-xl font-semibold text-gray-800">
                Add Hospital Service
            </h2>

            <p class="mt-1 text-sm text-gray-500">
                Create a laboratory test, imaging service, procedure or other billable service.
            </p>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">


            {{-- VALIDATION ERRORS --}}
            @if ($errors->any())

                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">

                    <div class="text-sm font-semibold text-red-700">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-inside list-disc text-sm text-red-600">

                        @foreach ($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif


            <form
                method="POST"
                action="{{ route('services.store') }}"
            >

                @csrf


                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">


                    {{-- CARD HEADER --}}
                    <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">

                        <h3 class="font-semibold text-gray-800">
                            Service Details
                        </h3>

                        <p class="mt-1 text-xs text-gray-500">
                            Fields marked with * are required.
                        </p>

                    </div>


                    {{-- FORM BODY --}}
                    <div class="space-y-6 p-6">


                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">


                            {{-- SERVICE CODE --}}
                            <div>

                                <label
                                    for="code"
                                    class="mb-1 block text-sm font-medium text-gray-700"
                                >
                                    Service Code *
                                </label>

                                <input
                                    id="code"
                                    type="text"
                                    name="code"
                                    value="{{ old('code') }}"
                                    placeholder="Example: CBC"
                                    required
                                    autocomplete="off"
                                    class="w-full rounded-lg border-gray-300 uppercase shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                >

                                <p class="mt-1 text-xs text-gray-500">
                                    Use a short unique code such as CBC, CREAT, CXR or USGABD.
                                </p>

                            </div>


                            {{-- SERVICE NAME --}}
                            <div>

                                <label
                                    for="name"
                                    class="mb-1 block text-sm font-medium text-gray-700"
                                >
                                    Service Name *
                                </label>

                                <input
                                    id="name"
                                    type="text"
                                    name="name"
                                    value="{{ old('name') }}"
                                    placeholder="Example: Complete Blood Count"
                                    required
                                    autocomplete="off"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                >

                            </div>


                            {{-- CATEGORY --}}
                            <div>

                                <label
                                    for="category"
                                    class="mb-1 block text-sm font-medium text-gray-700"
                                >
                                    Category *
                                </label>

                                <select
                                    id="category"
                                    name="category"
                                    required
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                >

                                    <option value="">
                                        Select Category
                                    </option>

                                    <option
                                        value="laboratory"
                                        @selected(old('category') === 'laboratory')
                                    >
                                        Laboratory
                                    </option>

                                    <option
                                        value="radiology"
                                        @selected(old('category') === 'radiology')
                                    >
                                        Radiology
                                    </option>

                                    <option
                                        value="procedure"
                                        @selected(old('category') === 'procedure')
                                    >
                                        Procedure
                                    </option>

                                    <option
                                        value="consultation"
                                        @selected(old('category') === 'consultation')
                                    >
                                        Consultation
                                    </option>

                                    <option
                                        value="other"
                                        @selected(old('category') === 'other')
                                    >
                                        Other
                                    </option>

                                </select>

                            </div>


                            {{-- DEPARTMENT --}}
                            <div>

                                <label
                                    for="department_id"
                                    class="mb-1 block text-sm font-medium text-gray-700"
                                >
                                    Department
                                </label>

                                <select
                                    id="department_id"
                                    name="department_id"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                >

                                    <option value="">
                                        No Specific Department
                                    </option>

                                    @foreach ($departments as $department)

                                        <option
                                            value="{{ $department->id }}"
                                            @selected(
                                                old('department_id') == $department->id
                                            )
                                        >
                                            {{ $department->name }}
                                        </option>

                                    @endforeach

                                </select>

                                <p class="mt-1 text-xs text-gray-500">
                                    Optional. Use this if the service belongs to a specific department.
                                </p>

                            </div>


                            {{-- PRICE --}}
                            <div>

                                <label
                                    for="price"
                                    class="mb-1 block text-sm font-medium text-gray-700"
                                >
                                    Price (₹) *
                                </label>

                                <input
                                    id="price"
                                    type="number"
                                    name="price"
                                    value="{{ old('price', '0.00') }}"
                                    min="0"
                                    max="999999.99"
                                    step="0.01"
                                    required
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                >

                            </div>


                            {{-- UNIT --}}
                            <div>

                                <label
                                    for="unit"
                                    class="mb-1 block text-sm font-medium text-gray-700"
                                >
                                    Unit
                                </label>

                                <input
                                    id="unit"
                                    type="text"
                                    name="unit"
                                    value="{{ old('unit') }}"
                                    placeholder="Optional"
                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                >

                                <p class="mt-1 text-xs text-gray-500">
                                    Optional. Useful later for laboratory reporting.
                                </p>

                            </div>

                        </div>


                        {{-- DESCRIPTION --}}
                        <div>

                            <label
                                for="description"
                                class="mb-1 block text-sm font-medium text-gray-700"
                            >
                                Description
                            </label>

                            <textarea
                                id="description"
                                name="description"
                                rows="3"
                                placeholder="Optional description or remarks"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-slate-500 focus:ring-slate-500"
                            >{{ old('description') }}</textarea>

                        </div>


                        {{-- SERVICE OPTIONS --}}
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">

                            <h4 class="mb-4 text-sm font-semibold text-gray-800">
                                Service Options
                            </h4>


                            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">


                                {{-- REQUIRES SAMPLE --}}
                                <label class="flex items-start gap-3">

                                    <input
                                        type="checkbox"
                                        name="requires_sample"
                                        value="1"
                                        @checked(old('requires_sample'))
                                        class="mt-1 rounded border-gray-300 text-slate-900 focus:ring-slate-500"
                                    >

                                    <div>

                                        <div class="text-sm font-medium text-gray-700">
                                            Requires Sample
                                        </div>

                                        <div class="mt-1 text-xs text-gray-500">
                                            Example: blood or urine tests.
                                        </div>

                                    </div>

                                </label>


                                {{-- REQUIRES REPORT --}}
                                <label class="flex items-start gap-3">

                                    <input
                                        type="checkbox"
                                        name="requires_report"
                                        value="1"
                                        @checked(old('requires_report', true))
                                        class="mt-1 rounded border-gray-300 text-slate-900 focus:ring-slate-500"
                                    >

                                    <div>

                                        <div class="text-sm font-medium text-gray-700">
                                            Requires Report
                                        </div>

                                        <div class="mt-1 text-xs text-gray-500">
                                            Generates a laboratory or diagnostic report.
                                        </div>

                                    </div>

                                </label>


                                {{-- ACTIVE --}}
                                <label class="flex items-start gap-3">

                                    <input
                                        type="checkbox"
                                        name="is_active"
                                        value="1"
                                        @checked(old('is_active', true))
                                        class="mt-1 rounded border-gray-300 text-slate-900 focus:ring-slate-500"
                                    >

                                    <div>

                                        <div class="text-sm font-medium text-gray-700">
                                            Active
                                        </div>

                                        <div class="mt-1 text-xs text-gray-500">
                                            Available for billing and ordering.
                                        </div>

                                    </div>

                                </label>


                            </div>

                        </div>


                    </div>


                    {{-- FORM FOOTER --}}
                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-6 py-4">

                        <a
                            href="{{ route('services.index') }}"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Save Service
                        </button>

                    </div>


                </div>

            </form>

        </div>

    </div>

</x-app-layout>