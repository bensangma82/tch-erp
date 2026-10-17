<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">
                    Role & Permission Dashboard
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Configure ERP access by role. Roles define broad access; permissions refine individual functions.
                </p>
            </div>

            <div class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 shadow-sm">
                Access Control
            </div>
        </div>
    </x-slot>


    <div class="py-8">
        <div class="mx-auto max-w-[1600px] px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif


            {{-- ========================================================= --}}
            {{-- ROLE SELECTOR --}}
            {{-- ========================================================= --}}

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="sticky top-0 z-20 border-b border-slate-100 bg-white/95 px-6 py-4 backdrop-blur">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="font-semibold text-slate-900">
                                ERP Roles
                            </h3>

                            <p class="mt-1 text-sm text-slate-500">
                                Select a role to configure its privileges.
                            </p>
                        </div>

                        <div class="text-xs font-medium text-slate-400">
                            {{ count($roles) }} roles
                        </div>
                    </div>
                </div>


                <div class="px-4 pt-4">
                    <input
                        id="roleSearch"
                        type="search"
                        placeholder="Search hospital roles..."
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 sm:max-w-sm"
                    >
                </div>

                <div id="roleGrid" class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

                    @foreach ($roles as $roleValue => $roleLabel)

                        @php
                            $isSelected = $selectedRole === $roleValue;
                        @endphp

                        <a
                            data-role-search="{{ strtolower($roleLabel.' '.$roleValue) }}"
                            href="{{ route(
                                'admin.role-permissions.index',
                                ['role' => $roleValue]
                            ) }}"
                            class="group flex items-center justify-between rounded-xl border px-4 py-3 transition
                                {{
                                    $isSelected
                                        ? 'border-slate-950 bg-slate-900 text-white shadow-md ring-1 ring-slate-900/10'
                                        : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-slate-300 hover:bg-white hover:shadow-sm'
                                }}"
                        >
                            <div class="min-w-0">

                                <div class="truncate text-sm font-semibold">
                                    {{ $roleLabel }}
                                </div>

                                <div
                                    class="mt-1 text-[11px] font-medium uppercase tracking-wide
                                        {{
                                            $isSelected
                                                ? 'text-slate-300'
                                                : 'text-slate-400'
                                        }}"
                                >
                                    {{ str_replace('_', ' ', $roleValue) }}
                                </div>

                            </div>


                            <div
                                class="ml-3 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold
                                    {{
                                        $isSelected
                                            ? 'bg-white/10 text-white'
                                            : 'bg-white text-slate-400 ring-1 ring-slate-200 group-hover:text-slate-700'
                                    }}"
                            >
                                @if ($isSelected)
                                    ✓
                                @else
                                    ›
                                @endif
                            </div>

                        </a>

                    @endforeach

                </div>

            </div>


            {{-- ========================================================= --}}
            {{-- PERMISSION MATRIX --}}
            {{-- ========================================================= --}}

            <form
                method="POST"
                action="{{ route('admin.role-permissions.update') }}"
                class="mt-6"
            >
                @csrf

                <input
                    type="hidden"
                    name="role"
                    value="{{ $selectedRole }}"
                >


                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                            <div>
                                <div class="flex flex-wrap items-center gap-2">

                                    <h3 class="text-lg font-semibold text-slate-900">
                                        {{ $roles[$selectedRole] }}
                                    </h3>

                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                        {{ count($assignedPermissionIds) }} assigned
                                    </span>

                                </div>

                                <p class="mt-1 text-sm text-slate-500">
                                    Tick the functions this role is allowed to use.
                                </p>

                                @php
                                    $totalPermissions = $permissionsByModule
                                        ->flatten(1)
                                        ->count();

                                    $assignedCount = count($assignedPermissionIds);

                                    $assignedPercentage = $totalPermissions > 0
                                        ? round(($assignedCount / $totalPermissions) * 100)
                                        : 0;
                                @endphp

                                <div class="mt-3 max-w-sm">
                                    <div class="mb-1 flex justify-between text-xs text-slate-500">
                                        <span>Permission coverage</span>
                                        <span>{{ $assignedCount }} / {{ $totalPermissions }} ({{ $assignedPercentage }}%)</span>
                                    </div>

                                    <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div
                                            class="h-full rounded-full bg-blue-600"
                                            style="width: {{ $assignedPercentage }}%"
                                        ></div>
                                    </div>
                                </div>
                            </div>


                            <div class="flex flex-wrap items-center gap-2">

                                <div class="relative">
                                    <input
                                        id="permissionSearch"
                                        type="search"
                                        placeholder="Search permissions..."
                                        class="w-56 rounded-lg border-slate-300 bg-white px-3 py-2 text-xs text-slate-700 shadow-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-blue-500"
                                    >
                                </div>

                                <button
                                    type="button"
                                    id="selectAllPermissions"
                                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    Select All
                                </button>

                                <button
                                    type="button"
                                    id="clearAllPermissions"
                                    class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                >
                                    Clear All
                                </button>

                                <button
                                    type="submit"
                                    class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-slate-800"
                                >
                                    Save Permissions
                                </button>

                            </div>

                        </div>

                    </div>


                    <div class="grid gap-5 p-6 xl:grid-cols-2">

                        @foreach ($permissionsByModule as $module => $permissions)

                            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">

                                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-5 py-3">

                                    <div>
                                        <div class="text-xs font-bold uppercase tracking-[0.12em] text-slate-700">
                                            {{ str_replace('_', ' ', $module) }}
                                        </div>

                                        <div class="mt-1 flex items-center gap-2 text-[11px] text-slate-400">
                                            <span>
                                                {{ $permissions->count() }}
                                                {{ $permissions->count() === 1 ? 'permission' : 'permissions' }}
                                            </span>

                                            <span
                                                class="module-selected-count rounded-full bg-slate-200/70 px-2 py-0.5 font-semibold text-slate-600"
                                                data-module="{{ $module }}"
                                            >
                                                0 selected
                                            </span>
                                        </div>
                                    </div>


                                    <button
                                        type="button"
                                        class="select-module rounded-md px-2.5 py-1.5 text-xs font-semibold text-blue-600 transition hover:bg-blue-50 hover:text-blue-800"
                                        data-module="{{ $module }}"
                                    >
                                        Select all
                                    </button>

                                </div>


                                <div class="divide-y divide-slate-100">

                                    @foreach ($permissions as $permission)

                                        @php
                                            $checked = in_array(
                                                $permission->id,
                                                $assignedPermissionIds,
                                                true
                                            );
                                        @endphp

                                        <label
                                            class="permission-row flex cursor-pointer items-start gap-3 px-5 py-4 transition hover:bg-slate-50"
                                            data-module="{{ $module }}"
                                            data-search="{{ strtolower($permission->label.' '.$permission->name.' '.($permission->description ?? '')) }}"
                                        >

                                            <input
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission->id }}"
                                                data-module="{{ $module }}"
                                                @checked($checked)
                                                class="permission-checkbox mt-0.5 rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                            >


                                            <span class="min-w-0 flex-1">

                                                <span class="flex flex-wrap items-center gap-x-2 gap-y-1">

                                                    <span class="text-sm font-semibold text-slate-800">
                                                        {{ $permission->label }}
                                                    </span>

                                                    <span class="font-mono text-[10px] text-slate-400">
                                                        {{ $permission->name }}
                                                    </span>

                                                </span>


                                                @php
                                                    $sensitiveKeywords = [
                                                        'refund',
                                                        'cancel',
                                                        'tally',
                                                        'journal',
                                                        'voucher',
                                                        'permission',
                                                        'user.manage',
                                                        'approve',
                                                        'delete',
                                                    ];

                                                    $permissionName = strtolower(
                                                        $permission->name
                                                    );

                                                    $isSensitive = collect($sensitiveKeywords)
                                                        ->contains(
                                                            fn ($keyword) =>
                                                                str_contains($permissionName, $keyword)
                                                        );
                                                @endphp

                                                @if ($isSensitive)
                                                    <span class="mt-2 inline-flex rounded-md border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-800">
                                                        Sensitive permission
                                                    </span>
                                                @endif

                                                @if ($permission->description)

                                                    <span class="mt-1.5 block text-xs leading-5 text-slate-500">
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


                    {{-- ================================================= --}}
                    {{-- STICKY SAVE FOOTER --}}
                    {{-- ================================================= --}}

                    <div class="sticky bottom-0 flex flex-col gap-3 border-t border-slate-200 bg-white/95 px-6 py-4 backdrop-blur sm:flex-row sm:items-center sm:justify-between">

                        <div class="text-xs leading-5 text-slate-500">
                            Changes apply to all users assigned the
                            <span class="font-semibold text-slate-700">
                                {{ $roles[$selectedRole] }}
                            </span>
                            role.
                        </div>


                        <div class="flex items-center gap-3">

                            <span
                                id="selectedPermissionCount"
                                class="text-xs font-semibold text-slate-500"
                            >
                                {{ count($assignedPermissionIds) }} selected
                            </span>

                            <button
                                type="submit"
                                class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                            >
                                Save Permissions
                            </button>

                        </div>

                    </div>

                </div>

            </form>

        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const allCheckboxes =
                Array.from(
                    document.querySelectorAll('.permission-checkbox')
                );

            const selectedCount =
                document.getElementById('selectedPermissionCount');


            function updateSelectedCount() {

                const count =
                    allCheckboxes.filter(function (checkbox) {
                        return checkbox.checked;
                    }).length;

                selectedCount.textContent =
                    count + ' selected';
            }


            function updateModuleSelectedCounts() {

                document
                    .querySelectorAll('.module-selected-count')
                    .forEach(function (badge) {

                        const module = badge.dataset.module;

                        const checkboxes =
                            Array.from(
                                document.querySelectorAll(
                                    '.permission-checkbox[data-module="' +
                                    module +
                                    '"]'
                                )
                            );

                        const selected =
                            checkboxes.filter(function (checkbox) {
                                return checkbox.checked;
                            }).length;

                        badge.textContent =
                            selected + ' selected';
                    });
            }


            function updateModuleButton(button) {

                const module = button.dataset.module;

                const checkboxes =
                    Array.from(
                        document.querySelectorAll(
                            '.permission-checkbox[data-module="' +
                            module +
                            '"]'
                        )
                    );

                const allChecked =
                    checkboxes.length > 0
                    &&
                    checkboxes.every(function (checkbox) {
                        return checkbox.checked;
                    });

                button.textContent =
                    allChecked
                        ? 'Clear all'
                        : 'Select all';
            }


            document
                .querySelectorAll('.select-module')
                .forEach(function (button) {

                    updateModuleButton(button);

                    button.addEventListener('click', function () {

                        const module = this.dataset.module;

                        const checkboxes =
                            Array.from(
                                document.querySelectorAll(
                                    '.permission-checkbox[data-module="' +
                                    module +
                                    '"]'
                                )
                            );

                        const allChecked =
                            checkboxes.length > 0
                            &&
                            checkboxes.every(function (checkbox) {
                                return checkbox.checked;
                            });

                        checkboxes.forEach(function (checkbox) {
                            checkbox.checked = ! allChecked;
                        });

                        updateModuleButton(this);
                        updateSelectedCount();
                        updateModuleSelectedCounts();
                    });

                });


            allCheckboxes.forEach(function (checkbox) {

                checkbox.addEventListener('change', function () {

                    const module = this.dataset.module;

                    const button =
                        document.querySelector(
                            '.select-module[data-module="' +
                            module +
                            '"]'
                        );

                    if (button) {
                        updateModuleButton(button);
                    }

                    updateSelectedCount();
                    updateModuleSelectedCounts();
                });

            });


            document
                .getElementById('selectAllPermissions')
                .addEventListener('click', function () {

                    allCheckboxes.forEach(function (checkbox) {
                        checkbox.checked = true;
                    });

                    document
                        .querySelectorAll('.select-module')
                        .forEach(updateModuleButton);

                    updateSelectedCount();
                    updateModuleSelectedCounts();
                });


            document
                .getElementById('clearAllPermissions')
                .addEventListener('click', function () {

                    allCheckboxes.forEach(function (checkbox) {
                        checkbox.checked = false;
                    });

                    document
                        .querySelectorAll('.select-module')
                        .forEach(updateModuleButton);

                    updateSelectedCount();
                    updateModuleSelectedCounts();
                });


            const permissionSearch = document.getElementById('permissionSearch');

            permissionSearch.addEventListener('input', function () {

                const query = this.value.trim().toLowerCase();

                document
                    .querySelectorAll('.permission-row')
                    .forEach(function (row) {

                        const haystack = row.dataset.search || '';

                        row.classList.toggle(
                            'hidden',
                            query !== '' && ! haystack.includes(query)
                        );
                    });
            });


            updateSelectedCount();
            updateModuleSelectedCounts();

        });
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const search = document.getElementById('roleSearch');

            if (!search) return;

            search.addEventListener('input', function () {
                const query = search.value.toLowerCase().trim();

                document.querySelectorAll('[data-role-search]')
                    .forEach(function (role) {
                        role.classList.toggle(
                            'hidden',
                            !role.dataset.roleSearch.includes(query)
                        );
                    });
            });
        });
    </script>

</x-app-layout>
