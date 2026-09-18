<x-app-layout>

    <x-slot name="header">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">
                    Administrative Request
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $administrativeRequest->request_no }}
                </p>
            </div>


            <a
                href="{{ route('administration.requests.index') }}"
                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
            >
                Back to Administration
            </a>

        </div>

    </x-slot>


    @php

        $statusLabels = [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'verified' => 'Verified',
            'pending_ms_approval' => 'Pending MS Approval',
            'ms_approved' => 'MS Approved',
            'ms_rejected' => 'MS Rejected',
            'higher_approval_required' => 'Higher Approval Required',
            'higher_approved' => 'Higher Approved',
            'execution_in_progress' => 'Execution In Progress',
            'executed' => 'Executed',
            'closed' => 'Closed',
            'cancelled' => 'Cancelled',
        ];

        $statusClasses = [
            'draft' => 'bg-slate-100 text-slate-700',
            'submitted' => 'bg-blue-50 text-blue-700',
            'verified' => 'bg-cyan-50 text-cyan-700',
            'pending_ms_approval' => 'bg-amber-50 text-amber-700',
            'ms_approved' => 'bg-emerald-50 text-emerald-700',
            'ms_rejected' => 'bg-red-50 text-red-700',
            'higher_approval_required' => 'bg-orange-50 text-orange-700',
            'higher_approved' => 'bg-teal-50 text-teal-700',
            'execution_in_progress' => 'bg-violet-50 text-violet-700',
            'executed' => 'bg-indigo-50 text-indigo-700',
            'closed' => 'bg-slate-200 text-slate-700',
            'cancelled' => 'bg-slate-100 text-slate-500',
        ];

        $typeLabels = [
            'purchase' => 'Purchase',
            'recruitment' => 'Recruitment',
            'finance' => 'Finance',
            'contract' => 'Contract',
            'project' => 'Project',
            'maintenance' => 'Maintenance',
            'hr' => 'HR',
            'other' => 'Other',
        ];

        $priorityClasses = [
            'low' => 'bg-slate-100 text-slate-600',
            'normal' => 'bg-blue-50 text-blue-700',
            'high' => 'bg-amber-50 text-amber-700',
            'urgent' => 'bg-red-50 text-red-700',
        ];

        $status = $administrativeRequest->status ?? 'draft';
        $priority = $administrativeRequest->priority ?? 'normal';

    @endphp


    <div class="min-h-screen bg-slate-50 py-6">

        <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">


            @if (session('success'))

                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>

            @endif


            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-start sm:justify-between">

                    <div>

                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Request Number
                        </div>

                        <div class="mt-1 text-2xl font-bold text-slate-900">
                            {{ $administrativeRequest->request_no }}
                        </div>

                        <div class="mt-2 text-lg font-semibold text-slate-700">
                            {{ $administrativeRequest->title }}
                        </div>

                    </div>


                    <div class="flex flex-wrap gap-2">

                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $priorityClasses[$priority] ?? 'bg-slate-100 text-slate-600' }}">
                            {{ strtoupper($priority) }}
                        </span>

                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses[$status] ?? 'bg-slate-100 text-slate-700' }}">
                            {{ $statusLabels[$status] ?? ucwords(str_replace('_', ' ', $status)) }}
                        </span>

                    </div>

                </div>


                <div class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-4">

                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Request Type
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $typeLabels[$administrativeRequest->request_type] ?? ucwords(str_replace('_', ' ', $administrativeRequest->request_type)) }}
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Department
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $administrativeRequest->department?->name ?? 'Hospital-wide' }}
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Estimated Amount
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            @if ($administrativeRequest->estimated_amount !== null)
                                ₹{{ number_format((float) $administrativeRequest->estimated_amount, 2) }}
                            @else
                                —
                            @endif
                        </div>
                    </div>


                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Created By
                        </div>

                        <div class="mt-2 font-semibold text-slate-900">
                            {{ $administrativeRequest->createdBy?->name ?? '—' }}
                        </div>

                        <div class="mt-1 text-xs text-slate-500">
                            {{ $administrativeRequest->created_at?->format('d M Y, h:i A') }}
                        </div>
                    </div>

                </div>

            </div>


            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">
                    <h3 class="font-semibold text-slate-900">
                        Description / Justification
                    </h3>
                </div>
                {{-- ========================================================= --}}
{{-- SUPPORTING DOCUMENTS --}}
{{-- ========================================================= --}}

<div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

    <div class="border-b border-slate-100 px-6 py-5">

        <h3 class="font-semibold text-slate-900">
            Supporting Documents
        </h3>

        <p class="mt-1 text-sm text-slate-500">
            Upload quotations, estimates, DPRs, specifications, contracts,
            correspondence, photographs or other supporting records.
        </p>

    </div>


    <div class="space-y-6 p-6">


        {{-- Upload form --}}

        <form
            method="POST"
            action="{{ route('administration.requests.documents.store', $administrativeRequest) }}"
            enctype="multipart/form-data"
            class="rounded-xl border border-slate-200 bg-slate-50 p-5"
        >

            @csrf


            <div class="grid gap-4 md:grid-cols-2">


                <div>

                    <label
                        for="document_type"
                        class="mb-2 block text-sm font-semibold text-slate-700"
                    >
                        Document Type
                    </label>

                    <select
                        id="document_type"
                        name="document_type"
                        class="w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    >

                        <option value="">
                            Select type
                        </option>

                        <option value="quotation">
                            Quotation
                        </option>

                        <option value="estimate">
                            Estimate
                        </option>

                        <option value="dpr">
                            DPR / Proposal
                        </option>

                        <option value="technical_specification">
                            Technical Specification
                        </option>

                        <option value="contract">
                            Contract / Agreement
                        </option>

                        <option value="approval_letter">
                            Approval / Sanction Letter
                        </option>

                        <option value="correspondence">
                            Correspondence
                        </option>

                        <option value="photograph">
                            Photograph
                        </option>

                        <option value="other">
                            Other
                        </option>

                    </select>

                </div>


                <div>

                    <label
                        for="document_title"
                        class="mb-2 block text-sm font-semibold text-slate-700"
                    >
                        Document Title
                        <span class="text-red-600">*</span>
                    </label>

                    <input
                        id="document_title"
                        name="title"
                        type="text"
                        required
                        maxlength="255"
                        value="{{ old('title') }}"
                        placeholder="Example: Vendor quotation - ABC Medical"
                        class="w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    >

                    @error('title')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                <div class="md:col-span-2">

                    <label
                        for="document"
                        class="mb-2 block text-sm font-semibold text-slate-700"
                    >
                        Select File
                        <span class="text-red-600">*</span>
                    </label>

                    <input
                        id="document"
                        name="document"
                        type="file"
                        required
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                        class="block w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-700 shadow-sm"
                    >

                    <p class="mt-1 text-xs text-slate-500">
                        PDF, Word, Excel or image files. Maximum 10 MB.
                    </p>

                    @error('document')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                <div class="md:col-span-2">

                    <label
                        for="document_remarks"
                        class="mb-2 block text-sm font-semibold text-slate-700"
                    >
                        Remarks
                    </label>

                    <textarea
                        id="document_remarks"
                        name="remarks"
                        rows="2"
                        maxlength="2000"
                        placeholder="Optional remarks about this document..."
                        class="w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    >{{ old('remarks') }}</textarea>

                </div>

            </div>


            <div class="mt-4 flex justify-end">

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                >
                    Upload Document
                </button>

            </div>

        </form>


        {{-- Uploaded documents --}}

        <div>

            <div class="mb-3 text-sm font-semibold text-slate-900">
                Attached Documents
            </div>


            @forelse ($administrativeRequest->documents as $document)

                <div class="mb-3 rounded-xl border border-slate-200 bg-white p-4">

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <div class="font-semibold text-slate-900">
                                {{ $document->title }}
                            </div>

                            <div class="mt-1 text-xs text-slate-500">

                                {{ $document->file_name }}

                                @if ($document->document_type)
                                    ·
                                    {{ ucwords(str_replace('_', ' ', $document->document_type)) }}
                                @endif

                                @if ($document->file_size)
                                    ·
                                    {{ number_format($document->file_size / 1024, 1) }} KB
                                @endif

                            </div>


                            <div class="mt-1 text-xs text-slate-500">

                                Uploaded by
                                {{ $document->uploadedBy?->name ?? '—' }}

                                @if ($document->uploaded_at)
                                    on
                                    {{ $document->uploaded_at->format('d M Y, h:i A') }}
                                @endif

                            </div>


                            @if ($document->remarks)

                                <div class="mt-2 text-sm text-slate-600">
                                    {{ $document->remarks }}
                                </div>

                            @endif

                        </div>


                        <div>

                            <a
                                href="{{ route(
    'administration.requests.documents.view',
    [
        'administrativeRequest' => $administrativeRequest,
        'document' => $document,
    ]
) }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                            >
                                View Document
                            </a>

                        </div>

                    </div>

                </div>

            @empty

                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center">

                    <div class="text-sm font-semibold text-slate-700">
                        No supporting documents uploaded
                    </div>

                    <div class="mt-1 text-xs text-slate-500">
                        Supporting documents can be added at any stage of the request.
                    </div>

                </div>

            @endforelse

        </div>

    </div>

</div>

                <div class="p-6">
                    <div class="whitespace-pre-line text-sm leading-7 text-slate-700">
                        {{ $administrativeRequest->description ?: 'No description provided.' }}
                    </div>
                </div>

            </div>


            @if ($administrativeRequest->remarks)

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">
                        <h3 class="font-semibold text-slate-900">
                            Internal Remarks
                        </h3>
                    </div>

                    <div class="p-6">
                        <div class="whitespace-pre-line text-sm leading-7 text-slate-700">
                            {{ $administrativeRequest->remarks }}
                        </div>
                    </div>

                </div>

            @endif


            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-100 px-6 py-5">

                    <h3 class="font-semibold text-slate-900">
                        Approval Workflow
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Administrative verification, Medical Superintendent decision and execution tracking
                    </p>

                </div>


                @php
                    $needsHigherApproval = (bool) $administrativeRequest->requires_higher_approval;
                    $workflowColumns = $needsHigherApproval ? 'md:grid-cols-5' : 'md:grid-cols-4';
                    $executionStepNumber = $needsHigherApproval ? 5 : 4;
                @endphp


                <div class="grid gap-4 p-6 {{ $workflowColumns }}">

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">

                        <div class="text-xs font-semibold uppercase text-slate-400">
                            1. Draft
                        </div>

                        <div class="mt-2 text-sm font-semibold text-slate-900">
                            {{ $administrativeRequest->createdBy?->name ?? '—' }}
                        </div>

                        @if ($administrativeRequest->created_at)
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $administrativeRequest->created_at->format('d M Y, h:i A') }}
                            </div>
                        @endif

                    </div>


                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">

                        <div class="text-xs font-semibold uppercase text-slate-400">
                            2. Administrator Verification
                        </div>

                        <div class="mt-2 text-sm font-semibold text-slate-900">
                            {{ $administrativeRequest->verifiedBy?->name ?? 'Pending' }}
                        </div>

                        @if ($administrativeRequest->verified_at)
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $administrativeRequest->verified_at->format('d M Y, h:i A') }}
                            </div>
                        @endif

                    </div>


                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">

                        <div class="text-xs font-semibold uppercase text-slate-400">
                            3. MS Decision
                        </div>

                        <div class="mt-2 text-sm font-semibold text-slate-900">
                            {{ $administrativeRequest->msDecidedBy?->name ?? 'Pending' }}
                        </div>

                        @if ($administrativeRequest->ms_decided_at)
                            <div class="mt-1 text-xs text-slate-500">
                                {{ $administrativeRequest->ms_decided_at->format('d M Y, h:i A') }}
                            </div>
                        @endif

                    </div>


                    @if ($needsHigherApproval)

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">

                            <div class="text-xs font-semibold uppercase text-slate-400">
                                4. Higher Approval
                            </div>

                            <div class="mt-2 text-sm font-semibold text-slate-900">
                                @if ($status === 'higher_approval_required')
                                    Pending
                                @elseif (in_array($status, ['higher_approved', 'execution_in_progress', 'executed', 'closed'], true))
                                    {{ $administrativeRequest->higher_authority ?? 'Higher Approved' }}
                                @else
                                    Pending
                                @endif
                            </div>

                            @if ($administrativeRequest->higher_approved_at)
                                <div class="mt-1 text-xs text-slate-500">
                                    Approved {{ $administrativeRequest->higher_approved_at->format('d M Y') }}
                                </div>
                            @elseif ($administrativeRequest->higher_authority)
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $administrativeRequest->higher_authority }}
                                </div>
                            @endif

                        </div>

                    @endif


                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">

                        <div class="text-xs font-semibold uppercase text-slate-400">
                            {{ $executionStepNumber }}. Execution
                        </div>

                        <div class="mt-2 text-sm font-semibold text-slate-900">

                            @if ($status === 'execution_in_progress')
                                {{ $administrativeRequest->assigned_role ?? 'Execution In Progress' }}
                            @elseif (in_array($status, ['executed', 'closed'], true))
                                {{ $administrativeRequest->executedBy?->name ?? 'Executed' }}
                            @else
                                Pending
                            @endif

                        </div>

                        @if ($status === 'execution_in_progress' && $administrativeRequest->execution_started_at)
                            <div class="mt-1 text-xs text-slate-500">
                                Started {{ $administrativeRequest->execution_started_at->format('d M Y, h:i A') }}
                            </div>
                        @elseif (in_array($status, ['executed', 'closed'], true) && $administrativeRequest->executed_at)
                            <div class="mt-1 text-xs text-slate-500">
                                Completed {{ $administrativeRequest->executed_at->format('d M Y, h:i A') }}
                            </div>
                        @endif

                    </div>

                </div>

            </div>


            @if ($needsHigherApproval)

                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                    <div class="border-b border-slate-100 px-6 py-5">

                        <h3 class="font-semibold text-slate-900">
                            Higher Approval
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Record approval from the designated higher authority before execution.
                        </p>

                    </div>


                    <div class="p-6">

                        @if ($status === 'higher_approval_required')

                            <form
                                method="POST"
                                action="{{ route(
                                    'administration.requests.higher-approval',
                                    $administrativeRequest
                                ) }}"
                                class="space-y-5"
                            >

                                @csrf

                                <div class="grid gap-4 md:grid-cols-2">

                                    <div>
                                        <label
                                            for="higher_authority"
                                            class="mb-2 block text-sm font-semibold text-slate-700"
                                        >
                                            Higher Authority
                                            <span class="text-red-600">*</span>
                                        </label>

                                        <select
                                            id="higher_authority"
                                            name="higher_authority"
                                            required
                                            class="w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                        >
                                            <option value="">Select authority</option>

                                            @foreach (['Board', 'CBCNEI', 'Chairman', 'Government', 'Other'] as $authority)
                                                <option
                                                    value="{{ $authority }}"
                                                    @selected(
                                                        old(
                                                            'higher_authority',
                                                            $administrativeRequest->higher_authority
                                                        ) === $authority
                                                    )
                                                >
                                                    {{ $authority }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('higher_authority')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>


                                    <div>
                                        <label
                                            for="higher_approval_reference"
                                            class="mb-2 block text-sm font-semibold text-slate-700"
                                        >
                                            Approval / Reference Number
                                        </label>

                                        <input
                                            id="higher_approval_reference"
                                            name="higher_approval_reference"
                                            type="text"
                                            maxlength="255"
                                            value="{{ old(
                                                'higher_approval_reference',
                                                $administrativeRequest->higher_approval_reference
                                            ) }}"
                                            placeholder="Example: CBCNEI/FIN/2026/123"
                                            class="w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                        >

                                        @error('higher_approval_reference')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>


                                    <div>
                                        <label
                                            for="higher_approved_at"
                                            class="mb-2 block text-sm font-semibold text-slate-700"
                                        >
                                            Approval Date
                                            <span class="text-red-600">*</span>
                                        </label>

                                        <input
                                            id="higher_approved_at"
                                            name="higher_approved_at"
                                            type="date"
                                            required
                                            value="{{ old('higher_approved_at', now()->format('Y-m-d')) }}"
                                            class="w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                        >

                                        @error('higher_approved_at')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>


                                    <div class="md:col-span-2">
                                        <label
                                            for="higher_approval_remarks"
                                            class="mb-2 block text-sm font-semibold text-slate-700"
                                        >
                                            Remarks
                                        </label>

                                        <textarea
                                            id="higher_approval_remarks"
                                            name="higher_approval_remarks"
                                            rows="3"
                                            maxlength="2000"
                                            placeholder="Enter approval conditions, observations or other remarks..."
                                            class="w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-slate-500 focus:ring-slate-500"
                                        >{{ old('higher_approval_remarks') }}</textarea>

                                        @error('higher_approval_remarks')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                </div>

                                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                                    <p class="text-sm text-amber-900">
                                        Upload the approval letter, sanction order or related document
                                        under <strong>Supporting Documents</strong> for audit purposes.
                                    </p>
                                </div>

                                <div class="flex justify-end">
                                    <button
                                        type="submit"
                                        class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                                    >
                                        Record Higher Approval
                                    </button>
                                </div>

                            </form>

                        @else

                            <div class="grid gap-4 md:grid-cols-2">

                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Higher Authority
                                    </div>
                                    <div class="mt-1 font-medium text-slate-900">
                                        {{ $administrativeRequest->higher_authority ?? '—' }}
                                    </div>
                                </div>

                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Approval Reference
                                    </div>
                                    <div class="mt-1 font-medium text-slate-900">
                                        {{ $administrativeRequest->higher_approval_reference ?? '—' }}
                                    </div>
                                </div>

                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Approval Date
                                    </div>
                                    <div class="mt-1 font-medium text-slate-900">
                                        {{ $administrativeRequest->higher_approved_at
                                            ? $administrativeRequest->higher_approved_at->format('d M Y')
                                            : '—' }}
                                    </div>
                                </div>

                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Recorded By
                                    </div>
                                    <div class="mt-1 font-medium text-slate-900">
                                        {{ $administrativeRequest->higherApprovalRecordedBy?->name ?? '—' }}
                                    </div>
                                </div>

                            </div>

                            @if ($administrativeRequest->higher_approval_remarks)
                                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        Approval Remarks
                                    </div>
                                    <div class="mt-2 text-sm text-slate-700">
                                        {{ $administrativeRequest->higher_approval_remarks }}
                                    </div>
                                </div>
                            @endif

                        @endif

                    </div>

                </div>

            @endif


            @if ($status === 'draft')

                <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5">

                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <div class="text-sm font-bold text-blue-900">
                                Draft Request
                            </div>

                            <div class="mt-2 text-sm leading-6 text-blue-700">
                                Review the request before submitting it for Administrator verification.
                                Once submitted, it enters the formal approval workflow.
                            </div>

                        </div>


                        <form
                            method="POST"
                            action="{{ route('administration.requests.submit', $administrativeRequest) }}"
                            onsubmit="return confirm('Submit this request for Administrator verification?');"
                        >

                            @csrf

                            <button
                                type="submit"
                                class="inline-flex whitespace-nowrap items-center justify-center rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-800"
                            >
                                Submit for Verification
                            </button>

                        </form>

                    </div>

                </div>

            @endif


            @if ($status === 'submitted')

                <div class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5">

                    <div class="mb-4">

                        <div class="text-sm font-bold text-cyan-900">
                            Administrator Verification
                        </div>

                        <div class="mt-2 text-sm leading-6 text-cyan-700">
                            Verify the request for completeness, documentation and administrative requirements
                            before forwarding it to the Medical Superintendent.
                        </div>

                    </div>


                    <form
                        method="POST"
                        action="{{ route('administration.requests.verify', $administrativeRequest) }}"
                        onsubmit="return confirm('Verify this request and forward it to the Medical Superintendent?');"
                        class="space-y-4"
                    >

                        @csrf


                        <div>

                            <label
                                for="verification_remarks"
                                class="mb-2 block text-sm font-semibold text-cyan-900"
                            >
                                Verification Remarks
                            </label>

                            <textarea
                                id="verification_remarks"
                                name="verification_remarks"
                                rows="4"
                                maxlength="2000"
                                placeholder="Example: Quotations verified, budget availability checked, specifications complete..."
                                class="w-full rounded-lg border-cyan-300 bg-white shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >{{ old('verification_remarks') }}</textarea>

                            @error('verification_remarks')
                                <p class="mt-1 text-sm text-red-600">
                                    {{ $message }}
                                </p>
                            @enderror

                        </div>


                        <div class="flex justify-end">

                            <button
    type="submit"
    class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
>
    Verify &amp; Forward to MS
</button>

                        </div>

                    </form>

                </div>

            @endif
@if ($status === 'pending_ms_approval')

    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">

        <div class="mb-4">

            <div class="text-sm font-bold text-amber-900">
                Medical Superintendent Decision
            </div>

            <div class="mt-2 text-sm leading-6 text-amber-700">
                Review the administrative verification, institutional need,
                financial implications and delegated approval requirements.
            </div>

        </div>


        <form
            method="POST"
            action="{{ route('administration.requests.ms-approve', $administrativeRequest) }}"
            class="space-y-4"
        >

            @csrf


            <div>

                <label
                    for="ms_remarks"
                    class="mb-2 block text-sm font-semibold text-amber-900"
                >
                    MS Remarks
                </label>

                <textarea
                    id="ms_remarks"
                    name="ms_remarks"
                    rows="4"
                    maxlength="2000"
                    placeholder="Enter approval remarks, conditions, justification or instructions..."
                    class="w-full rounded-lg border-amber-300 bg-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                >{{ old('ms_remarks') }}</textarea>

                @error('ms_remarks')
                    <p class="mt-1 text-sm text-red-600">
                        {{ $message }}
                    </p>
                @enderror

            </div>


            <div class="rounded-xl border border-amber-200 bg-white p-4">

                <label class="flex items-start gap-3">

                    <input
                        type="checkbox"
                        name="requires_higher_approval"
                        value="1"
                        class="mt-1 rounded border-slate-300 text-slate-900 focus:ring-slate-500"
                    >

                    <span>

                        <span class="block text-sm font-semibold text-slate-900">
                            Higher Approval Required
                        </span>

                        <span class="mt-1 block text-xs leading-5 text-slate-500">
                            Select this when the request exceeds the Medical Superintendent's
                            delegated authority or requires Board / CBCNEI / other higher approval.
                        </span>

                    </span>

                </label>


                <div class="mt-4">

                    <label
                        for="higher_authority"
                        class="mb-2 block text-sm font-semibold text-slate-700"
                    >
                        Higher Authority
                    </label>

                    <select
                        id="higher_authority"
                        name="higher_authority"
                        class="w-full rounded-lg border-slate-300 bg-white shadow-sm focus:border-slate-500 focus:ring-slate-500"
                    >

                        <option value="">
                            Select if applicable
                        </option>

                        <option value="Board">
                            Board
                        </option>

                        <option value="CBCNEI">
                            CBCNEI
                        </option>

                        <option value="Chairman">
                            Chairman
                        </option>

                        <option value="Government">
                            Government
                        </option>

                        <option value="Other">
                            Other
                        </option>

                    </select>

                </div>

            </div>


            <div class="flex flex-col gap-3 border-t border-amber-200 pt-4 sm:flex-row sm:justify-end">

                <button
                    type="submit"
                    formaction="{{ route('administration.requests.ms-return', $administrativeRequest) }}"
                    onclick="return confirm('Return this request for correction and re-verification?');"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50"
                >
                    Return for Correction
                </button>


                <button
                    type="submit"
                    formaction="{{ route('administration.requests.ms-reject', $administrativeRequest) }}"
                    onclick="return confirm('Reject this administrative request?');"
                    class="inline-flex items-center justify-center rounded-lg border border-red-300 bg-white px-5 py-2.5 text-sm font-semibold text-red-700 shadow-sm hover:bg-red-50"
                >
                    Reject
                </button>


                <button
                    type="submit"
                    onclick="return confirm('Approve this administrative request?');"
                    class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                >
                    Approve
                </button>

            </div>

        </form>

    </div>

@endif


@if (in_array($status, ['ms_approved', 'higher_approved', 'execution_in_progress', 'executed'], true))

    <div class="rounded-2xl border border-violet-200 bg-violet-50 p-5">

        <div class="mb-4">

            <div class="text-sm font-bold text-violet-900">
                Execution
            </div>

            <div class="mt-2 text-sm leading-6 text-violet-700">
                Track implementation of the approved administrative decision.
            </div>

        </div>


        @if (in_array($status, ['ms_approved', 'higher_approved'], true))

            <form
                method="POST"
                action="{{ route('administration.requests.start-execution', $administrativeRequest) }}"
                class="space-y-4"
            >

                @csrf


                <div>

                    <label
                        for="execution_category"
                        class="mb-2 block text-sm font-semibold text-violet-900"
                    >
                        Work Category
                    </label>

                    <select
                        id="execution_category"
                        name="execution_category"
                        required
                        class="w-full rounded-lg border-violet-300 bg-white shadow-sm focus:border-violet-500 focus:ring-violet-500"
                    >

                        <option value="">
                            Select work category
                        </option>

                        <option
                            value="clinical"
                            @selected(old('execution_category') === 'clinical')
                        >
                            Clinical Work — Deputy Medical Superintendent
                        </option>

                        <option
                            value="nursing"
                            @selected(old('execution_category') === 'nursing')
                        >
                            Nursing Work — Nursing Superintendent
                        </option>

                        <option
                            value="non_clinical"
                            @selected(old('execution_category') === 'non_clinical')
                        >
                            Non-Clinical Work — Manager
                        </option>

                    </select>

                    @error('execution_category')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                <div>

                    <label
                        for="execution_remarks"
                        class="mb-2 block text-sm font-semibold text-violet-900"
                    >
                        Execution Instructions / Remarks
                    </label>

                    <textarea
                        id="execution_remarks"
                        name="execution_remarks"
                        rows="4"
                        maxlength="2000"
                        placeholder="Enter implementation instructions, responsible person, vendor follow-up, timeline or other remarks..."
                        class="w-full rounded-lg border-violet-300 bg-white shadow-sm focus:border-violet-500 focus:ring-violet-500"
                    ></textarea>

                </div>


                <div class="flex justify-end">

                    <button
                        type="submit"
                        onclick="return confirm('Start execution of this request?');"
                        class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                    >
                        Start Execution
                    </button>

                </div>

            </form>

        @endif


        @if ($status === 'execution_in_progress')

            <div class="mb-5 grid gap-4 sm:grid-cols-2">

                <div class="rounded-xl border border-violet-200 bg-white p-4">

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Responsible Role
                    </div>

                    <div class="mt-2 text-sm font-semibold text-slate-900">
                        {{ $administrativeRequest->assigned_role ?? 'Not assigned' }}
                    </div>

                </div>


                <div class="rounded-xl border border-violet-200 bg-white p-4">

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Execution Started
                    </div>

                    <div class="mt-2 text-sm font-semibold text-slate-900">
                        {{ $administrativeRequest->execution_started_at?->format('d M Y, h:i A') ?? '—' }}
                    </div>

                </div>

            </div>


            @if ($administrativeRequest->execution_remarks)

                <div class="mb-5 rounded-xl border border-violet-200 bg-white p-4">

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Execution Instructions / Remarks
                    </div>

                    <div class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">
                        {{ $administrativeRequest->execution_remarks }}
                    </div>

                </div>

            @endif


            <form
                method="POST"
                action="{{ route('administration.requests.mark-executed', $administrativeRequest) }}"
                class="space-y-4"
            >

                @csrf


                <div>

                    <label
                        for="execution_remarks_completed"
                        class="mb-2 block text-sm font-semibold text-violet-900"
                    >
                        Completion Remarks
                    </label>

                    <textarea
                        id="execution_remarks_completed"
                        name="execution_remarks"
                        rows="4"
                        maxlength="2000"
                        placeholder="Enter completion details, vendor delivery status, implementation outcome or other final remarks..."
                        class="w-full rounded-lg border-violet-300 bg-white shadow-sm focus:border-violet-500 focus:ring-violet-500"
                    >{{ old('execution_remarks') }}</textarea>

                    @error('execution_remarks')
                        <p class="mt-1 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                <div class="flex justify-end">

                    <button
                        type="submit"
                        onclick="return confirm('Mark this request as executed?');"
                        class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                    >
                        Mark Executed
                    </button>

                </div>

            </form>

        @endif


        @if ($status === 'executed')

            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4">

                <div class="text-sm font-bold text-emerald-900">
                    Execution Completed
                </div>

                <div class="mt-2 text-sm text-emerald-700">
                    Executed by:
                    <span class="font-semibold">
                        {{ $administrativeRequest->executedBy?->name ?? '—' }}
                    </span>
                </div>

                <div class="mt-1 text-sm text-emerald-700">
                    Completed:
                    {{ $administrativeRequest->executed_at?->format('d M Y, h:i A') ?? '—' }}
                </div>

                @if ($administrativeRequest->execution_remarks)

                    <div class="mt-3 whitespace-pre-line text-sm leading-6 text-emerald-700">
                        {{ $administrativeRequest->execution_remarks }}
                    </div>

                @endif

            </div>


            <form
                method="POST"
                action="{{ route('administration.requests.close', $administrativeRequest) }}"
                class="space-y-4"
            >

                @csrf


                <div>

                    <label
                        for="closure_remarks"
                        class="mb-2 block text-sm font-semibold text-violet-900"
                    >
                        Closure Remarks
                    </label>

                    <textarea
                        id="closure_remarks"
                        name="closure_remarks"
                        rows="3"
                        maxlength="2000"
                        placeholder="Optional final closure remarks..."
                        class="w-full rounded-lg border-violet-300 bg-white shadow-sm focus:border-violet-500 focus:ring-violet-500"
                    ></textarea>

                </div>


                <div class="flex justify-end">

                    <button
                        type="submit"
                        onclick="return confirm('Close this administrative request?');"
                        class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800"
                    >
                        Close Request
                    </button>

                </div>

            </form>

        @endif

    </div>

@endif

        </div>

    </div>

</x-app-layout>
