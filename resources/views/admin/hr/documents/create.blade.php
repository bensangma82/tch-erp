<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">
                    Human Resources
                </div>

                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                    Upload Employee Document
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Files are stored privately and accessed through authenticated HR routes.
                </p>
            </div>


            <a
                href="{{ route('admin.hr.documents.index') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
            >
                Document Register
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
                        Document Details
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Accepted file types: PDF, JPG, JPEG and PNG. Maximum size: 10 MB.
                    </p>
                </div>


                <form
                    method="POST"
                    action="{{ route('admin.hr.documents.store') }}"
                    enctype="multipart/form-data"
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
                                        @selected(
                                            (string) old(
                                                'employee_id',
                                                $selectedEmployeeId
                                            ) === (string) $employee->id
                                        )
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
                                Document Type
                            </label>

                            <select
                                name="document_type"
                                required
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >
                                <option value="">Select type</option>

                                @foreach ($documentTypes as $type)
                                    <option
                                        value="{{ $type }}"
                                        @selected(old('document_type') === $type)
                                    >
                                        {{ ucwords(str_replace('_', ' ', $type)) }}
                                    </option>
                                @endforeach
                            </select>

                        </div>


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                Document Title
                            </label>

                            <input
                                type="text"
                                name="title"
                                value="{{ old('title') }}"
                                required
                                maxlength="200"
                                placeholder="e.g. Meghalaya Medical Council Registration"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                        </div>


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                Reference / Registration No.
                            </label>

                            <input
                                type="text"
                                name="reference_no"
                                value="{{ old('reference_no') }}"
                                maxlength="100"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                        </div>


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                Issue Date
                            </label>

                            <input
                                type="date"
                                name="issue_date"
                                value="{{ old('issue_date') }}"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                        </div>


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                Expiry Date
                            </label>

                            <input
                                type="date"
                                name="expiry_date"
                                value="{{ old('expiry_date') }}"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                            <p class="mt-1 text-xs text-slate-400">
                                Leave blank for documents without an expiry date.
                            </p>

                        </div>


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                File
                            </label>

                            <input
                                type="file"
                                name="document_file"
                                required
                                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                class="mt-2 block w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-700 shadow-sm file:mr-4 file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200"
                            >

                            <p class="mt-1 text-xs text-slate-400">
                                Maximum file size: 10 MB.
                            </p>

                        </div>


                        <div class="md:col-span-2">

                            <label class="block text-sm font-semibold text-slate-700">
                                HR Remarks
                            </label>

                            <textarea
                                name="remarks"
                                rows="3"
                                maxlength="5000"
                                placeholder="Optional internal HR notes"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >{{ old('remarks') }}</textarea>

                        </div>

                    </div>


                    <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-4">

                        <div class="text-sm font-semibold text-blue-900">
                            Verification workflow
                        </div>

                        <div class="mt-1 text-xs leading-5 text-blue-700">
                            New uploads are marked Pending. HR can review the scanned document
                            and then Verify or Reject it from the Document Register.
                        </div>

                    </div>


                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                        <a
                            href="{{ route('admin.hr.documents.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                        >
                            Upload Document
                        </button>

                    </div>

                </form>

            </section>

        </div>

    </div>

</x-app-layout>
