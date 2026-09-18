<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Edit User
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Manage account details, role, permissions and password
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

        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif


            @if (session('error'))

                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                    {{ session('error') }}
                </div>

            @endif


            @if ($errors->any())

                <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4">

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



            {{-- ========================================================= --}}
            {{-- USER DETAILS --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">


                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Account Details
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Update the user's identity, login email, role and access permissions.
                    </p>

                </div>


                <form
                    method="POST"
                    action="{{ route('admin.users.update', $user) }}"
                    class="space-y-6 p-6"
                >

                    @csrf
                    @method('PUT')


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
                            value="{{ old('name', $user->name) }}"
                            required
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                        @error('name')

                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>



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
                            value="{{ old('email', $user->email) }}"
                            required
                            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        >

                        @error('email')

                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>



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

                            @foreach ($roles as $value => $label)

                                <option
                                    value="{{ $value }}"
                                    @selected(
                                        old(
                                            'role',
                                            $user->role
                                        ) === $value
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
                                No designation
                            </option>

                            @foreach ($designations as $value => $label)

                                <option
                                    value="{{ $value }}"
                                    @selected(
                                        old(
                                            'designation',
                                            $user->designation
                                        ) === $value
                                    )
                                >
                                    {{ $label }}
                                </option>

                            @endforeach

                        </select>

                        <p class="mt-2 text-xs leading-5 text-slate-500">
                            Designation identifies the staff member's hospital responsibility.
                            It is separate from the ERP role used for system access.
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
                        class="rounded-xl border border-slate-200 bg-slate-50 p-5"
                    >

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">

                            <div>

                                <h4 class="font-semibold text-slate-900">
                                    Pharmacy Permissions
                                </h4>

                                <p class="mt-1 max-w-2xl text-xs leading-5 text-slate-500">
                                    Select only the actions this pharmacy user is authorised
                                    to perform. Backend route protection will enforce these
                                    permissions even if a user attempts to access an action
                                    directly.
                                </p>

                            </div>


                            <div class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                                Permission Based
                            </div>

                        </div>



                        <div class="mt-5 grid gap-3 md:grid-cols-2">


                            @forelse ($pharmacyPermissions as $permission)

                                @php

                                    $checked =
                                        in_array(
                                            $permission->name,
                                            old(
                                                'pharmacy_permissions',
                                                $userPermissionNames
                                            ),
                                            true
                                        );

                                @endphp


                                <label
                                    class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-blue-300 hover:bg-blue-50/30"
                                >

                                    <input
                                        type="checkbox"
                                        name="pharmacy_permissions[]"
                                        value="{{ $permission->name }}"
                                        @checked($checked)
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
                                Stock Audit creation, approval and posting should normally
                                be assigned to different users. The server also prevents
                                an audit creator from approving their own audit and prevents
                                the creator or approver from performing final posting.
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



                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-6">

                        <a
                            href="{{ route('admin.users.index') }}"
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



            {{-- ========================================================= --}}
            {{-- PASSWORD RESET --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">


                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Reset Password
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Set a new login password for this user.
                    </p>

                </div>


                <form
                    method="POST"
                    action="{{ route('admin.users.password', $user) }}"
                    class="space-y-6 p-6"
                >

                    @csrf
                    @method('PUT')


                    <div class="grid gap-5 md:grid-cols-2">


                        <div>

                            <label
                                for="password"
                                class="mb-2 block text-sm font-semibold text-slate-700"
                            >
                                New Password
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



                    <div class="flex justify-end border-t border-slate-100 pt-6">

                        <button
                            type="submit"
                            class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >
                            Reset Password
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