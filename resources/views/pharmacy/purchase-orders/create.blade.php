<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    New Purchase Order
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Create a pharmacy purchase order
                </p>
            </div>

            <a
                href="{{ route('pharmacy.purchase-orders.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Purchase Orders
            </a>

        </div>

    </x-slot>


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


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
                action="{{ route('pharmacy.purchase-orders.store') }}"
                id="purchaseOrderForm"
                class="space-y-6"
            >

                @csrf



                {{-- ========================================================= --}}
                {{-- PO HEADER --}}
                {{-- ========================================================= --}}

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Purchase Order Details
                        </h3>

                    </div>


                    <div class="grid gap-6 p-6 md:grid-cols-2 lg:grid-cols-4">


                        <div class="lg:col-span-2">

                            <label
                                for="pharmacy_supplier_id"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Supplier *
                            </label>

                            <select
                                id="pharmacy_supplier_id"
                                name="pharmacy_supplier_id"
                                required
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                                <option value="">
                                    Select supplier
                                </option>

                                @foreach ($suppliers as $supplier)

                                    <option
                                        value="{{ $supplier->id }}"
                                        @selected(
                                            old('pharmacy_supplier_id')
                                            == $supplier->id
                                        )
                                    >
                                        {{ $supplier->code }}
                                        ·
                                        {{ $supplier->name }}
                                        @if ($supplier->state)
                                            · {{ $supplier->state }}
                                        @endif
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        <div>

                            <label
                                for="po_date"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                PO Date *
                            </label>

                            <input
                                id="po_date"
                                name="po_date"
                                type="date"
                                required
                                value="{{ old('po_date', today()->format('Y-m-d')) }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>


                        <div>

                            <label
                                for="expected_delivery_date"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Expected Delivery
                            </label>

                            <input
                                id="expected_delivery_date"
                                name="expected_delivery_date"
                                type="date"
                                value="{{ old('expected_delivery_date') }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>


                        <div class="md:col-span-2 lg:col-span-4">

                            <label
                                for="remarks"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Remarks
                            </label>

                            <textarea
                                id="remarks"
                                name="remarks"
                                rows="3"
                                maxlength="3000"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Optional purchase instructions, delivery terms, etc."
                            >{{ old('remarks') }}</textarea>

                        </div>


                    </div>

                </div>



                {{-- ========================================================= --}}
                {{-- ITEMS --}}
                {{-- ========================================================= --}}

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="flex flex-col gap-3 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <h3 class="font-semibold text-slate-900">
                                Medicines
                            </h3>

                            <p class="mt-1 text-xs text-slate-500">
                                Add medicines, quantities, purchase cost, discount and GST
                            </p>

                        </div>


                        <button
                            type="button"
                            id="addRowButton"
                            class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100"
                        >
                            + Add Medicine
                        </button>

                    </div>


                    <div class="overflow-x-auto">

                        <table class="min-w-[1100px] w-full divide-y divide-slate-200">

                            <thead class="bg-slate-50">

                                <tr>

                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Medicine
                                    </th>

                                    <th class="w-28 px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Qty
                                    </th>

                                    <th class="w-36 px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Unit Cost
                                    </th>

                                    <th class="w-28 px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Discount %
                                    </th>

                                    <th class="w-28 px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        GST %
                                    </th>

                                    <th class="w-36 px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Taxable
                                    </th>

                                    <th class="w-36 px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Total
                                    </th>

                                    <th class="w-20 px-3 py-3"></th>

                                </tr>

                            </thead>


                            <tbody
                                id="itemsBody"
                                class="divide-y divide-slate-100"
                            >
                            </tbody>

                        </table>

                    </div>


                    <div class="border-t border-slate-100 bg-slate-50 px-6 py-5">

                        <div class="ml-auto max-w-md space-y-3">


                            <div class="flex items-center justify-between text-sm">

                                <span class="text-slate-500">
                                    Gross Subtotal
                                </span>

                                <span
                                    id="subtotalPreview"
                                    class="font-semibold text-slate-900"
                                >
                                    ₹0.00
                                </span>

                            </div>


                            <div class="flex items-center justify-between text-sm">

                                <span class="text-slate-500">
                                    Discount
                                </span>

                                <span
                                    id="discountPreview"
                                    class="font-semibold text-red-700"
                                >
                                    ₹0.00
                                </span>

                            </div>


                            <div class="flex items-center justify-between text-sm">

                                <span class="text-slate-500">
                                    Taxable Amount
                                </span>

                                <span
                                    id="taxablePreview"
                                    class="font-semibold text-slate-900"
                                >
                                    ₹0.00
                                </span>

                            </div>


                            <div class="flex items-center justify-between text-sm">

                                <span class="text-slate-500">
                                    CGST
                                </span>

                                <span
                                    id="cgstPreview"
                                    class="font-semibold text-slate-900"
                                >
                                    ₹0.00
                                </span>

                            </div>


                            <div class="flex items-center justify-between text-sm">

                                <span class="text-slate-500">
                                    SGST
                                </span>

                                <span
                                    id="sgstPreview"
                                    class="font-semibold text-slate-900"
                                >
                                    ₹0.00
                                </span>

                            </div>


                            <div class="flex items-center justify-between text-sm">

                                <span class="text-slate-500">
                                    IGST
                                </span>

                                <span
                                    id="igstPreview"
                                    class="font-semibold text-slate-900"
                                >
                                    ₹0.00
                                </span>

                            </div>


                            <div class="flex items-center justify-between border-t border-slate-300 pt-3">

                                <span class="font-bold text-slate-900">
                                    Purchase Order Total
                                </span>

                                <span
                                    id="grandTotalPreview"
                                    class="text-2xl font-bold text-blue-700"
                                >
                                    ₹0.00
                                </span>

                            </div>


                            <p class="text-right text-xs text-slate-400">
                                Final values are recalculated by the server when the PO is saved.
                            </p>

                        </div>

                    </div>

                </div>



                {{-- ========================================================= --}}
                {{-- ACTIONS --}}
                {{-- ========================================================= --}}

                <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white px-6 py-5 shadow-sm sm:flex-row sm:items-center sm:justify-between">

                    <div class="text-xs leading-5 text-slate-500">
                        Creating this Purchase Order does
                        <strong>not</strong>
                        add medicines to pharmacy stock. Stock will be received later through GRN.
                    </div>


                    <div class="flex gap-3">

                        <a
                            href="{{ route('pharmacy.purchase-orders.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            id="saveButton"
                            disabled
                            class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                        >
                            Save Draft PO
                        </button>

                    </div>

                </div>


            </form>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- MEDICINE ROW TEMPLATE --}}
    {{-- ========================================================= --}}

    <template id="itemRowTemplate">

        <tr class="purchase-item-row">

            <td class="px-4 py-4 align-top">

                <select
                    data-field="medicine_id"
                    required
                    class="medicine-select w-full min-w-72 rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >

                    <option value="">
                        Select medicine
                    </option>

                    @foreach ($medicines as $medicine)

                        <option
                            value="{{ $medicine->id }}"
                            data-gst="{{ (float) ($medicine->gst_percent ?? 0) }}"
                            data-code="{{ $medicine->code }}"
                        >
                            {{ $medicine->generic_name }}

                            @if ($medicine->brand_name)
                                · {{ $medicine->brand_name }}
                            @endif

                            @if ($medicine->strength)
                                · {{ $medicine->strength }}
                            @endif
                        </option>

                    @endforeach

                </select>

            </td>


            <td class="px-3 py-4 align-top">

                <input
                    type="number"
                    min="1"
                    step="1"
                    value="1"
                    data-field="quantity_ordered"
                    required
                    class="quantity-input w-full rounded-lg border-slate-300 text-right text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >

            </td>


            <td class="px-3 py-4 align-top">

                <input
                    type="number"
                    min="0"
                    step="0.01"
                    value="0"
                    data-field="unit_cost"
                    required
                    class="unit-cost-input w-full rounded-lg border-slate-300 text-right text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >

            </td>


            <td class="px-3 py-4 align-top">

                <input
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    value="0"
                    data-field="discount_percent"
                    class="discount-input w-full rounded-lg border-slate-300 text-right text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >

            </td>


            <td class="px-3 py-4 align-top">

                <input
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    value="0"
                    data-field="gst_percent"
                    class="gst-input w-full rounded-lg border-slate-300 text-right text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >

            </td>


            <td class="px-3 py-4 text-right align-top">

                <div class="taxable-preview py-2 text-sm font-semibold text-slate-700">
                    ₹0.00
                </div>

            </td>


            <td class="px-3 py-4 text-right align-top">

                <div class="line-total-preview py-2 text-sm font-bold text-slate-900">
                    ₹0.00
                </div>

            </td>


            <td class="px-3 py-4 text-right align-top">

                <button
                    type="button"
                    class="remove-row-button rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100"
                >
                    Remove
                </button>

            </td>

        </tr>

    </template>



    <script>

        document.addEventListener(
            'DOMContentLoaded',
            function () {

                const body =
                    document.getElementById('itemsBody');

                const template =
                    document.getElementById('itemRowTemplate');

                const addRowButton =
                    document.getElementById('addRowButton');

                const saveButton =
                    document.getElementById('saveButton');

                const supplierSelect =
                    document.getElementById('pharmacy_supplier_id');


                let rowCounter = 0;



                function money(value) {

                    return '₹'
                        + Number(value || 0).toLocaleString(
                            'en-IN',
                            {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }
                        );
                }



                function refreshNames() {

                    const rows =
                        body.querySelectorAll(
                            '.purchase-item-row'
                        );


                    rows.forEach(
                        function (row, index) {

                            row
                                .querySelectorAll(
                                    '[data-field]'
                                )
                                .forEach(
                                    function (field) {

                                        field.name =
                                            'items['
                                            + index
                                            + ']['
                                            + field.dataset.field
                                            + ']';
                                    }
                                );
                        }
                    );
                }



                function supplierIsInterState() {

                    const selected =
                        supplierSelect
                            .options[
                                supplierSelect.selectedIndex
                            ];


                    if (! selected) {
                        return false;
                    }


                    const text =
                        selected.textContent
                            .toLowerCase();


                    /*
                     * Client-side preview only.
                     * Server calculates tax using supplier state.
                     */
                    return (
                        supplierSelect.value
                        && ! text.includes('meghalaya')
                    );
                }



                function calculateRow(row) {

                    const quantity =
                        Math.max(
                            0,
                            Number(
                                row.querySelector(
                                    '.quantity-input'
                                ).value
                                || 0
                            )
                        );


                    const unitCost =
                        Math.max(
                            0,
                            Number(
                                row.querySelector(
                                    '.unit-cost-input'
                                ).value
                                || 0
                            )
                        );


                    const discountPercent =
                        Math.min(
                            100,
                            Math.max(
                                0,
                                Number(
                                    row.querySelector(
                                        '.discount-input'
                                    ).value
                                    || 0
                                )
                            )
                        );


                    const gstPercent =
                        Math.min(
                            100,
                            Math.max(
                                0,
                                Number(
                                    row.querySelector(
                                        '.gst-input'
                                    ).value
                                    || 0
                                )
                            )
                        );


                    const gross =
                        quantity
                        * unitCost;


                    const discount =
                        gross
                        * discountPercent
                        / 100;


                    const taxable =
                        gross
                        - discount;


                    const gst =
                        taxable
                        * gstPercent
                        / 100;


                    let cgst = 0;
                    let sgst = 0;
                    let igst = 0;


                    if (
                        supplierIsInterState()
                    ) {

                        igst =
                            gst;

                    } else {

                        cgst =
                            gst / 2;

                        sgst =
                            gst - cgst;
                    }


                    const total =
                        taxable
                        + cgst
                        + sgst
                        + igst;


                    row.querySelector(
                        '.taxable-preview'
                    ).textContent =
                        money(taxable);


                    row.querySelector(
                        '.line-total-preview'
                    ).textContent =
                        money(total);


                    return {
                        gross,
                        discount,
                        taxable,
                        cgst,
                        sgst,
                        igst,
                        total
                    };
                }



                function calculateTotals() {

                    let subtotal = 0;
                    let discount = 0;
                    let taxable = 0;
                    let cgst = 0;
                    let sgst = 0;
                    let igst = 0;
                    let total = 0;


                    const rows =
                        body.querySelectorAll(
                            '.purchase-item-row'
                        );


                    rows.forEach(
                        function (row) {

                            const values =
                                calculateRow(row);


                            subtotal +=
                                values.gross;

                            discount +=
                                values.discount;

                            taxable +=
                                values.taxable;

                            cgst +=
                                values.cgst;

                            sgst +=
                                values.sgst;

                            igst +=
                                values.igst;

                            total +=
                                values.total;
                        }
                    );


                    document.getElementById(
                        'subtotalPreview'
                    ).textContent =
                        money(subtotal);


                    document.getElementById(
                        'discountPreview'
                    ).textContent =
                        money(discount);


                    document.getElementById(
                        'taxablePreview'
                    ).textContent =
                        money(taxable);


                    document.getElementById(
                        'cgstPreview'
                    ).textContent =
                        money(cgst);


                    document.getElementById(
                        'sgstPreview'
                    ).textContent =
                        money(sgst);


                    document.getElementById(
                        'igstPreview'
                    ).textContent =
                        money(igst);


                    document.getElementById(
                        'grandTotalPreview'
                    ).textContent =
                        money(total);


                    updateSubmitState();
                }



                function updateSubmitState() {

                    const rows =
                        body.querySelectorAll(
                            '.purchase-item-row'
                        );


                    let valid =
                        rows.length > 0
                        && supplierSelect.value;


                    rows.forEach(
                        function (row) {

                            const medicine =
                                row.querySelector(
                                    '.medicine-select'
                                ).value;


                            const quantity =
                                Number(
                                    row.querySelector(
                                        '.quantity-input'
                                    ).value
                                    || 0
                                );


                            if (
                                ! medicine
                                || quantity <= 0
                            ) {

                                valid =
                                    false;
                            }
                        }
                    );


                    saveButton.disabled =
                        ! valid;
                }



                function addRow(values = {}) {

                    const fragment =
                        template.content.cloneNode(
                            true
                        );


                    const row =
                        fragment.querySelector(
                            '.purchase-item-row'
                        );


                    row.dataset.rowId =
                        rowCounter++;


                    body.appendChild(
                        fragment
                    );


                    const createdRow =
                        body.lastElementChild;


                    const medicineSelect =
                        createdRow.querySelector(
                            '.medicine-select'
                        );


                    const quantityInput =
                        createdRow.querySelector(
                            '.quantity-input'
                        );


                    const unitCostInput =
                        createdRow.querySelector(
                            '.unit-cost-input'
                        );


                    const discountInput =
                        createdRow.querySelector(
                            '.discount-input'
                        );


                    const gstInput =
                        createdRow.querySelector(
                            '.gst-input'
                        );


                    if (values.medicine_id) {
                        medicineSelect.value =
                            values.medicine_id;
                    }


                    if (values.quantity_ordered) {
                        quantityInput.value =
                            values.quantity_ordered;
                    }


                    if (
                        values.unit_cost !== undefined
                    ) {
                        unitCostInput.value =
                            values.unit_cost;
                    }


                    if (
                        values.discount_percent !== undefined
                    ) {
                        discountInput.value =
                            values.discount_percent;
                    }


                    if (
                        values.gst_percent !== undefined
                    ) {

                        gstInput.value =
                            values.gst_percent;

                    } else if (
                        medicineSelect.value
                    ) {

                        const option =
                            medicineSelect
                                .options[
                                    medicineSelect.selectedIndex
                                ];


                        gstInput.value =
                            option.dataset.gst
                            || 0;
                    }


                    medicineSelect.addEventListener(
                        'change',
                        function () {

                            const option =
                                medicineSelect
                                    .options[
                                        medicineSelect.selectedIndex
                                    ];


                            if (
                                option
                                && option.value
                            ) {

                                gstInput.value =
                                    option.dataset.gst
                                    || 0;
                            }


                            calculateTotals();
                        }
                    );


                    createdRow
                        .querySelectorAll(
                            'input'
                        )
                        .forEach(
                            function (input) {

                                input.addEventListener(
                                    'input',
                                    calculateTotals
                                );
                            }
                        );


                    createdRow
                        .querySelector(
                            '.remove-row-button'
                        )
                        .addEventListener(
                            'click',
                            function () {

                                createdRow.remove();

                                refreshNames();

                                calculateTotals();
                            }
                        );


                    refreshNames();

                    calculateTotals();
                }



                addRowButton.addEventListener(
                    'click',
                    function () {
                        addRow();
                    }
                );


                supplierSelect.addEventListener(
                    'change',
                    calculateTotals
                );



                @if (old('items'))

                    const oldItems =
                        @json(old('items'));

                    oldItems.forEach(
                        function (item) {

                            addRow({
                                medicine_id:
                                    item.medicine_id,

                                quantity_ordered:
                                    item.quantity_ordered,

                                unit_cost:
                                    item.unit_cost,

                                discount_percent:
                                    item.discount_percent,

                                gst_percent:
                                    item.gst_percent
                            });
                        }
                    );

                @else

                    addRow();

                @endif

            }
        );

    </script>

</x-app-layout>