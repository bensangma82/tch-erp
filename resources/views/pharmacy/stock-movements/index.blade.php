<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Stock Movement History
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Complete audit trail for this pharmacy batch
                </p>

            </div>


            <div class="flex flex-wrap gap-2">

                <a
                    href="{{ route('pharmacy.stock-batches.adjust', $stockBatch) }}"
                    class="inline-flex items-center rounded-lg border border-purple-200 bg-purple-50 px-4 py-2 text-sm font-semibold text-purple-700 shadow-sm transition hover:bg-purple-100"
                >
                    Adjust Stock
                </a>

                <a
                    href="{{ route('pharmacy.stock-batches.index') }}"
                    class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                >
                    Back to Pharmacy Stock
                </a>

            </div>

        </div>

    </x-slot>



    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif



            {{-- ========================================================= --}}
            {{-- BATCH SUMMARY --}}
            {{-- ========================================================= --}}

            <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">


                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Medicine
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $stockBatch->medicine?->generic_name ?? '—' }}
                        </div>

                        @if ($stockBatch->medicine?->brand_name)

                            <div class="mt-1 text-sm text-slate-500">
                                {{ $stockBatch->medicine->brand_name }}
                            </div>

                        @endif

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Batch Number
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{ $stockBatch->batch_number }}
                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Expiry
                        </div>

                        <div class="mt-2 font-bold text-slate-900">
                            {{
                                $stockBatch->expiry_date
                                    ? $stockBatch->expiry_date->format('d M Y')
                                    : '—'
                            }}
                        </div>

                    </div>



                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Current Balance
                        </div>

                        <div class="mt-2 text-2xl font-bold text-slate-900">
                            {{ number_format($stockBatch->quantity_available) }}
                        </div>

                        <div class="mt-1 text-xs text-slate-500">
                            {{ ucfirst($stockBatch->medicine?->unit ?? 'units') }}
                        </div>

                    </div>


                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- MOVEMENT LEDGER --}}
            {{-- ========================================================= --}}

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">


                <div class="border-b border-slate-100 px-6 py-5">

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <h3 class="font-semibold text-slate-900">
                                Movement Ledger
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Newest transactions are shown first
                            </p>

                        </div>


                        <div class="text-xs text-slate-400">
                            Positive movements add stock · Negative movements deduct stock
                        </div>

                    </div>

                </div>



                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Date & Time
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Movement
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Quantity
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Balance
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Reference
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Remarks
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    User
                                </th>

                            </tr>

                        </thead>



                        <tbody class="divide-y divide-slate-100 bg-white">

                            @forelse ($movements as $movement)

                                @php

                                    /*
                                    |--------------------------------------------------------------------------
                                    | NORMALIZE MOVEMENT TYPE
                                    |--------------------------------------------------------------------------
                                    |
                                    | This makes comparison safe even if the stored
                                    | value contains uppercase characters or
                                    | accidental leading/trailing spaces.
                                    |
                                    */

                                    $movementType =
                                        strtolower(
                                            trim(
                                                (string) $movement->movement_type
                                            )
                                        );


                                    /*
                                    |--------------------------------------------------------------------------
                                    | MOVEMENT DIRECTION
                                    |--------------------------------------------------------------------------
                                    |
                                    | Quantity is stored as an absolute positive value.
                                    | Direction is determined only by movement_type.
                                    |
                                    */

                                    $incomingTypes = [
                                        'stock_in',
                                        'return',
                                        'adjustment_in',
                                        'correction_plus',
                                        'other_plus',
                                    ];


                                    $outgoingTypes = [
                                        'sale',
                                        'dispense',
                                        'damage',
                                        'damaged',
                                        'expiry',
                                        'expired_writeoff',
                                        'adjustment_out',
                                        'correction_minus',
                                        'lost',
                                        'other_minus',
                                        'disposal',
                                        'purchase_return',
                                    ];


                                    $isIncoming =
                                        in_array(
                                            $movementType,
                                            $incomingTypes,
                                            true
                                        );


                                    $isOutgoing =
                                        in_array(
                                            $movementType,
                                            $outgoingTypes,
                                            true
                                        );


                                    /*
                                    |--------------------------------------------------------------------------
                                    | FRIENDLY MOVEMENT LABEL
                                    |--------------------------------------------------------------------------
                                    */

                                    $movementLabel =
                                        match ($movementType) {

                                            'stock_in' =>
                                                'Stock In',

                                            'sale' =>
                                                'Sale',

                                            'dispense' =>
                                                'Dispense',

                                            'return' =>
                                                'Return',

                                            'adjustment_in' =>
                                                'Adjustment In',

                                            'adjustment_out' =>
                                                'Adjustment Out',

                                            'correction_plus' =>
                                                'Correction Plus',

                                            'correction_minus' =>
                                                'Correction Minus',

                                            'damage' =>
                                                'Damage',

                                            'damaged' =>
                                                'Damaged',

                                            'expiry' =>
                                                'Expired Stock',

                                            'expired_writeoff' =>
                                                'Expired Write-off',

                                            'lost' =>
                                                'Lost / Missing',

                                            'other_plus' =>
                                                'Other Increase',

                                            'other_minus' =>
                                                'Other Decrease',

                                            'disposal' =>
                                                'Disposal',

                                            'purchase_return' =>
                                                'Purchase Return',

                                            default =>
                                                ucwords(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $movementType
                                                    )
                                                ),
                                        };


                                    /*
                                    |--------------------------------------------------------------------------
                                    | BADGE STYLE
                                    |--------------------------------------------------------------------------
                                    */

                                    if ($isIncoming) {

                                        $badgeClass =
                                            'bg-emerald-50 text-emerald-700';

                                    } elseif ($isOutgoing) {

                                        $badgeClass =
                                            'bg-red-50 text-red-700';

                                    } else {

                                        $badgeClass =
                                            'bg-slate-100 text-slate-700';
                                    }


                                    /*
                                    |--------------------------------------------------------------------------
                                    | QUANTITY DISPLAY
                                    |--------------------------------------------------------------------------
                                    */

                                    $absoluteQuantity =
                                        abs(
                                            (float) $movement->quantity
                                        );


                                    if ($isIncoming) {

                                        $displayQuantity =
                                            '+'
                                            . number_format(
                                                $absoluteQuantity
                                            );

                                        $quantityClass =
                                            'text-emerald-700';

                                    } elseif ($isOutgoing) {

                                        $displayQuantity =
                                            '-'
                                            . number_format(
                                                $absoluteQuantity
                                            );

                                        $quantityClass =
                                            'text-red-700';

                                    } else {

                                        $displayQuantity =
                                            number_format(
                                                $movement->quantity
                                            );

                                        $quantityClass =
                                            'text-slate-700';
                                    }

                                @endphp



                                <tr class="transition hover:bg-slate-50">


                                    {{-- DATE / TIME --}}

                                    <td class="whitespace-nowrap px-6 py-4">

                                        <div class="text-sm font-semibold text-slate-800">
                                            {{ optional($movement->movement_at)->format('d M Y') }}
                                        </div>

                                        <div class="mt-1 text-xs text-slate-400">
                                            {{ optional($movement->movement_at)->format('h:i A') }}
                                        </div>

                                    </td>



                                    {{-- MOVEMENT --}}

                                    <td class="px-6 py-4">

                                        <span
                                            class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $badgeClass }}"
                                        >
                                            {{ $movementLabel }}
                                        </span>

                                    </td>



                                    {{-- QUANTITY --}}

                                    <td class="whitespace-nowrap px-6 py-4 text-right">

                                        <span class="font-bold {{ $quantityClass }}">
                                            {{ $displayQuantity }}
                                        </span>

                                    </td>



                                    {{-- BALANCE --}}

                                    <td class="whitespace-nowrap px-6 py-4 text-right">

                                        <div class="text-sm font-bold text-slate-900">
                                            {{ number_format($movement->balance_after) }}
                                        </div>

                                    </td>



                                    {{-- REFERENCE --}}

                                    <td class="px-6 py-4">

                                        <div class="text-sm text-slate-700">

                                            {{
                                                $movement->reference_type
                                                    ? ucwords(
                                                        str_replace(
                                                            '_',
                                                            ' ',
                                                            $movement->reference_type
                                                        )
                                                    )
                                                    : '—'
                                            }}

                                        </div>

                                        @if ($movement->reference_id)

                                            <div class="mt-1 text-xs text-slate-400">
                                                #{{ $movement->reference_id }}
                                            </div>

                                        @endif

                                    </td>



                                    {{-- REMARKS --}}

                                    <td class="max-w-sm px-6 py-4">

                                        <div class="text-sm leading-6 text-slate-600">
                                            {{ $movement->remarks ?: '—' }}
                                        </div>

                                    </td>



                                    {{-- USER --}}

                                    <td class="whitespace-nowrap px-6 py-4">

                                        <div class="text-sm font-medium text-slate-700">
                                            {{ $movement->createdBy?->name ?? 'System' }}
                                        </div>

                                    </td>


                                </tr>


                            @empty

                                <tr>

                                    <td
                                        colspan="7"
                                        class="px-6 py-12 text-center text-sm text-slate-500"
                                    >
                                        No stock movements recorded for this batch.
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>



                {{-- PAGINATION --}}

                @if ($movements->hasPages())

                    <div class="border-t border-slate-100 px-6 py-4">
                        {{ $movements->links() }}
                    </div>

                @endif


            </div>

        </div>

    </div>

</x-app-layout>