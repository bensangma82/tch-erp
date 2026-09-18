<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Purchase Order
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $pharmacyPurchaseOrder->po_no }}
                </p>

            </div>


            <div class="flex flex-wrap gap-2">

                <a
                    href="{{ route('pharmacy.purchase-orders.index') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Purchase Orders
                </a>

                <button
                    type="button"
                    onclick="window.print()"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Print PO
                </button>

            </div>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif


            @if (session('error'))

                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium text-red-700">
                    {{ session('error') }}
                </div>

            @endif



            @php

                $statusLabel =
                    match ($pharmacyPurchaseOrder->status) {

                        'draft' =>
                            'Draft',

                        'approved' =>
                            'Approved',

                        'partially_received' =>
                            'Partially Received',

                        'received' =>
                            'Received',

                        'cancelled' =>
                            'Cancelled',

                        default =>
                            ucwords(
                                str_replace(
                                    '_',
                                    ' ',
                                    $pharmacyPurchaseOrder->status
                                )
                            ),
                    };


                $statusClass =
                    match ($pharmacyPurchaseOrder->status) {

                        'draft' =>
                            'bg-amber-50 text-amber-700',

                        'approved' =>
                            'bg-blue-50 text-blue-700',

                        'partially_received' =>
                            'bg-purple-50 text-purple-700',

                        'received' =>
                            'bg-emerald-50 text-emerald-700',

                        'cancelled' =>
                            'bg-red-50 text-red-700',

                        default =>
                            'bg-slate-100 text-slate-700',
                    };


                $canApprovePo =
                    auth()->user()?->hasPermission(
                        'pharmacy.po.approve'
                    ) ?? false;


                $canCreateGrn =
                    auth()->user()?->hasPermission(
                        'pharmacy.grn.create'
                    ) ?? false;

            @endphp



            {{-- ========================================================= --}}
            {{-- PO HEADER --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Purchase Order
                            </div>

                            <div class="mt-2 text-2xl font-bold text-slate-900">
                                {{ $pharmacyPurchaseOrder->po_no }}
                            </div>

                        </div>


                        <span class="inline-flex rounded-full px-4 py-2 text-sm font-semibold {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>

                    </div>

                </div>


                <div class="grid gap-6 p-6 md:grid-cols-2 lg:grid-cols-4">


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Supplier
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyPurchaseOrder->supplier?->name ?? '—' }}
                        </div>

                        @if ($pharmacyPurchaseOrder->supplier?->code)

                            <div class="mt-1 text-sm text-slate-500">
                                {{ $pharmacyPurchaseOrder->supplier->code }}
                            </div>

                        @endif

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            PO Date
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyPurchaseOrder->po_date?->format('d M Y') ?? '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Expected Delivery
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyPurchaseOrder->expected_delivery_date?->format('d M Y') ?? '—' }}
                        </div>

                    </div>


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Created By
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $pharmacyPurchaseOrder->createdBy?->name ?? 'System' }}
                        </div>

                    </div>


                    @if ($pharmacyPurchaseOrder->approved_at)

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Approved By
                            </div>

                            <div class="mt-2 font-bold text-slate-900">
                                {{ $pharmacyPurchaseOrder->approvedBy?->name ?? '—' }}
                            </div>

                        </div>


                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Approved At
                            </div>

                            <div class="mt-2 font-bold text-slate-900">
                                {{ $pharmacyPurchaseOrder->approved_at->format('d M Y, h:i A') }}
                            </div>

                        </div>

                    @endif


                    @if ($pharmacyPurchaseOrder->supplier?->gstin)

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Supplier GSTIN
                            </div>

                            <div class="mt-2 font-bold text-slate-900">
                                {{ $pharmacyPurchaseOrder->supplier->gstin }}
                            </div>

                        </div>

                    @endif


                    @if ($pharmacyPurchaseOrder->supplier?->drug_license_no)

                        <div>

                            <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Drug Licence
                            </div>

                            <div class="mt-2 font-bold text-slate-900">
                                {{ $pharmacyPurchaseOrder->supplier->drug_license_no }}
                            </div>

                        </div>

                    @endif

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- ITEMS --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Ordered Medicines
                    </h3>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Medicine
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Qty
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Unit Cost
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Discount
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    GST
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Taxable
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Total
                                </th>

                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Received
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100">

                            @foreach ($pharmacyPurchaseOrder->items as $item)

                                <tr>

                                    <td class="px-5 py-4">

                                        <div class="font-semibold text-slate-900">
                                            {{ $item->medicine_name }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-500">

                                            @if ($item->brand_name)
                                                {{ $item->brand_name }}
                                            @endif

                                            @if ($item->strength)
                                                · {{ $item->strength }}
                                            @endif

                                        </div>

                                    </td>


                                    <td class="px-4 py-4 text-right font-semibold text-slate-900">
                                        {{ number_format($item->quantity_ordered) }}
                                    </td>


                                    <td class="px-4 py-4 text-right text-slate-700">
                                        ₹{{ number_format((float) $item->unit_cost, 2) }}
                                    </td>


                                    <td class="px-4 py-4 text-right text-slate-700">

                                        {{ number_format((float) $item->discount_percent, 2) }}%

                                        @if ((float) $item->discount_amount > 0)

                                            <div class="mt-1 text-xs text-red-600">
                                                -₹{{ number_format((float) $item->discount_amount, 2) }}
                                            </div>

                                        @endif

                                    </td>


                                    <td class="px-4 py-4 text-right text-slate-700">
                                        {{ number_format((float) $item->gst_percent, 2) }}%
                                    </td>


                                    <td class="px-4 py-4 text-right font-medium text-slate-800">
                                        ₹{{ number_format((float) $item->taxable_amount, 2) }}
                                    </td>


                                    <td class="px-4 py-4 text-right font-bold text-slate-900">
                                        ₹{{ number_format((float) $item->line_total, 2) }}
                                    </td>


                                    <td class="px-4 py-4 text-right">

                                        <div class="font-semibold text-slate-900">
                                            {{ number_format($item->quantity_received) }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">
                                            of {{ number_format($item->quantity_ordered) }}
                                        </div>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- TOTALS --}}
            {{-- ========================================================= --}}

            <div class="grid gap-6 lg:grid-cols-2">


                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <h3 class="font-semibold text-slate-900">
                        Remarks
                    </h3>

                    <div class="mt-4 text-sm leading-6 text-slate-600">
                        {{ $pharmacyPurchaseOrder->remarks ?: 'No remarks.' }}
                    </div>

                </div>


                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                    <h3 class="font-semibold text-slate-900">
                        Purchase Summary
                    </h3>


                    <div class="mt-5 space-y-3">


                        <div class="flex justify-between text-sm">

                            <span class="text-slate-500">
                                Gross Subtotal
                            </span>

                            <span class="font-semibold text-slate-900">
                                ₹{{ number_format((float) $pharmacyPurchaseOrder->subtotal, 2) }}
                            </span>

                        </div>


                        <div class="flex justify-between text-sm">

                            <span class="text-slate-500">
                                Discount
                            </span>

                            <span class="font-semibold text-red-700">
                                ₹{{ number_format((float) $pharmacyPurchaseOrder->discount_amount, 2) }}
                            </span>

                        </div>


                        <div class="flex justify-between text-sm">

                            <span class="text-slate-500">
                                Taxable Amount
                            </span>

                            <span class="font-semibold text-slate-900">
                                ₹{{ number_format((float) $pharmacyPurchaseOrder->taxable_amount, 2) }}
                            </span>

                        </div>


                        <div class="flex justify-between text-sm">

                            <span class="text-slate-500">
                                CGST
                            </span>

                            <span class="font-semibold text-slate-900">
                                ₹{{ number_format((float) $pharmacyPurchaseOrder->cgst_amount, 2) }}
                            </span>

                        </div>


                        <div class="flex justify-between text-sm">

                            <span class="text-slate-500">
                                SGST
                            </span>

                            <span class="font-semibold text-slate-900">
                                ₹{{ number_format((float) $pharmacyPurchaseOrder->sgst_amount, 2) }}
                            </span>

                        </div>


                        <div class="flex justify-between text-sm">

                            <span class="text-slate-500">
                                IGST
                            </span>

                            <span class="font-semibold text-slate-900">
                                ₹{{ number_format((float) $pharmacyPurchaseOrder->igst_amount, 2) }}
                            </span>

                        </div>


                        <div class="flex justify-between border-t border-slate-200 pt-4">

                            <span class="font-bold text-slate-900">
                                Purchase Order Total
                            </span>

                            <span class="text-2xl font-bold text-blue-700">
                                ₹{{ number_format((float) $pharmacyPurchaseOrder->total_amount, 2) }}
                            </span>

                        </div>

                    </div>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- ACTIONS --}}
            {{-- ========================================================= --}}

            <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">


                <div class="text-xs leading-5 text-slate-500">

                    @if ($pharmacyPurchaseOrder->status === 'draft')

                        This Purchase Order is still a draft.

                        @if ($canApprovePo)
                            Approving it confirms the procurement request.
                        @else
                            Approval must be completed by an authorised user.
                        @endif


                    @elseif ($pharmacyPurchaseOrder->status === 'approved')

                        This Purchase Order is approved.

                        @if ($canCreateGrn)
                            It is ready for GRN receiving.
                        @else
                            Goods receiving must be completed by an authorised user.
                        @endif


                    @elseif ($pharmacyPurchaseOrder->status === 'partially_received')

                        This Purchase Order has been partially received.

                        @if ($canCreateGrn)
                            Additional goods may still be received.
                        @else
                            Further receiving requires GRN permission.
                        @endif


                    @elseif ($pharmacyPurchaseOrder->status === 'cancelled')

                        This Purchase Order has been cancelled.


                    @elseif ($pharmacyPurchaseOrder->status === 'received')

                        This Purchase Order has been fully received.


                    @else

                        Purchase Order status:
                        {{ $statusLabel }}

                    @endif

                </div>


                <div class="flex flex-wrap gap-3">


                    @if ($pharmacyPurchaseOrder->status === 'draft')

                        <form
                            method="POST"
                            action="{{ route('pharmacy.purchase-orders.cancel', $pharmacyPurchaseOrder) }}"
                            onsubmit="return confirm('Cancel this Purchase Order?');"
                        >

                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="rounded-lg border border-red-200 bg-red-50 px-5 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-100"
                            >
                                Cancel PO
                            </button>

                        </form>


                        @if ($canApprovePo)

                            <form
                                method="POST"
                                action="{{ route('pharmacy.purchase-orders.approve', $pharmacyPurchaseOrder) }}"
                                onsubmit="return confirm('Approve this Purchase Order?');"
                            >

                                @csrf
                                @method('PATCH')

                                <button
                                    type="submit"
                                    class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                                >
                                    Approve PO
                                </button>

                            </form>

                        @endif

                    @endif



                    @if (
                        $canCreateGrn
                        &&
                        in_array(
                            $pharmacyPurchaseOrder->status,
                            [
                                'approved',
                                'partially_received',
                            ],
                            true
                        )
                    )

                        <a
                            href="{{ route('pharmacy.grns.create', $pharmacyPurchaseOrder) }}"
                            class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                        >
                            Receive Goods / Create GRN
                        </a>

                    @endif


                    <button
                        type="button"
                        onclick="window.print()"
                        class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Print Purchase Order
                    </button>

                </div>

            </div>


        </div>

    </div>


    <style>

        @media print {

            aside,
            nav,
            header,
            button,
            form,
            .print-hidden {
                display: none !important;
            }

            body {
                background: white !important;
            }

            .min-h-screen {
                min-height: auto !important;
                background: white !important;
            }

            .shadow-sm {
                box-shadow: none !important;
            }

        }

    </style>

</x-app-layout>