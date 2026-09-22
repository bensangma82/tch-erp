<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h2 class="text-xl font-semibold text-slate-900">
                    Role & Permission Dashboard
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Assign ERP privileges to each role.
                </p>

            </div>

        </div>

    </x-slot>


    <div class="py-8">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

            @if (session('success'))

                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('success') }}
                </div>

            @endif


            <div class="grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">

                {{-- ================================================= --}}
                {{-- ROLE SELECTOR --}}
                {{-- ================================================= --}}

                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">

                    <div class="mb-4">

                        <h3 class="font-semibold text-slate-900">
                            ERP Roles
                        </h3>

                        <p class="mt-1 text-xs text-slate-500">
                            Select a role to configure access.
                        </p>

                    </div>


                    <div class="space-y-2">

                        @foreach ($roles as $roleValue => $roleLabel)

                            <a
                                href="{{ route(
                                    'admin.role-permissions.index',
                                    ['role' => $roleValue]
                                ) }}"
                                class="block rounded-xl px-4 py-3 text-sm font-medium transition
                                    {{
                                        $selectedRole === $roleValue
                                            ? 'bg-slate-900 text-white'
                                            : 'bg-slate-50 text-slate-700 hover:bg-slate-100'
                                    }}"
                            >

                                {{ $roleLabel }}

                            </a>

                        @endforeach

                    </div>

                </div>


                {{-- ================================================= --}}
                {{-- PERMISSION MATRIX --}}
                {{-- ================================================= --}}

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                            <div>

                                <h3 class="text-lg font-semibold text-slate-900">

                                    Permissions:
                                    {{ $roles[$selectedRole] }}

                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    Tick the privileges this role should receive.
                                </p>

                            </div>


                            <div class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                {{ count($assignedPermissionIds) }} assigned
                            </div>

                        </div>

                    </div>


                    <form
                        method="POST"
                        action="{{ route('admin.role-permissions.update') }}"
                    >

                        @csrf


                        <input
                            type="hidden"
                            name="role"
                            value="{{ $selectedRole }}"
                        >


                        <div class="space-y-6 p-6">


                            @foreach ($permissionsByModule as $module => $permissions)


                                <div class="overflow-hidden rounded-xl border border-slate-200">


                                    <div class="flex items-center justify-between bg-slate-50 px-5 py-3">

                                        <div>

                                            <div class="text-sm font-bold uppercase tracking-wide text-slate-700">
                                                {{ str_replace('_', ' ', $module) }}
                                            </div>

                                            <div class="mt-1 text-xs text-slate-500">
                                                {{ $permissions->count() }} permissions
                                            </div>

                                        </div>


                                        <button
                                            type="button"
                                            class="select-module text-xs font-semibold text-blue-600 hover:text-blue-800"
                                            data-module="{{ $module }}"
                                        >
                                            Select all
                                        </button>

                                    </div>


                                    <div class="grid gap-0 divide-y divide-slate-100 md:grid-cols-2 md:divide-x md:divide-y">


                                        @foreach ($permissions as $permission)

                                            <label
                                                class="flex cursor-pointer items-start gap-3 p-4 transition hover:bg-slate-50"
                                            >

                                                <input
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="{{ $permission->id }}"
                                                    data-module="{{ $module }}"
                                                    @checked(
                                                        in_array(
                                                            $permission->id,
                                                            $assignedPermissionIds,
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

                                        @endforeach


                                    </div>


                                </div>


                            @endforeach


                        </div>


                        <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-6 py-4">


                            <div class="text-xs text-slate-500">

                                Changes apply to all users with the
                                <span class="font-semibold">
                                    {{ $roles[$selectedRole] }}
                                </span>
                                role.

                            </div>


                            <button
                                type="submit"
                                class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                            >
                                Save Permissions
                            </button>


                        </div>


                    </form>

                </div>

            </div>

        </div>

    </div>


    <script>

        document.addEventListener('DOMContentLoaded', function () {

            document
                .querySelectorAll('.select-module')
                .forEach(function (button) {

                    button.addEventListener('click', function () {

                        const module = this.dataset.module;

                        const checkboxes =
                            document.querySelectorAll(
                                'input[type="checkbox"][data-module="' +
                                module +
                                '"]'
                            );

                        const allChecked =
                            Array.from(checkboxes)
                                .every(function (checkbox) {
                                    return checkbox.checked;
                                });

                        checkboxes.forEach(function (checkbox) {
                            checkbox.checked = ! allChecked;
                        });

                        this.textContent =
                            allChecked
                                ? 'Select all'
                                : 'Clear all';

                    });

                });

        });

    </script>

</x-app-layout>
