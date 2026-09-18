<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Create User
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Add a new staff account to the TCH Hospital ERP
                </p>

            </div>


            <a
                href="{{ route('admin.users.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                Back to Users
            </a>

        </div>

    </x-slot>



    <div class="bg-slate-50 py-6">

        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">


            @if ($errors->any())

                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-4">

                    <div class="font-semibold text-red-700">
                        Please correct the following:
                    </div>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-600">

                        @foreach ($errors->all() as $error)

                            <li>
                                {{ $error }}
                            </li>

                        @endforeach

                    </ul>

                </div>

            @endif



            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">


                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        User Details
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Create the account and assign the appropriate role and permissions.
                    </p>

                </div>


                <form
                    method="POST"
                    action="{{ route('admin.users.store') }}"
                    class="space-y-6 p-6"
                >

                    @csrf



                    {{-- ================================================= --}}
                    {{-- NAME --}}
                    {{-- ================================================= --}}

                    <div>

                        <label
                            for="name"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Full Name
                        </label>

                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name') }}"
                            required
                            autofocus
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                        @error('name')

                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>



                    {{-- ================================================= --}}
                    {{-- EMAIL --}}
                    {{-- ================================================= --}}

                    <div>

                        <label
                            for="email"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Email
                        </label>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            required
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                        @error('email')

                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>



                    {{-- ================================================= --}}
                    {{-- ROLE --}}
                    {{-- ================================================= --}}

                    <div>

                        <label
                            for="role"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Role
                        </label>

                        <select
                            id="role"
                            name="role"
                            required
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                            <option value="">
                                Select role
                            </option>


                            @foreach ($roles as $value => $label)

                                <option
                                    value="{{ $value }}"
                                    @selected(
                                        old('role') === $value
                                    )
                                >
                                    {{ $label }}
                                </option>

                            @endforeach

                        </select>

                        @error('role')

                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>



                    {{-- ================================================= --}}
                    {{-- DESIGNATION --}}
                    {{-- ================================================= --}}

                    <div>

                        <label
                            for="designation"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Designation
                        </label>

                        <select
                            id="designation"
                            name="designation"
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                            <option value="">
                                Select designation (optional)
                            </option>

                            @foreach ([
                                'Medical Superintendent',
                                'Deputy Medical Superintendent',
                                'Administrator',
                                'Nursing Superintendent',
                                'Manager',
                                'Accountant',
                                'Assistant Accountant',
                                'Pharmacy In-charge',
                                'Laboratory In-charge',
                                'Radiology In-charge',
                                'Reception In-charge',
                                'Stores In-charge',
                                'Maintenance In-charge',
                                'Other',
                            ] as $designation)

                                <option
                                    value="{{ $designation }}"
                                    @selected(old('designation') === $designation)
                                >
                                    {{ $designation }}
                                </option>

                            @endforeach

                        </select>

                        <p class="mt-2 text-xs leading-5 text-slate-500">
                            Designation is the staff member's hospital post and is separate
                            from the ERP role used for system access and permissions.
                        </p>

                        @error('designation')

                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>



                    {{-- ================================================= --}}
                    {{-- PHARMACY PERMISSIONS --}}
                    {{-- ================================================= --}}

                    <div
                        id="pharmacyPermissionPanel"
                        class="hidden rounded-xl border border-slate-200 bg-slate-50 p-5"
                    >

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">

                            <div>

                                <h4 class="font-semibold text-slate-900">
                                    Pharmacy Permissions
                                </h4>

                                <p class="mt-1 max-w-2xl text-xs leading-5 text-slate-500">
                                    Select only the pharmacy actions this user
                                    is authorised to perform.
                                </p>

                            </div>


                            <div class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                Permission Based
                            </div>

                        </div>



                        <div class="mt-5 grid gap-3 md:grid-cols-2">


                            @forelse ($pharmacyPermissions as $permission)

                                <label
                                    class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-blue-300 hover:bg-blue-50/30"
                                >

                                    <input
                                        type="checkbox"
                                        name="pharmacy_permissions[]"
                                        value="{{ $permission->name }}"
                                        @checked(
                                            in_array(
                                                $permission->name,
                                                old(
                                                    'pharmacy_permissions',
                                                    []
                                                ),
                                                true
                                            )
                                        )
                                        class="mt-1 rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                    >


                                    <span class="min-w-0">

                                        <span class="block text-sm font-semibold text-slate-800">
                                            {{ $permission->label }}
                                        </span>


                                        <span class="mt-1 block text-xs font-mono text-slate-400">
                                            {{ $permission->name }}
                                        </span>


                                        @if ($permission->description)

                                            <span class="mt-2 block text-xs leading-5 text-slate-500">
                                                {{ $permission->description }}
                                            </span>

                                        @endif

                                    </span>

                                </label>


                            @empty

                                <div class="md:col-span-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                                    No pharmacy permissions have been configured.
                                </div>

                            @endforelse

                        </div>



                        <div class="mt-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">

                            <div class="text-sm font-semibold text-amber-800">
                                Separation of duties
                            </div>

                            <div class="mt-1 text-xs leading-5 text-amber-700">
                                Stock Audit creation, approval and final posting
                                should normally be assigned to different users.
                            </div>

                        </div>


                        @error('pharmacy_permissions')

                            <p class="mt-3 text-sm text-red-600">
                                {{ $message }}
                            </p>

                        @enderror


                        @error('pharmacy_permissions.*')

                            <p class="mt-3 text-sm text-red-600">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>



                    {{-- ================================================= --}}
                    {{-- PASSWORD --}}
                    {{-- ================================================= --}}

                    <div class="grid gap-5 md:grid-cols-2">


                        <div>

                            <label
                                for="password"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Password
                            </label>

                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                autocomplete="new-password"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            @error('password')

                                <p class="mt-2 text-sm text-red-600">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>



                        <div>

                            <label
                                for="password_confirmation"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                Confirm Password
                            </label>

                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                        </div>

                    </div>



                    {{-- ================================================= --}}
                    {{-- ACTIONS --}}
                    {{-- ================================================= --}}

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">

                        <a
                            href="{{ route('admin.users.index') }}"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                        >
                            Cancel
                        </a>


                        <button
                            type="submit"
                            class="rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
                        >
                            Create User
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>



    <script>

        document.addEventListener(
            'DOMContentLoaded',
            function () {

                const roleSelect =
                    document.getElementById(
                        'role'
                    );


                const pharmacyPanel =
                    document.getElementById(
                        'pharmacyPermissionPanel'
                    );


                function updatePermissionPanel() {

                    if (
                        roleSelect.value
                        ===
                        'pharmacy'
                    ) {

                        pharmacyPanel.classList.remove(
                            'hidden'
                        );

                    } else {

                        pharmacyPanel.classList.add(
                            'hidden'
                        );
                    }
                }


                roleSelect.addEventListener(
                    'change',
                    updatePermissionPanel
                );


                updatePermissionPanel();

            }
        );

    </script>

</x-app-layout>