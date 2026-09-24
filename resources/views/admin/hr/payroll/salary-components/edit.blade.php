<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <div class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-700">
                    Human Resources · Payroll
                </div>

                <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                    Edit Salary Component
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Update the configuration of an existing payroll component.
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


            {{-- VALIDATION ERRORS --}}

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



            {{-- COMPONENT SUMMARY --}}

            <section class="overflow-hidden rounded-2xl border border-cyan-100 bg-gradient-to-br from-cyan-50 via-white to-slate-50 shadow-sm">

                <div class="px-6 py-6">

                    <div class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-700">
                        Payroll Master
                    </div>

                    <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">

                        <div>

                            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                                {{ $component->name }}
                            </h1>

                            <div class="mt-1 font-mono text-sm font-semibold text-slate-500">
                                {{ $component->code }}
                            </div>

                        </div>


                        <div>

                            @if ($component->is_active)

                                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
                                    Active
                                </span>

                            @else

                                <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">
                                    Inactive
                                </span>

                            @endif

                        </div>

                    </div>


                    <p class="mt-3 max-w-4xl text-sm leading-6 text-slate-600">
                        Changes affect future salary structures and payroll calculations.
                        Historical payroll entries retain their stored component snapshots.
                    </p>

                </div>

            </section>



            {{-- EDIT FORM --}}

            <form
                method="POST"
                action="{{ route('admin.hr.payroll.salary-components.update', $component) }}"
            >

                @csrf
                @method('PUT')

                @include('admin.hr.payroll.salary-components._form')

            </form>


        </div>

    </div>

</x-app-layout>