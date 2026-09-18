<x-app-layout>

    <x-slot name="header">

        <div class="flex items-center justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    User Management
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Manage ERP users, roles and account access
                </p>
            </div>


            <a
                href="{{ route('admin.users.create') }}"
                class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
            >
                Add User
            </a>

        </div>

    </x-slot>


    <div class="bg-slate-50 py-6">

        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif


            @if (session('error'))

                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>

            @endif


            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">


                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        ERP Users
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Accounts authorized to access the hospital ERP
                    </p>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    User
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Email
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Role
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Status
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100 bg-white">

                            @forelse ($users as $user)

                                @php
                                    $roleLabels = [
                                        'admin' => 'Administrator',
                                        'reception' => 'Reception',
                                        'nursing' => 'Nursing',
                                        'billing' => 'Billing',
                                        'laboratory' => 'Laboratory',
                                        'radiology' => 'Radiology',
                                        'doctor' => 'Doctor',
                                    ];

                                    $roleLabel =
                                        $roleLabels[$user->role]
                                        ?? ucfirst($user->role ?? 'Staff');
                                @endphp


                                <tr class="transition hover:bg-slate-50">

                                    <td class="px-6 py-4">

                                        <div class="font-semibold text-slate-900">
                                            {{ $user->name }}
                                        </div>

                                        @if (auth()->id() === $user->id)

                                            <div class="mt-1 text-xs font-medium text-blue-600">
                                                Current user
                                            </div>

                                        @endif

                                    </td>


                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        {{ $user->email }}
                                    </td>


                                    <td class="px-6 py-4">

                                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                            {{ $roleLabel }}
                                        </span>

                                    </td>


                                    <td class="px-6 py-4">

                                        @if ($user->is_active)

                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">

                                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>

                                                Active

                                            </span>

                                        @else

                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">

                                                <span class="h-2 w-2 rounded-full bg-red-500"></span>

                                                Inactive

                                            </span>

                                        @endif

                                    </td>


                                    <td class="px-6 py-4">

                                        <div class="flex items-center justify-end gap-2">


                                            <a
                                                href="{{ route('admin.users.edit', $user) }}"
                                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
                                            >
                                                Edit
                                            </a>


                                            @if (auth()->id() !== $user->id)

                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.users.status', $user) }}"
                                                    onsubmit="return confirm('{{ $user->is_active ? 'Deactivate this user?' : 'Activate this user?' }}');"
                                                >

                                                    @csrf
                                                    @method('PATCH')


                                                    @if ($user->is_active)

                                                        <button
                                                            type="submit"
                                                            class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100"
                                                        >
                                                            Deactivate
                                                        </button>

                                                    @else

                                                        <button
                                                            type="submit"
                                                            class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
                                                        >
                                                            Activate
                                                        </button>

                                                    @endif

                                                </form>

                                            @else

                                                <span class="text-xs text-slate-400">
                                                    Protected
                                                </span>

                                            @endif

                                        </div>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td
                                        colspan="5"
                                        class="px-6 py-10 text-center text-sm text-slate-500"
                                    >
                                        No users found.
                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</x-app-layout>