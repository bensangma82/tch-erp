<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-xl font-semibold text-slate-900">
                    Edit Department
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Update department master information.
                </p>

            </div>

            <a
                href="{{ route('admin.departments.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
            >
                Back to Departments
            </a>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">


            @if ($errors->any())

                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-5">

                    <div class="font-semibold text-red-700">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-disc pl-5 text-sm text-red-600">

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
                action="{{ route('admin.departments.update', $department) }}"
                class="space-y-6"
            >

                @csrf
                @method('PUT')


                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">

                        <h3 class="font-semibold text-slate-900">
                            Department Information
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Changes here will affect department selection across the ERP.
                        </p>

                    </div>


                    <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">


                        <div>

                            <label
                                for="code"
                                class="mb-1 block text-sm font-semibold text-slate-700"
                            >
                                Department Code
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="code"
                                type="text"
                                name="code"
                                value="{{ old('code', $department->code) }}"
                                maxlength="50"
                                required
                                autocomplete="off"
                                class="w-full rounded-lg border-slate-300 uppercase shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>


                        <div>

                            <label
                                for="name"
                                class="mb-1 block text-sm font-semibold text-slate-700"
                            >
                                Department Name
                                <span class="text-red-500">*</span>
                            </label>

                            <input
                                id="name"
                                type="text"
                                name="name"
                                value="{{ old('name', $department->name) }}"
                                maxlength="255"
                                required
                                autocomplete="off"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>


                        <div>

                            <label
                                for="type"
                                class="mb-1 block text-sm font-semibold text-slate-700"
                            >
                                Department Type
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="type"
                                name="type"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option value="">
                                    Select Type
                                </option>

                                <option
                                    value="clinical"
                                    @selected(old('type', $department->type) === 'clinical')
                                >
                                    Clinical
                                </option>

                                <option
                                    value="diagnostic"
                                    @selected(old('type', $department->type) === 'diagnostic')
                                >
                                    Diagnostic
                                </option>

                                <option
                                    value="support"
                                    @selected(old('type', $department->type) === 'support')
                                >
                                    Support
                                </option>

                                <option
                                    value="administrative"
                                    @selected(old('type', $department->type) === 'administrative')
                                >
                                    Administrative
                                </option>

                            </select>

                        </div>


                        <div>

                            <label
                                for="is_active"
                                class="mb-1 block text-sm font-semibold text-slate-700"
                            >
                                Status
                                <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="is_active"
                                name="is_active"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option
                                    value="1"
                                    @selected((string) old('is_active', $department->is_active ? '1' : '0') === '1')
                                >
                                    Active
                                </option>

                                <option
                                    value="0"
                                    @selected((string) old('is_active', $department->is_active ? '1' : '0') === '0')
                                >
                                    Inactive
                                </option>

                            </select>

                        </div>


                        <div class="md:col-span-2">

                            <label
                                for="remarks"
                                class="mb-1 block text-sm font-semibold text-slate-700"
                            >
                                Remarks
                            </label>

                            <textarea
                                id="remarks"
                                name="remarks"
                                rows="4"
                                maxlength="2000"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >{{ old('remarks', $department->remarks) }}</textarea>

                        </div>

                    </div>

                </div>


                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                    <a
                        href="{{ route('admin.departments.index') }}"
                        class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                    >
                        Update Department
                    </button>

                </div>

            </form>

        </div>

    </div>

</x-app-layout>