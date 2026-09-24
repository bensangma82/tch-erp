<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">
                    Human Resources · Payroll
                </div>

                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                    Add Salary Component
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Create an earning or deduction component for employee salary structures.
                </p>

            </div>

            <a
                href="{{ route('admin.hr.payroll.salary-components.index') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
            >
                Back to Salary Components
            </a>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            {{-- ========================================================= --}}
            {{-- VALIDATION ERRORS --}}
            {{-- ========================================================= --}}

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
                        Payroll Master
                    </div>

                    <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">
                        New Salary Component
                    </h1>

                    <p class="mt-2 max-w-4xl text-sm leading-6 text-slate-600">
                        Configure the component once here. The amount or percentage can later
                        be customized for individual employees through their salary structure.
                    </p>

                </div>

            </section>



            {{-- ========================================================= --}}
            {{-- CREATE FORM --}}
            {{-- ========================================================= --}}

            <form
                method="POST"
                action="{{ route('admin.hr.payroll.salary-components.store') }}"
            >

                @csrf

                @include('admin.hr.payroll.salary-components._form')

            </form>


        </div>

    </div>

</x-app-layout>