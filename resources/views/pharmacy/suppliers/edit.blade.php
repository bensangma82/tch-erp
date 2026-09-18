<x-app-layout>

    <x-slot name="header">

        <div class="flex items-center justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Edit Pharmacy Supplier
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $pharmacySupplier->code }} · {{ $pharmacySupplier->name }}
                </p>
            </div>

            <a
                href="{{ route('pharmacy.suppliers.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Supplier Master
            </a>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif


            @if ($errors->any())

                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-5 py-4">

                    <div class="font-semibold text-red-700">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-600">

                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach

                    </ul>

                </div>

            @endif


            <form
                method="POST"
                action="{{ route('pharmacy.suppliers.update', $pharmacySupplier) }}"
                class="rounded-2xl border border-slate-200 bg-white shadow-sm"
            >

                @csrf
                @method('PUT')


                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Supplier Details
                    </h3>

                </div>


                <div class="grid gap-6 p-6 md:grid-cols-2">


                    @php
                        $supplier = $pharmacySupplier;
                    @endphp


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Supplier Code *
                        </label>

                        <input
                            type="text"
                            name="code"
                            value="{{ old('code', $supplier->code) }}"
                            required
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Supplier Name *
                        </label>

                        <input
                            type="text"
                            name="name"
                            value="{{ old('name', $supplier->name) }}"
                            required
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Contact Person
                        </label>

                        <input
                            type="text"
                            name="contact_person"
                            value="{{ old('contact_person', $supplier->contact_person) }}"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Phone
                        </label>

                        <input
                            type="text"
                            name="phone"
                            value="{{ old('phone', $supplier->phone) }}"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Alternate Phone
                        </label>

                        <input
                            type="text"
                            name="alternate_phone"
                            value="{{ old('alternate_phone', $supplier->alternate_phone) }}"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            value="{{ old('email', $supplier->email) }}"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div class="md:col-span-2">

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Address
                        </label>

                        <textarea
                            name="address"
                            rows="3"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >{{ old('address', $supplier->address) }}</textarea>

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            City
                        </label>

                        <input
                            type="text"
                            name="city"
                            value="{{ old('city', $supplier->city) }}"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            District
                        </label>

                        <input
                            type="text"
                            name="district"
                            value="{{ old('district', $supplier->district) }}"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            State
                        </label>

                        <input
                            type="text"
                            name="state"
                            value="{{ old('state', $supplier->state) }}"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            PIN Code
                        </label>

                        <input
                            type="text"
                            name="pin_code"
                            value="{{ old('pin_code', $supplier->pin_code) }}"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            GSTIN
                        </label>

                        <input
                            type="text"
                            name="gstin"
                            value="{{ old('gstin', $supplier->gstin) }}"
                            class="w-full rounded-lg border-slate-300 uppercase shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Drug Licence No.
                        </label>

                        <input
                            type="text"
                            name="drug_license_no"
                            value="{{ old('drug_license_no', $supplier->drug_license_no) }}"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            PAN No.
                        </label>

                        <input
                            type="text"
                            name="pan_no"
                            value="{{ old('pan_no', $supplier->pan_no) }}"
                            class="w-full rounded-lg border-slate-300 uppercase shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Credit Period
                        </label>

                        <div class="flex items-center gap-2">

                            <input
                                type="number"
                                name="credit_days"
                                min="0"
                                value="{{ old('credit_days', $supplier->credit_days) }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            <span class="text-sm text-slate-500">
                                days
                            </span>

                        </div>

                    </div>


                    <div class="md:col-span-2">

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Remarks
                        </label>

                        <textarea
                            name="remarks"
                            rows="3"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >{{ old('remarks', $supplier->remarks) }}</textarea>

                    </div>


                </div>


                <div class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-5">

                    <a
                        href="{{ route('pharmacy.suppliers.index') }}"
                        class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Back
                    </a>

                    <button
                        type="submit"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                    >
                        Save Changes
                    </button>

                </div>

            </form>

        </div>

    </div>

</x-app-layout>