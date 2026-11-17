@php
    $assignmentPrefix = $reassignment ? 'reassign' : 'assign';
    $assignmentChoices = $reassignment ? $eligibleAssignees->filter(fn ($candidate) => (string) $candidate->id !== (string) $administrativeRequest->assigned_to) : $eligibleAssignees;
@endphp
<p class="text-sm text-slate-600">Request department: <strong>{{ $administrativeRequest->department?->name ?? 'Hospital-wide' }}</strong>. Choose a named executor; a work category does not grant system or approval access.</p>
<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="{{ $assignmentPrefix }}_category" class="mb-2 block text-sm font-semibold">Work category</label>
        <select id="{{ $assignmentPrefix }}_category" name="execution_category" required class="w-full rounded-lg border-slate-300">
            <option value="">Select work category</option>
            @foreach ($executionCategories as $value => $label)
                <option value="{{ $value }}" @selected(old('execution_category', $administrativeRequest->execution_category) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="{{ $assignmentPrefix }}_person" class="mb-2 block text-sm font-semibold">{{ $reassignment ? 'New responsible person' : 'Responsible person' }}</label>
        <select id="{{ $assignmentPrefix }}_person" name="assigned_to" required class="w-full rounded-lg border-slate-300">
            <option value="">Select an eligible active user</option>
            @foreach ($assignmentChoices as $candidate)
                    <option value="{{ $candidate->id }}" @selected((string) old('assigned_to') === (string) $candidate->id)>{{ $candidate->name }} — {{ $candidate->designation ?: str_replace('_', ' ', $candidate->role) }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Only active accounts with Administration View + Complete Assigned Work are listed, including the existing admin override.</p>
    </div>
    <div>
        <label for="{{ $assignmentPrefix }}_due" class="mb-2 block text-sm font-semibold">Required by</label>
        <input id="{{ $assignmentPrefix }}_due" type="date" name="required_by" value="{{ old('required_by', $administrativeRequest->required_by?->toDateString()) }}" class="w-full rounded-lg border-slate-300">
        <p class="mt-1 text-xs text-slate-500">Leave blank to retain any existing target date.</p>
    </div>
</div>
<div>
    <label for="{{ $assignmentPrefix }}_instructions" class="mb-2 block text-sm font-semibold">Execution instructions</label>
    <textarea id="{{ $assignmentPrefix }}_instructions" name="execution_remarks" maxlength="2000" rows="3" class="w-full rounded-lg border-slate-300">{{ old('execution_remarks', $administrativeRequest->execution_remarks) }}</textarea>
</div>
@if ($assignmentChoices->isEmpty())
    <p role="alert" class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">No eligible alternative is available for this selection. Grant the intended executor Administration View and Complete Assigned Work before assigning.</p>
@endif
