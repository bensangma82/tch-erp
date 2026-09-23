<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">
                    Human Resources
                </div>

                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                    Leave Type Master
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Configure leave categories, annual entitlement, carry-forward rules and approval requirements.
                </p>

            </div>

            <a
                href="{{ route('admin.hr.index') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
            >
                Back to HR Dashboard
            </a>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- FLASH / VALIDATION --}}
            {{-- ========================================================= --}}

            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('success') }}
                </div>

            @endif


            @if ($errors->any())

                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-4">

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



            {{-- ========================================================= --}}
            {{-- INTRO --}}
            {{-- ========================================================= --}}

            <section class="overflow-hidden rounded-2xl border border-cyan-100 bg-gradient-to-br from-cyan-50 via-white to-slate-50 shadow-sm">

                <div class="px-6 py-6">

                    <div class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-700">
                        Leave Configuration
                    </div>

                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">
                        Hospital Leave Categories
                    </h1>

                    <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">
                        Entitlements are currently configurable by HR. Leave types should normally be deactivated rather than deleted so historical leave records remain intact.
                    </p>

                </div>

            </section>



            {{-- ========================================================= --}}
            {{-- ADD LEAVE TYPE --}}
            {{-- ========================================================= --}}

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Add Leave Type
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Create a new leave category for future leave requests and balances.
                    </p>

                </div>


                <form
                    method="POST"
                    action="{{ route('admin.hr.leave-types.store') }}"
                    class="p-6"
                >

                    @csrf

                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                Code
                            </label>

                            <input
                                type="text"
                                name="code"
                                value="{{ old('code') }}"
                                required
                                maxlength="30"
                                placeholder="e.g. CL"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                        </div>


                        <div class="xl:col-span-2">

                            <label class="block text-sm font-semibold text-slate-700">
                                Leave Name
                            </label>

                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                required
                                maxlength="120"
                                placeholder="e.g. Casual Leave"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                        </div>


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                Display Order
                            </label>

                            <input
                                type="number"
                                name="sort_order"
                                value="{{ old('sort_order', 0) }}"
                                min="0"
                                max="9999"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                        </div>


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                Annual Entitlement
                            </label>

                            <input
                                type="number"
                                name="default_annual_entitlement"
                                value="{{ old('default_annual_entitlement', 0) }}"
                                min="0"
                                max="366"
                                step="0.5"
                                required
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                            <p class="mt-1 text-xs text-slate-400">
                                Days per year
                            </p>

                        </div>


                        <div>

                            <label class="block text-sm font-semibold text-slate-700">
                                Maximum Carry Forward
                            </label>

                            <input
                                type="number"
                                name="max_carry_forward"
                                value="{{ old('max_carry_forward', 0) }}"
                                min="0"
                                max="366"
                                step="0.5"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >

                            <p class="mt-1 text-xs text-slate-400">
                                Used only when carry-forward is enabled
                            </p>

                        </div>


                        <div class="md:col-span-2">

                            <label class="block text-sm font-semibold text-slate-700">
                                Description
                            </label>

                            <textarea
                                name="description"
                                rows="3"
                                maxlength="2000"
                                class="mt-2 block w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                placeholder="Optional description or policy note"
                            >{{ old('description') }}</textarea>

                        </div>

                    </div>


                    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">

                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">

                            <input type="hidden" name="is_paid" value="0">

                            <input
                                type="checkbox"
                                name="is_paid"
                                value="1"
                                @checked(old('is_paid', true))
                                class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-500"
                            >

                            <span class="text-sm font-medium text-slate-700">
                                Paid Leave
                            </span>

                        </label>


                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">

                            <input type="hidden" name="allow_carry_forward" value="0">

                            <input
                                type="checkbox"
                                name="allow_carry_forward"
                                value="1"
                                @checked(old('allow_carry_forward'))
                                class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-500"
                            >

                            <span class="text-sm font-medium text-slate-700">
                                Allow Carry Forward
                            </span>

                        </label>


                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">

                            <input type="hidden" name="requires_approval" value="0">

                            <input
                                type="checkbox"
                                name="requires_approval"
                                value="1"
                                @checked(old('requires_approval', true))
                                class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-500"
                            >

                            <span class="text-sm font-medium text-slate-700">
                                Requires Approval
                            </span>

                        </label>


                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">

                            <input type="hidden" name="is_active" value="0">

                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                @checked(old('is_active', true))
                                class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-500"
                            >

                            <span class="text-sm font-medium text-slate-700">
                                Active
                            </span>

                        </label>

                    </div>


                    <div class="mt-6 flex justify-end">

                        <button
                            type="submit"
                            class="inline-flex items-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                        >
                            Add Leave Type
                        </button>

                    </div>

                </form>

            </section>



            {{-- ========================================================= --}}
            {{-- EXISTING LEAVE TYPES --}}
            {{-- ========================================================= --}}

            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <h3 class="font-semibold text-slate-900">
                                Existing Leave Types
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ $leaveTypes->count() }} leave type{{ $leaveTypes->count() === 1 ? '' : 's' }} configured.
                            </p>

                        </div>

                    </div>

                </div>


                <div class="space-y-4 p-6">

                    @forelse ($leaveTypes as $leaveType)

                        <form
                            method="POST"
                            action="{{ route('admin.hr.leave-types.update', $leaveType) }}"
                            class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5"
                        >

                            @csrf
                            @method('PUT')


                            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">


                                <div class="min-w-0 flex-1">

                                    <div class="flex flex-wrap items-center gap-2">

                                        <div class="text-lg font-bold text-slate-900">
                                            {{ $leaveType->name }}
                                        </div>

                                        <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-bold text-slate-700">
                                            {{ $leaveType->code }}
                                        </span>

                                        @if ($leaveType->is_active)

                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                Active
                                            </span>

                                        @else

                                            <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                                Inactive
                                            </span>

                                        @endif


                                        @if ($leaveType->is_paid)

                                            <span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                                Paid
                                            </span>

                                        @else

                                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                                Unpaid
                                            </span>

                                        @endif

                                    </div>


                                    @if ($leaveType->description)

                                        <p class="mt-2 text-sm leading-6 text-slate-500">
                                            {{ $leaveType->description }}
                                        </p>

                                    @endif

                                </div>


                                <div class="text-xs font-medium text-slate-400">
                                    ID {{ $leaveType->id }}
                                </div>

                            </div>



                            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">


                                <div>

                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Code
                                    </label>

                                    <input
                                        type="text"
                                        name="code"
                                        value="{{ $leaveType->code }}"
                                        required
                                        maxlength="30"
                                        class="mt-2 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                    >

                                </div>


                                <div class="xl:col-span-2">

                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Leave Name
                                    </label>

                                    <input
                                        type="text"
                                        name="name"
                                        value="{{ $leaveType->name }}"
                                        required
                                        maxlength="120"
                                        class="mt-2 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                    >

                                </div>


                                <div>

                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Display Order
                                    </label>

                                    <input
                                        type="number"
                                        name="sort_order"
                                        value="{{ $leaveType->sort_order }}"
                                        min="0"
                                        max="9999"
                                        class="mt-2 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                    >

                                </div>


                                <div>

                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Annual Entitlement
                                    </label>

                                    <input
                                        type="number"
                                        name="default_annual_entitlement"
                                        value="{{ $leaveType->default_annual_entitlement }}"
                                        min="0"
                                        max="366"
                                        step="0.5"
                                        required
                                        class="mt-2 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                    >

                                </div>


                                <div>

                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Max Carry Forward
                                    </label>

                                    <input
                                        type="number"
                                        name="max_carry_forward"
                                        value="{{ $leaveType->max_carry_forward }}"
                                        min="0"
                                        max="366"
                                        step="0.5"
                                        class="mt-2 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                    >

                                </div>


                                <div class="md:col-span-2">

                                    <label class="block text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Description
                                    </label>

                                    <textarea
                                        name="description"
                                        rows="2"
                                        maxlength="2000"
                                        class="mt-2 block w-full rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                                    >{{ $leaveType->description }}</textarea>

                                </div>

                            </div>



                            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">

                                <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">

                                    <input type="hidden" name="is_paid" value="0">

                                    <input
                                        type="checkbox"
                                        name="is_paid"
                                        value="1"
                                        @checked($leaveType->is_paid)
                                        class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-500"
                                    >

                                    <span class="text-sm font-medium text-slate-700">
                                        Paid Leave
                                    </span>

                                </label>


                                <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">

                                    <input type="hidden" name="allow_carry_forward" value="0">

                                    <input
                                        type="checkbox"
                                        name="allow_carry_forward"
                                        value="1"
                                        @checked($leaveType->allow_carry_forward)
                                        class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-500"
                                    >

                                    <span class="text-sm font-medium text-slate-700">
                                        Carry Forward
                                    </span>

                                </label>


                                <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">

                                    <input type="hidden" name="requires_approval" value="0">

                                    <input
                                        type="checkbox"
                                        name="requires_approval"
                                        value="1"
                                        @checked($leaveType->requires_approval)
                                        class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-500"
                                    >

                                    <span class="text-sm font-medium text-slate-700">
                                        Requires Approval
                                    </span>

                                </label>


                                <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3">

                                    <input type="hidden" name="is_active" value="0">

                                    <input
                                        type="checkbox"
                                        name="is_active"
                                        value="1"
                                        @checked($leaveType->is_active)
                                        class="rounded border-slate-300 text-cyan-700 focus:ring-cyan-500"
                                    >

                                    <span class="text-sm font-medium text-slate-700">
                                        Active
                                    </span>

                                </label>

                            </div>


                            <div class="mt-5 flex justify-end">

                                <button
                                    type="submit"
                                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-100"
                                >
                                    Save Changes
                                </button>

                            </div>

                        </form>

                    @empty

                        <div class="rounded-xl border border-dashed border-slate-300 px-6 py-12 text-center">

                            <div class="font-semibold text-slate-700">
                                No leave types configured
                            </div>

                            <p class="mt-1 text-sm text-slate-500">
                                Add the first leave type using the form above.
                            </p>

                        </div>

                    @endforelse

                </div>

            </section>


        </div>

    </div>

</x-app-layout>
