<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">
                    Human Resources
                </div>

                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                    New Leave Request
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Create a leave application for an active employee.
                </p>
            </div>

            <a
                href="{{ route('admin.hr.leave-requests.index') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
            >
                Leave Request Register
            </a>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

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
                        Leave Application
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Total leave days are calculated as inclusive calendar days.
                    </p>

                </div>


                <form
                    method="POST"
                    action="{{ route('admin.hr.leave-requests.store') }}"
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

                                <option value="">
                                    Select employee
                                </option>

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


                        <div class="md:col-span-2">

                            <label class="block text-sm font-semibold text-slate-700">
                                Leave Type
                            </label>

                            <select
                                name="leave_type_id"
                                required
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                                <option value="">
                                    Select leave type
                                </option>

                                @foreach ($leaveTypes as $leaveType)

                                    <option
                                        value="{{ $leaveType->id }}"
                                        @selected((string) old('leave_type_id') === (string) $leaveType->id)
                                    >
                                        {{ $leaveType->name }}
                                        ({{ $leaveType->code }})
                                        {{ $leaveType->is_paid ? '- Paid' : '- Unpaid' }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                Start Date
                            </label>

                            <input
                                type="date"
                                name="start_date"
                                id="start_date"
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
                                id="end_date"
                                value="{{ old('end_date') }}"
                                required
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                        </div>


                        <div class="md:col-span-2">

                            <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3">

                                <div class="text-xs font-semibold uppercase tracking-wide text-blue-700">
                                    Calculated Duration
                                </div>

                                <div
                                    id="durationText"
                                    class="mt-1 text-lg font-bold text-blue-900"
                                >
                                    Select start and end dates
                                </div>

                            </div>

                        </div>


                        <div class="md:col-span-2">

                            <label class="block text-sm font-semibold text-slate-700">
                                Reason
                            </label>

                            <textarea
                                name="reason"
                                rows="4"
                                maxlength="2000"
                                placeholder="Optional reason for leave"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >{{ old('reason') }}</textarea>

                        </div>

                    </div>


                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Weekly offs and public holidays are currently included in the leave duration because hospital-specific exclusion rules have not yet been configured.
                    </div>


                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                        <a
                            href="{{ route('admin.hr.leave-requests.index') }}"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                        >
                            Submit Leave Request
                        </button>

                    </div>

                </form>

            </section>

        </div>

    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const startDate =
                document.getElementById('start_date');

            const endDate =
                document.getElementById('end_date');

            const durationText =
                document.getElementById('durationText');


            function updateDuration() {

                if (
                    ! startDate.value
                    || ! endDate.value
                ) {
                    durationText.textContent =
                        'Select start and end dates';

                    return;
                }


                const start =
                    new Date(
                        startDate.value
                        + 'T00:00:00'
                    );

                const end =
                    new Date(
                        endDate.value
                        + 'T00:00:00'
                    );


                if (end < start) {

                    durationText.textContent =
                        'End date cannot be before start date';

                    return;
                }


                const millisecondsPerDay =
                    1000 * 60 * 60 * 24;

                const days =
                    Math.floor(
                        (
                            end.getTime()
                            - start.getTime()
                        )
                        / millisecondsPerDay
                    )
                    + 1;


                durationText.textContent =
                    days
                    + (
                        days === 1
                            ? ' day'
                            : ' days'
                    );
            }


            startDate.addEventListener(
                'change',
                updateDuration
            );

            endDate.addEventListener(
                'change',
                updateDuration
            );

            updateDuration();
        });
    </script>

</x-app-layout>
