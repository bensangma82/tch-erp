<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-slate-900">Roles &amp; Permissions</h2>
        <p class="mt-1 text-sm text-slate-500">Review access by role, inspect individual grants and record permission changes.</p>
    </x-slot>
    <div class="bg-slate-50 py-6">
    <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6">
        @if (session('success'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>@endif
        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                <p class="mt-2">The matrix below shows current saved assignments. Review it before submitting again.</p>
            </div>
        @endif
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <form method="GET" action="{{ route('admin.role-permissions.index') }}" class="grid gap-4 sm:grid-cols-2">
                <div><label for="role" class="mb-2 block text-sm font-semibold">Editing role</label>
                    <select id="role" name="role" class="w-full rounded-lg border-slate-300" onchange="this.form.querySelector('[name=user_id]').value=''; this.form.submit()">
                        @foreach ($roles as $value => $label)<option value="{{ $value }}" @selected($selectedRole === $value)>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div><label for="user_id" class="mb-2 block text-sm font-semibold">Inspect a user in this role</label>
                    <select id="user_id" name="user_id" class="w-full rounded-lg border-slate-300" onchange="this.form.submit()">
                        <option value="">Role grants only</option>
                        @foreach ($roleUsers as $member)<option value="{{ $member->id }}" @selected($selectedUser?->id === $member->id)>{{ $member->name }}{{ $member->is_active ? '' : ' (inactive)' }}</option>@endforeach
                    </select>
                </div>
                <button class="w-fit rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Load role / user</button>
            </form>
            <div class="mt-4 flex flex-wrap gap-3 border-t pt-4 text-sm text-slate-600">
                <span><strong>{{ count($roleUsers) }}</strong> users in this role</span>
                <span><strong>{{ count($assignedPermissionIds) }}</strong> saved role grants</span>
                <span><strong>{{ $permissionsByModule->flatten(1)->count() }}</strong> registered permissions</span>
            </div>
            <p class="mt-3 text-xs leading-5 text-slate-500">Roles control access. Designations identify hospital responsibilities. Administrator and Super Administrator retain the current ERP-wide override and are not edited through this matrix. Route restrictions and workflow checks still apply after a permission is granted.</p>
        </div>

        <details class="rounded-xl border border-slate-200 bg-white p-5" @if ($selectedUser) open @endif>
            <summary class="cursor-pointer font-semibold text-slate-900">{{ $selectedUser ? 'Access grants: '.$selectedUser->name : 'Access grants for this role' }} ({{ $effectiveGrants->count() }})</summary>
            @if ($selectedUser)
                <p class="mt-3 text-sm text-slate-600">Role: <strong>{{ $roles[$selectedRole] }}</strong> · Designation: <strong>{{ $selectedUser->designation ?: 'Not set' }}</strong> · Account: <strong>{{ $selectedUser->is_active ? 'Active' : 'Inactive — authenticated access is blocked by active-user middleware' }}</strong></p>
                <a href="{{ route('admin.users.edit', $selectedUser) }}" class="mt-2 inline-block text-sm font-semibold text-indigo-700">Open user account →</a>
            @endif
            <p class="my-3 text-xs leading-5 text-slate-500">This shows registered grants from role and direct-user assignments, not a guarantee that every action is allowed. Direct-only financial permissions and workflow checks may impose additional requirements. Removing a role grant does not revoke an independent direct-user grant.</p>
            <div class="max-h-80 overflow-auto">
                <table class="w-full text-left text-sm"><thead><tr class="border-b"><th class="py-2">Permission</th><th class="py-2">Source</th></tr></thead><tbody>
                @forelse ($effectiveGrants as $grant)
                    <tr class="border-b border-slate-100"><td class="py-2 pr-3"><div>{{ $grant['permission']->label }}</div><div class="break-all text-xs text-slate-500">{{ $grant['permission']->name }}</div></td><td class="py-2">{{ $grant['source'] }}</td></tr>
                @empty<tr><td colspan="2" class="py-4 text-slate-500">No registered grants found.</td></tr>@endforelse
                </tbody></table>
            </div>
        </details>

        <form id="permissionMatrix" method="POST" action="{{ route('admin.role-permissions.update') }}" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            @csrf
            <input type="hidden" name="role" value="{{ $selectedRole }}">
            <input type="hidden" name="snapshot" value="{{ $snapshot }}">
            <input type="hidden" name="matrix_present" value="1">
            <div class="border-b p-5">
                <h3 class="text-lg font-bold text-slate-900">Editing {{ $roles[$selectedRole] }}</h3>
                <p class="mt-1 text-sm text-slate-500">Saving affects every user with this role. Existing direct grants are preserved.</p>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <label class="sr-only" for="permissionSearch">Search permissions</label>
                    <input id="permissionSearch" type="search" placeholder="Search permission or module" class="w-full rounded-lg border-slate-300 text-sm sm:w-80">
                    <button type="button" id="selectVisible" class="rounded-lg border px-3 py-2 text-sm">Select visible</button>
                    <button type="button" id="clearVisible" class="rounded-lg border px-3 py-2 text-sm">Clear visible</button>
                    <span id="changeSummary" aria-live="polite" class="text-sm font-semibold text-slate-600"></span>
                </div>
            </div>
            <div class="grid gap-4 p-4 lg:grid-cols-2">
                @foreach ($permissionsByModule as $module => $permissions)
                    <section class="permission-module overflow-hidden rounded-xl border border-slate-200">
                        <div class="flex items-center justify-between bg-slate-50 px-4 py-3">
                            <h4 class="text-xs font-bold uppercase tracking-wide text-slate-700">{{ str_replace('_', ' ', $module) }}</h4>
                            <button type="button" class="select-module text-xs font-semibold text-indigo-700">Select visible</button>
                        </div>
                        <div class="divide-y divide-slate-100">
                        @foreach ($permissions as $permission)
                            @php
                                $checked = in_array((int) $permission->id, $assignedPermissionIds, true);
                                $protected = in_array($permission->name, $directOnly, true);
                                $sensitive = preg_match('/refund|cancel|approve|verify|execute|assign|close|pay|reset|delete|journal|tally|permission|system\.users|\.post/', $permission->name);
                            @endphp
                            <label class="permission-row flex items-start gap-3 p-4" data-search="{{ strtolower($module.' '.$permission->label.' '.$permission->name.' '.$permission->description) }}">
                                <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" data-original="{{ $checked ? '1' : '0' }}"
                                    @checked($checked) @disabled($protected)
                                    class="permission-checkbox mt-1 rounded border-slate-300 text-indigo-600">
                                <span class="min-w-0">
                                    <span class="text-sm font-semibold text-slate-800">{{ $permission->label }}</span>
                                    <span class="mt-1 block break-all font-mono text-xs text-slate-400">{{ $permission->name }}</span>
                                    @if ($protected)<span class="mt-1 block text-xs font-semibold text-amber-800">Direct assignment only — manage on the individual user account. Any historical role grant is preserved.</span>
                                    @elseif ($sensitive)<span class="mt-1 block text-xs font-semibold text-amber-800">Sensitive action — select individually</span>@endif
                                    <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $permission->description }}</span>
                                </span>
                            </label>
                        @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
            <div class="space-y-3 border-t bg-slate-50 p-5">
                <label for="reason" class="block text-sm font-semibold text-slate-800">Reason for this permission change</label>
                <textarea id="reason" name="reason" rows="2" maxlength="1000" placeholder="Describe the responsibility or access change (10–1000 characters when changing grants)." class="w-full rounded-lg border-slate-300">{{ old('reason') }}</textarea>
                <div id="changeList" class="text-sm text-slate-600" aria-live="polite"></div>
                <button class="rounded-lg bg-indigo-700 px-5 py-3 text-sm font-semibold text-white">Save {{ $roles[$selectedRole] }} permissions</button>
            </div>
        </form>

        <details class="rounded-xl border border-slate-200 bg-white p-5">
            <summary class="cursor-pointer font-semibold text-slate-900">Recent role permission changes</summary>
            <p class="mt-2 text-xs text-slate-500">Latest 10 changes recorded through this editor after this update. Historical grants and edits made elsewhere have no backfilled audit entry.</p>
            <div class="mt-4 divide-y divide-slate-100">
                @forelse ($audits as $audit)
                    <article class="space-y-2 py-4 text-sm">
                        <p class="font-semibold">{{ $audit->actor_name }} · {{ $audit->created_at }}</p>
                        <p class="whitespace-pre-line text-slate-600">{{ $audit->reason }}</p>
                        <p class="break-words text-xs text-emerald-700">Added: {{ implode(', ', json_decode($audit->added_permissions, true)) ?: 'None' }}</p>
                        <p class="break-words text-xs text-red-700">Removed: {{ implode(', ', json_decode($audit->removed_permissions, true)) ?: 'None' }}</p>
                    </article>
                @empty<p class="py-4 text-sm text-slate-500">No role changes recorded yet.</p>@endforelse
            </div>
        </details>
    </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('permissionMatrix');
        const boxes = [...form.querySelectorAll('.permission-checkbox:not(:disabled)')];
        const rows = [...form.querySelectorAll('.permission-row')];
        const sensitive = /refund|cancel|approve|verify|execute|assign|close|pay|reset|delete|journal|tally|permission|system\.users|\.post/;
        const changed = () => boxes.filter(b => b.checked !== (b.dataset.original === '1'));
        function refresh() {
            const diff = changed();
            const added = diff.filter(b => b.checked), removed = diff.filter(b => !b.checked);
            document.getElementById('changeSummary').textContent = `${added.length} to add · ${removed.length} to remove`;
            const list = document.getElementById('changeList');
            list.replaceChildren();
            diff.forEach(box => {
                const line = document.createElement('div');
                line.textContent = `${box.checked ? 'Add' : 'Remove'}: ${box.closest('label').querySelector('.font-mono').textContent.trim()}`;
                list.appendChild(line);
            });
            document.getElementById('reason').required = diff.length > 0;
            document.getElementById('reason').minLength = diff.length ? 10 : 0;
        }
        function bulk(container, selected) {
            container.querySelectorAll('.permission-row:not(.hidden) .permission-checkbox:not(:disabled)').forEach(box => {
                const name = box.closest('label').querySelector('.font-mono').textContent.trim();
                if (!selected || !sensitive.test(name)) box.checked = selected;
            });
            refresh();
        }
        boxes.forEach(b => b.addEventListener('change', refresh));
        document.getElementById('selectVisible').addEventListener('click', () => bulk(form, true));
        document.getElementById('clearVisible').addEventListener('click', () => bulk(form, false));
        form.querySelectorAll('.select-module').forEach(button => button.addEventListener('click', () => bulk(button.closest('section'), true)));
        document.getElementById('permissionSearch').addEventListener('input', event => {
            const query = event.target.value.trim().toLowerCase();
            rows.forEach(row => row.classList.toggle('hidden', !row.dataset.search.includes(query)));
        });
        form.addEventListener('submit', event => {
            if (changed().length && !window.confirm('Apply the listed permission changes to every user in this role?')) event.preventDefault();
        });
        refresh();
    });
    </script>
</x-app-layout>
