<x-app-layout>

    <x-slot name="header">

        <div>

            <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                Edit Stock Batch
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Update batch pricing, expiry and reorder settings
            </p>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">


            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">


                <div class="border-b border-slate-100 px-6 py-5">

                    <div class="flex items-center justify-between">

                        <div>

                            <h3 class="font-semibold text-slate-900">
                                {{ $stockBatch->medicine->generic_name }}
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Batch {{ $stockBatch->batch_number }}
                            </p>

                        </div>


                        <div class="text-right">

                            <div class="text-xs text-slate-400">
                                Available Quantity
                            </div>

                            <div class="mt-1 text-xl font-bold text-slate-900">
                                {{ number_format($stockBatch->quantity_available) }}
                            </div>

                        </div>

                    </div>

                </div>



                <form
                    method="POST"
                    action="{{ route('pharmacy.stock-batches.update', $stockBatch) }}"
                    class="space-y-6 p-6"
                >

                    @csrf
                    @method('PUT')


                    <div class="grid gap-5 md:grid-cols-2">


                        <div class="md:col-span-2">

                            <label
                                for="medicine_id"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Medicine
                            </label>

                            <select
                                id="medicine_id"
                                name="medicine_id"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                @foreach ($medicines as $medicine)

                                    <option
                                        value="{{ $medicine->id }}"
                                        @selected(
                                            old('medicine_id', $stockBatch->medicine_id)
                                            == $medicine->id
                                        )
                                    >
                                        {{ $medicine->generic_name }}

                                        @if ($medicine->brand_name)
                                            — {{ $medicine->brand_name }}
                                        @endif

                                        @if ($medicine->strength)
                                            — {{ $medicine->strength }}
                                        @endif

                                        ({{ $medicine->code }})
                                    </option>

                                @endforeach

                            </select>

                        </div>



                        <div>

                            <label
                                for="batch_number"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Batch Number
                            </label>

                            <input
                                id="batch_number"
                                name="batch_number"
                                type="text"
                                value="{{ old('batch_number', $stockBatch->batch_number) }}"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>



                        <div>

                            <label
                                for="expiry_date"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Expiry Date
                            </label>

                            <input
                                id="expiry_date"
                                name="expiry_date"
                                type="date"
                                value="{{ old(
                                    'expiry_date',
                                    optional($stockBatch->expiry_date)->format('Y-m-d')
                                ) }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>



                        <div>

                            <label
                                for="purchase_price"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Purchase Price / Unit
                            </label>

                            <input
                                id="purchase_price"
                                name="purchase_price"
                                type="number"
                                min="0"
                                step="0.01"
                                value="{{ old('purchase_price', $stockBatch->purchase_price) }}"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>



                        <div>

                            <label
                                for="selling_price"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Selling Price / Unit
                            </label>

                            <input
                                id="selling_price"
                                name="selling_price"
                                type="number"
                                min="0"
                                step="0.01"
                                value="{{ old('selling_price', $stockBatch->selling_price) }}"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>



                        <div>

                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Quantity Received
                            </label>

                            <input
                                type="number"
                                value="{{ $stockBatch->quantity_received }}"
                                disabled
                                class="w-full rounded-lg border-slate-200 bg-slate-100 text-slate-500"
                            >

                            <p class="mt-2 text-xs text-slate-400">
                                Historical received quantity is locked.
                            </p>

                        </div>



                        <div>

                            <label
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Quantity Available
                            </label>

                            <input
                                type="number"
                                value="{{ $stockBatch->quantity_available }}"
                                disabled
                                class="w-full rounded-lg border-slate-200 bg-slate-100 text-slate-500"
                            >

                            <p class="mt-2 text-xs text-slate-400">
                                Available stock will later be controlled by stock movements and pharmacy sales.
                            </p>

                        </div>



                        <div>

                            <label
                                for="reorder_level"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Reorder Level
                            </label>

                            <input
                                id="reorder_level"
                                name="reorder_level"
                                type="number"
                                min="0"
                                step="1"
                                value="{{ old('reorder_level', $stockBatch->reorder_level) }}"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>



                        <div>

                            <label
                                for="received_date"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Received Date
                            </label>

                            <input
                                id="received_date"
                                name="received_date"
                                type="date"
                                value="{{ old(
                                    'received_date',
                                    optional($stockBatch->received_date)->format('Y-m-d')
                                ) }}"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>

                    </div>



                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">

                        <a
                            href="{{ route('pharmacy.stock-batches.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Back
                        </a>


                        <button
                            type="submit"
                            class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                        >
                            Save Changes
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</x-app-layout>