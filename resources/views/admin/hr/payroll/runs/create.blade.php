<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">
                Create Monthly Payroll
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Create a payroll period before calculating employee salaries.
            </p>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">

            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">
                    <h3 class="font-semibold text-slate-900">
                        Payroll Period
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Only one payroll run can be created for each month.
                    </p>
                </div>

                <form
                    method="POST"
                    action="{{ route('admin.hr.payroll.runs.store') }}"
                    class="space-y-6 p-6"
                >
                    @csrf

                    @if ($errors->any())
                        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3">
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

                    <div class="grid gap-5 md:grid-cols-2">

                        {{-- Month --}}
                        <div>
                            <label
                                for="month"
                                class="mb-1 block text-sm font-medium text-slate-700"
                            >
                                Payroll Month
                                <span class="text-red-600">*</span>
                            </label>

                            <select
                                id="month"
                                name="month"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-cyan-600 focus:ring-cyan-600"
                            >
                                @for ($month = 1; $month <= 12; $month++)
                                    <option
                                        value="{{ $month }}"
                                        @selected(
                                            (int) old('month', now()->month) === $month
                                        )
                                    >
                                        {{ \Illuminate\Support\Carbon::create(null, $month, 1)->format('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>

                        {{-- Year --}}
                        <div>
                            <label
                                for="year"
                                class="mb-1 block text-sm font-medium text-slate-700"
                            >
                                Payroll Year
                                <span class="text-red-600">*</span>
                            </label>

                            <input
                                type="number"
                                id="year"
                                name="year"
                                min="2000"
                                max="2100"
                                required
                                value="{{ old('year', now()->year) }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-cyan-600 focus:ring-cyan-600"
                            >
                        </div>

                    </div>

                    {{-- Description --}}
                    <div>
                        <label
                            for="description"
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            Description
                        </label>

                        <input
                            type="text"
                            id="description"
                            name="description"
                            maxlength="255"
                            value="{{ old('description') }}"
                            placeholder="Optional — e.g. September 2026 Payroll"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-cyan-600 focus:ring-cyan-600"
                        >

                        <p class="mt-1 text-xs text-slate-500">
                            If left blank, the system will automatically use the payroll month and year.
                        </p>
                    </div>

                    {{-- Remarks --}}
                    <div>
                        <label
                            for="remarks"
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            Remarks
                        </label>

                        <textarea
                            id="remarks"
                            name="remarks"
                            rows="3"
                            maxlength="2000"
                            placeholder="Optional payroll notes"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-cyan-600 focus:ring-cyan-600"
                        >{{ old('remarks') }}</textarea>
                    </div>

                    {{-- Information --}}
                    <div class="rounded-lg border border-cyan-200 bg-cyan-50 px-4 py-4">
                        <div class="font-semibold text-cyan-900">
                            What happens next?
                        </div>

                        <div class="mt-2 text-sm leading-6 text-cyan-800">
                            Creating the payroll period does not calculate or approve salaries.
                            The payroll will initially remain in
                            <strong>Draft</strong> status. You can review the period before
                            running the salary calculation.
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">

                        <a
                            href="{{ route('admin.hr.payroll.runs.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg px-5 py-2 text-sm font-semibold shadow-sm"
                            style="background-color: #0e7490 !important; color: #ffffff !important;"
                        >
                            Create Payroll Run
                        </button>

                    </div>
                </form>

            </div>

        </div>
    </div>
</x-app-layout>