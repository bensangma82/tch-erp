
<x-app-layout>

    {{-- ========================================================= --}}
    {{-- PAGE HEADER --}}
    {{-- ========================================================= --}}

    <x-slot name="header">

        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div class="flex items-center gap-3">

                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-blue-100 bg-blue-50 text-blue-700">

                    <svg class="h-6 w-6"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24"
                         stroke-width="1.8"
                         stroke-linecap="round"
                         stroke-linejoin="round">

                        <rect x="4" y="11" width="3" height="9" rx="1"/>
                        <rect x="10.5" y="5" width="3" height="15" rx="1"/>
                        <rect x="17" y="8" width="3" height="12" rx="1"/>

                    </svg>

                </div>

                <div>

                    <h2 class="text-xl font-bold tracking-tight text-slate-900">
                        Accounting Reports
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Accrual-based financial reports generated from
                        TCH ERP journal entries.
                    </p>

                </div>

            </div>

            <div class="flex flex-wrap items-center gap-2">

                <a href="{{ route('finance.accounting-reports.general-ledger') }}"
                   class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-800">

                    <svg class="h-4 w-4"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24"
                         stroke-width="1.8">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M8 3h8l4 4v14H8a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3Z"/>

                        <path stroke-linecap="round"
                              d="M9 12h7M9 16h7"/>

                    </svg>

                    Open General Ledger

                </a>

            </div>

        </div>

    </x-slot>


    <div class="py-6">

        <div class="mx-auto max-w-screen-2xl space-y-6 px-4 sm:px-6 lg:px-8">


            {{-- ================================================= --}}
            {{-- ACCOUNTING INFORMATION BANNER --}}
            {{-- ================================================= --}}

            <div class="rounded-xl border border-blue-200 bg-blue-50/80 px-5 py-4">

                <div class="flex items-start gap-3">

                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">

                        <svg class="h-5 w-5"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24"
                             stroke-width="1.8">

                            <circle cx="12" cy="12" r="9"/>
                            <path stroke-linecap="round" d="M12 11v5m0-8h.01"/>

                        </svg>

                    </div>

                    <div>

                        <h3 class="text-sm font-bold text-blue-900">
                            ERP Accounting Reports
                        </h3>

                        <p class="mt-1 text-xs leading-6 text-blue-800 sm:text-sm">

                            These financial reports are generated from
                            <strong>posted journal entries in TCH ERP</strong>.

                            They operate independently of TallyPrime reports.

                            Figures remain provisional until opening balances,
                            journal postings, and reconciliation differences
                            have been reviewed and verified.

                        </p>

                    </div>

                </div>

            </div>


            {{-- ================================================= --}}
            {{-- REPORTING OVERVIEW --}}
            {{-- ================================================= --}}

            <section class="space-y-4">

                <div>

                    <h3 class="text-sm font-bold uppercase tracking-wide text-slate-800">
                        Reporting Overview
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Available financial statements and the basis of accounting.
                    </p>

                </div>


                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">


                    {{-- REPORTS AVAILABLE --}}

                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                        <div class="flex items-start justify-between gap-3">

                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-700">

                                <svg class="h-6 w-6"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.8">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M8 3h8l4 4v14H8a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3Z"/>

                                    <path stroke-linecap="round"
                                          d="M9 12h7M9 16h7"/>

                                </svg>

                            </div>

                            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-semibold text-blue-700">
                                Available
                            </span>

                        </div>

                        <p class="mt-4 text-xs font-semibold text-slate-500">
                            Financial Reports
                        </p>

                        <p class="mt-1 text-3xl font-bold tabular-nums text-slate-900">
                            4
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Core accrual accounting reports
                        </p>

                    </div>


                    {{-- ACCOUNTING BASIS --}}

                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">

                            <svg class="h-6 w-6"
                                 fill="none"
                                 stroke="currentColor"
                                 viewBox="0 0 24 24"
                                 stroke-width="1.8">

                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      d="M4 5.5C4 4.7 4.7 4 5.5 4H12v16H5.5A1.5 1.5 0 0 0 4 21V5.5ZM20 5.5c0-.8-.7-1.5-1.5-1.5H12v16h6.5A1.5 1.5 0 0 1 20 21V5.5Z"/>

                            </svg>

                        </div>

                        <p class="mt-4 text-xs font-semibold text-slate-500">
                            Accounting Basis
                        </p>

                        <p class="mt-2 text-lg font-bold text-slate-900">
                            Accrual
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Posted General Ledger journal entries
                        </p>

                    </div>


                    {{-- REPORTING STATUS --}}

                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-700">

                            <svg class="h-6 w-6"
                                 fill="none"
                                 stroke="currentColor"
                                 viewBox="0 0 24 24"
                                 stroke-width="1.8">

                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      d="M12 3 2.5 20h19L12 3Z"/>

                                <path stroke-linecap="round"
                                      d="M12 9v5m0 3h.01"/>

                            </svg>

                        </div>

                        <p class="mt-4 text-xs font-semibold text-slate-500">
                            Financial Statement Status
                        </p>

                        <p class="mt-2 text-lg font-bold text-amber-700">
                            Provisional
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            Subject to opening-balance reconciliation
                        </p>

                    </div>


                    {{-- INTEGRATION --}}

                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-700">

                            <svg class="h-6 w-6"
                                 fill="none"
                                 stroke="currentColor"
                                 viewBox="0 0 24 24"
                                 stroke-width="1.8">

                                <rect x="3" y="5" width="8" height="7" rx="1"/>
                                <rect x="13" y="12" width="8" height="7" rx="1"/>

                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      d="M7 16v3h6m4-10V5h-6"/>

                            </svg>

                        </div>

                        <p class="mt-4 text-xs font-semibold text-slate-500">
                            Tally Integration
                        </p>

                        <p class="mt-2 text-lg font-bold text-slate-900">
                            Separate Reports
                        </p>

                        <p class="mt-1 text-xs text-slate-500">
                            ERP and Tally balances require reconciliation
                        </p>

                    </div>

                </div>

            </section>


            {{-- ================================================= --}}
            {{-- FINANCIAL REPORTS --}}
            {{-- ================================================= --}}

            <section class="space-y-4">

                <div class="flex flex-wrap items-center justify-between gap-3">

                    <div>

                        <h3 class="text-lg font-bold text-slate-900">
                            Financial Reports
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            View and analyze the hospital's financial
                            data from posted ERP journal entries.
                        </p>

                    </div>

                    <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600">
                        4 Reports Available
                    </span>

                </div>


                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">


                    {{-- ========================================= --}}
                    {{-- GENERAL LEDGER --}}
                    {{-- ========================================= --}}

                    <div class="group flex flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-blue-300 hover:shadow-md">

                        <div class="flex items-start gap-4">

                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-700">

                                <svg class="h-8 w-8"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.7">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M12 6c-2-1.5-5-2-9-1.5V19c4-.5 7 0 9 1.5C14 19 17 18.5 21 19V4.5C17 4 14 4.5 12 6Z"/>

                                    <path stroke-linecap="round" d="M12 6v14.5"/>

                                </svg>

                            </div>

                            <div class="min-w-0">

                                <div class="text-[11px] font-bold uppercase tracking-wide text-blue-700">
                                    General Ledger
                                </div>

                                <h4 class="mt-1 text-lg font-bold text-slate-900">
                                    Account Ledger
                                </h4>

                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    View transactions for individual GL accounts,
                                    including opening balances, debit and credit movements,
                                    running balances, and closing balances.
                                </p>

                            </div>

                        </div>


                        <div class="mt-6 grid grid-cols-1 gap-3 border-t border-slate-100 pt-5 sm:grid-cols-3">

                            <div>
                                <p class="text-[11px] font-bold text-slate-700">
                                    Basis
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Posted Journal Entries
                                </p>
                            </div>

                            <div>
                                <p class="text-[11px] font-bold text-slate-700">
                                    Date Scope
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Selected Period
                                </p>
                            </div>

                            <div>
                                <p class="text-[11px] font-bold text-slate-700">
                                    Purpose
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Account Activity
                                </p>
                            </div>

                        </div>


                        <div class="mt-auto pt-6">

                            <a href="{{ route('finance.accounting-reports.general-ledger') }}"
                               class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-blue-800">

                                View General Ledger

                                <svg class="h-4 w-4"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24"
                                     stroke-width="2">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M5 12h14m-6-6 6 6-6 6"/>

                                </svg>

                            </a>

                        </div>

                    </div>


                    {{-- ========================================= --}}
                    {{-- TRIAL BALANCE --}}
                    {{-- ========================================= --}}

                    <div class="group flex flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-violet-300 hover:shadow-md">

                        <div class="flex items-start gap-4">

                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-violet-50 text-violet-700">

                                <svg class="h-8 w-8"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.7">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M12 3v18M5 7h14M5 7l-3 6h6L5 7Zm14 0-3 6h6l-3-6ZM6 21h12"/>

                                </svg>

                            </div>

                            <div class="min-w-0">

                                <div class="text-[11px] font-bold uppercase tracking-wide text-violet-700">
                                    Trial Balance
                                </div>

                                <h4 class="mt-1 text-lg font-bold text-slate-900">
                                    Trial Balance
                                </h4>

                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Review closing debit and credit balances
                                    of all GL accounts as of a selected date
                                    and verify whether total debits equal total credits.
                                </p>

                            </div>

                        </div>


                        <div class="mt-6 grid grid-cols-1 gap-3 border-t border-slate-100 pt-5 sm:grid-cols-3">

                            <div>
                                <p class="text-[11px] font-bold text-slate-700">
                                    Basis
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Posted Journal Entries
                                </p>
                            </div>

                            <div>
                                <p class="text-[11px] font-bold text-slate-700">
                                    Date Scope
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    As of Selected Date
                                </p>
                            </div>

                            <div>
                                <p class="text-[11px] font-bold text-slate-700">
                                    Purpose
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Verify Ledger Integrity
                                </p>
                            </div>

                        </div>


                        <div class="mt-auto pt-6">

                            <a href="{{ route('finance.accounting-reports.trial-balance') }}"
                               class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-blue-800">

                                View Trial Balance

                                <svg class="h-4 w-4"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24"
                                     stroke-width="2">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M5 12h14m-6-6 6 6-6 6"/>

                                </svg>

                            </a>

                        </div>

                    </div>


                    {{-- ========================================= --}}
                    {{-- INCOME AND EXPENDITURE --}}
                    {{-- ========================================= --}}

                    <div class="group flex flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-emerald-300 hover:shadow-md">

                        <div class="flex items-start gap-4">

                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">

                                <svg class="h-8 w-8"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.7">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M4 4v16h16M8 16v-4m5 4V7m5 9v-7"/>

                                </svg>

                            </div>

                            <div class="min-w-0">

                                <div class="text-[11px] font-bold uppercase tracking-wide text-emerald-700">
                                    Income &amp; Expenditure
                                </div>

                                <h4 class="mt-1 text-lg font-bold text-slate-900">
                                    Income &amp; Expenditure Statement
                                </h4>

                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Review recognized income and expenses
                                    for a selected period and calculate
                                    the hospital's operating surplus or deficit.
                                </p>

                            </div>

                        </div>


                        <div class="mt-6 grid grid-cols-1 gap-3 border-t border-slate-100 pt-5 sm:grid-cols-3">

                            <div>
                                <p class="text-[11px] font-bold text-slate-700">
                                    Basis
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Posted Journal Entries
                                </p>
                            </div>

                            <div>
                                <p class="text-[11px] font-bold text-slate-700">
                                    Date Scope
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Selected Period
                                </p>
                            </div>

                            <div>
                                <p class="text-[11px] font-bold text-slate-700">
                                    Purpose
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Operating Performance
                                </p>
                            </div>

                        </div>


                        <div class="mt-auto pt-6">

                            <a href="{{ route('finance.accounting-reports.income-expenditure') }}"
                               class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-blue-800">

                                View Income &amp; Expenditure

                                <svg class="h-4 w-4"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24"
                                     stroke-width="2">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M5 12h14m-6-6 6 6-6 6"/>

                                </svg>

                            </a>

                        </div>

                    </div>


                    {{-- ========================================= --}}
                    {{-- BALANCE SHEET --}}
                    {{-- ========================================= --}}

                    <div class="group flex flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:border-rose-300 hover:shadow-md">

                        <div class="flex items-start gap-4">

                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-rose-50 text-rose-700">

                                <svg class="h-8 w-8"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.7">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M7 3h8l4 4v14H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/>

                                    <path stroke-linecap="round"
                                          d="M9 12h6M9 16h6M15 3v4h4"/>

                                </svg>

                            </div>

                            <div class="min-w-0">

                                <div class="text-[11px] font-bold uppercase tracking-wide text-rose-700">
                                    Balance Sheet
                                </div>

                                <h4 class="mt-1 text-lg font-bold text-slate-900">
                                    Balance Sheet
                                </h4>

                                <p class="mt-2 text-sm leading-6 text-slate-600">
                                    Review assets, liabilities, equity,
                                    and accumulated surplus or deficit,
                                    with a balance reconciliation as of
                                    a selected date.
                                </p>

                            </div>

                        </div>


                        <div class="mt-6 grid grid-cols-1 gap-3 border-t border-slate-100 pt-5 sm:grid-cols-3">

                            <div>
                                <p class="text-[11px] font-bold text-slate-700">
                                    Basis
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Posted Journal Entries
                                </p>
                            </div>

                            <div>
                                <p class="text-[11px] font-bold text-slate-700">
                                    Date Scope
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    As of Selected Date
                                </p>
                            </div>

                            <div>
                                <p class="text-[11px] font-bold text-slate-700">
                                    Purpose
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    Financial Position
                                </p>
                            </div>

                        </div>


                        <div class="mt-auto pt-6">

                            <a href="{{ route('finance.accounting-reports.balance-sheet') }}"
                               class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 text-xs font-semibold text-white transition hover:bg-blue-800">

                                View Balance Sheet

                                <svg class="h-4 w-4"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24"
                                     stroke-width="2">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M5 12h14m-6-6 6 6-6 6"/>

                                </svg>

                            </a>

                        </div>

                    </div>

                </div>

            </section>


            {{-- ================================================= --}}
            {{-- ACCOUNTING CONTROLS AND INFORMATION --}}
            {{-- ================================================= --}}

            <section class="space-y-4">

                <div>

                    <h3 class="text-sm font-bold uppercase tracking-wide text-slate-800">
                        Accounting Controls &amp; Information
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Important considerations before using ERP reports
                        for management and statutory reporting.
                    </p>

                </div>


                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">


                    {{-- OPENING BALANCES --}}

                    <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-5">

                        <div class="flex items-start gap-3">

                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">

                                <svg class="h-5 w-5"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.8">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M12 3 2.5 20h19L12 3Z"/>

                                    <path stroke-linecap="round"
                                          d="M12 9v5m0 3h.01"/>

                                </svg>

                            </span>

                            <div>

                                <h4 class="text-sm font-bold text-amber-900">
                                    Opening Balances
                                </h4>

                                <p class="mt-2 text-xs leading-5 text-amber-800">
                                    Verify opening balances before relying
                                    on the Trial Balance or Balance Sheet
                                    for final financial reporting.
                                </p>

                            </div>

                        </div>

                    </div>


                    {{-- RECONCILIATION --}}

                    <div class="rounded-xl border border-blue-200 bg-blue-50/60 p-5">

                        <div class="flex items-start gap-3">

                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">

                                <svg class="h-5 w-5"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.8">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M4 7h14m-4-4 4 4-4 4M20 17H6m4-4-4 4 4 4"/>

                                </svg>

                            </span>

                            <div>

                                <h4 class="text-sm font-bold text-blue-900">
                                    ERP–Tally Reconciliation
                                </h4>

                                <p class="mt-2 text-xs leading-5 text-blue-800">
                                    Compare ERP journal entries with imported
                                    TallyPrime transactions and investigate
                                    unresolved differences.
                                </p>

                                <a href="{{ url('/admin/finance/tally/reconciliation') }}"
                                   class="mt-3 inline-flex items-center gap-1 text-xs font-bold text-blue-700 hover:underline">

                                    Open Reconciliation

                                    <span>→</span>

                                </a>

                            </div>

                        </div>

                    </div>


                    {{-- ACCOUNTING BASIS --}}

                    <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-5">

                        <div class="flex items-start gap-3">

                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">

                                <svg class="h-5 w-5"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24"
                                     stroke-width="1.8">

                                    <circle cx="12" cy="12" r="9"/>

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="m8 12 3 3 5-6"/>

                                </svg>

                            </span>

                            <div>

                                <h4 class="text-sm font-bold text-emerald-900">
                                    About These Reports
                                </h4>

                                <p class="mt-2 text-xs leading-5 text-emerald-800">
                                    These reports reflect ERP General Ledger
                                    postings. They do not automatically represent
                                    the balances in TallyPrime.
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

            </section>


            {{-- ================================================= --}}
            {{-- FOOTER --}}
            {{-- ================================================= --}}

            <div class="rounded-xl border border-slate-200 bg-white px-5 py-4">

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                    <div>

                        <p class="text-xs font-bold text-slate-800">
                            TCH ERP Financial Reporting
                        </p>

                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Financial figures are derived from posted ERP
                            accounting journals and remain subject to
                            reconciliation and review.
                        </p>

                    </div>

                    <span class="inline-flex w-fit items-center rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-600">
                        Accrual Accounting
                    </span>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>
