<x-app-layout>

    <x-slot name="header">

        <div>

            <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                Add Stock Batch
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Record a newly received pharmacy batch
            </p>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">


            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">


                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Batch Details
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Quantity available will initially equal quantity received
                    </p>

                </div>


                <form
                    method="POST"
                    action="{{ route('pharmacy.stock-batches.store') }}"
                    class="space-y-6 p-6"
                >

                    @csrf


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

                                <option value="">
                                    Select medicine
                                </option>

                                @foreach ($medicines as $medicine)

                                    <option
                                        value="{{ $medicine->id }}"
                                        @selected(old('medicine_id') == $medicine->id)
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

                            @error('medicine_id')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

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
                                value="{{ old('batch_number') }}"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            @error('batch_number')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

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
                                value="{{ old('expiry_date') }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            @error('expiry_date')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

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
                                value="{{ old('purchase_price', 0) }}"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            @error('purchase_price')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

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
                                value="{{ old('selling_price', 0) }}"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            @error('selling_price')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>



                        <div>

                            <label
                                for="quantity_received"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Quantity Received
                            </label>

                            <input
                                id="quantity_received"
                                name="quantity_received"
                                type="number"
                                min="1"
                                step="1"
                                value="{{ old('quantity_received') }}"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            @error('quantity_received')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

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
                                value="{{ old('reorder_level', 10) }}"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            @error('reorder_level')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

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
                                value="{{ old('received_date', now()->format('Y-m-d')) }}"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            @error('received_date')
                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>

                    </div>



                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">

                        <a
                            href="{{ route('pharmacy.stock-batches.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Cancel
                        </a>


                        <button
                            type="submit"
                            class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                        >
                            Save Stock Batch
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</x-app-layout>