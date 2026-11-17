<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeRequest;
use App\Models\Department;
use App\Models\User;
use App\Services\Administration\AdministrativeWorkAssignment;
use App\Support\AdministrativeRequestAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Models\AdministrativeRequestDocument;
use Illuminate\Support\Facades\Storage;

class AdministrativeRequestController extends Controller
{
    private function requireAccess(string $action, ?AdministrativeRequest $item = null): void
    {
        abort_unless(
            AdministrativeRequestAccess::allows(Auth::user(), $action, $item),
            403,
            'You do not have permission to perform this administrative action.'
        );
    }

    public function index(Request $request): View
    {
        $this->requireAccess('view');

        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'request_type' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'priority' => ['nullable', 'string', 'max:30'],
            'queue' => ['nullable', 'in:verification,ms,higher,execution,closure,closed,overdue,mine'],
        ]);
        $query = AdministrativeRequest::query()
            ->with([
                'department',
                'createdBy',
                'verifiedBy',
                'msDecidedBy',
                'assignedTo',
            ])
            ->latest();

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where('request_no', 'ilike', "%{$search}%")
                    ->orWhere('title', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('request_type')) {
            $query->where(
                'request_type',
                $request->request_type
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        if ($request->filled('priority')) {
            $query->where(
                'priority',
                $request->priority
            );
        }

        switch ($request->input('queue')) {
            case 'verification': $query->whereIn('status', ['submitted', 'verified']); break;
            case 'ms': $query->where('status', 'pending_ms_approval'); break;
            case 'higher': $query->where('status', 'higher_approval_required'); break;
            case 'execution': $query->whereIn('status', AdministrativeRequest::EXECUTION_STATUSES); break;
            case 'closure': $query->whereIn('status', ['executed', 'execution_verified']); break;
            case 'closed': $query->where('status', 'closed'); break;
            case 'overdue': $query->overdue(); break;
            case 'mine': $query->where('assigned_to', Auth::id())->where('status', 'execution_in_progress'); break;
        }

        $requests = $query
            ->paginate(20)
            ->withQueryString();

        $counts = AdministrativeRequest::query()->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')->pluck('aggregate', 'status');
        $summary = [
            'total' => $counts->sum(),
            'verification' => ($counts['submitted'] ?? 0) + ($counts['verified'] ?? 0),
            'pending_ms' => $counts['pending_ms_approval'] ?? 0,
            'higher_approval' => $counts['higher_approval_required'] ?? 0,
            'pending_execution' => collect(AdministrativeRequest::EXECUTION_STATUSES)->sum(fn ($status) => $counts[$status] ?? 0),
            'closure' => ($counts['executed'] ?? 0) + ($counts['execution_verified'] ?? 0),
            'closed' => $counts['closed'] ?? 0,
            'overdue' => AdministrativeRequest::query()->overdue()->count(),
            'mine' => AdministrativeRequest::query()->where('assigned_to', Auth::id())
                ->where('status', 'execution_in_progress')->count(),
        ];

        return view(
            'administration.requests.index',
            compact('requests', 'summary')
        );
    }


    public function create(): View
    {
        $this->requireAccess('create');

        $departments = Department::query()
            ->orderBy('name')
            ->get();

        return view(
            'administration.requests.create',
            compact('departments')
        );
    }


    public function store(Request $request)
    {
        $this->requireAccess('create');

        $validated = $request->validate([
            'required_by' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'request_type' => [
                'required',
                'in:purchase,recruitment,finance,contract,project,maintenance,hr,other',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'department_id' => [
                'nullable',
                'exists:departments,id',
            ],

            'estimated_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'priority' => [
                'required',
                'in:low,normal,high,urgent',
            ],

            'remarks' => [
                'nullable',
                'string',
            ],
        ]);

        $administrativeRequest = DB::transaction(
            function () use ($validated) {

                return AdministrativeRequest::create([
                    ...$validated,

                    'request_no' =>
                        AdministrativeRequest::generateRequestNumber(),

                    'status' => 'draft',

                    'created_by' => Auth::id(),
                ]);
            }
        );

        return redirect()
            ->route(
                'administration.requests.show',
                $administrativeRequest
            )
            ->with(
                'success',
                'Administrative request created successfully.'
            );
    }


    public function show(
        AdministrativeRequest $administrativeRequest
    ): View {
        $this->requireAccess('view', $administrativeRequest);


        $administrativeRequest->load([
            'department',
            'createdBy',
            'verifiedBy',
            'msDecidedBy',
            'higherApprovalRecordedBy',
            'assignedTo',
            'executedBy',
            'completionVerifiedBy',
            'closedBy',
            'documents.uploadedBy',
        ]);

        $executionCategories = AdministrativeWorkAssignment::CATEGORIES;
        $eligibleAssignees = collect();
        if (AdministrativeRequestAccess::allows(Auth::user(), 'assign', $administrativeRequest)
            && in_array($administrativeRequest->status, ['ms_approved', 'higher_approved', 'execution_in_progress'], true)) {
            $eligibleAssignees = app(AdministrativeWorkAssignment::class)->eligibleUsers()->get(['id', 'name', 'role', 'designation']);
        }
        $assignmentHistory = DB::table('administrative_request_assignments')
            ->where('administrative_request_id', $administrativeRequest->id)->orderByDesc('id')->limit(20)->get();
        $latestAssignmentId = (int) ($assignmentHistory->first()?->id ?? 0);

        return view(
            'administration.requests.show',
            [
                'administrativeRequest' => $administrativeRequest,
                'executionCategories' => $executionCategories,
                'eligibleAssignees' => $eligibleAssignees,
                'assignmentHistory' => $assignmentHistory,
                'latestAssignmentId' => $latestAssignmentId,
            ]
        );
    }


    public function submit(
        AdministrativeRequest $administrativeRequest
    ) {
        $this->requireAccess('submit', $administrativeRequest);

        if (
            $administrativeRequest->status !== 'draft'
        ) {
            return back()->with(
                'error',
                'Only draft requests can be submitted.'
            );
        }

        $administrativeRequest->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        return redirect()
            ->route(
                'administration.requests.show',
                $administrativeRequest
            )
            ->with(
                'success',
                'Request submitted for Administrator verification.'
            );
    }


    public function verify(
    Request $request,
    AdministrativeRequest $administrativeRequest
) {
        $this->requireAccess('verify', $administrativeRequest);

    if ($administrativeRequest->status !== 'submitted') {
        return back()->with(
            'error',
            'Only submitted requests can be verified.'
        );
    }

    $validated = $request->validate([
        'verification_remarks' => [
            'nullable',
            'string',
            'max:2000',
        ],
    ]);

    $administrativeRequest->update([
        'status' => 'pending_ms_approval',
        'verified_by' => Auth::id(),
        'verified_at' => now(),
        'verification_remarks' =>
            $validated['verification_remarks'] ?? null,
    ]);

    return redirect()
        ->route(
            'administration.requests.show',
            $administrativeRequest
        )
        ->with(
            'success',
            'Request verified and forwarded to the Medical Superintendent.'
        );
}


public function msApprove(
    Request $request,
    AdministrativeRequest $administrativeRequest
) {
        $this->requireAccess('approve', $administrativeRequest);

    if ($administrativeRequest->status !== 'pending_ms_approval') {
        return back()->with(
            'error',
            'Only requests pending MS approval can be approved.'
        );
    }

    $validated = $request->validate([
        'approved_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999999.99'],
        'ms_remarks' => [
            'nullable',
            'string',
            'max:2000',
        ],
        'requires_higher_approval' => [
            'nullable',
            'boolean',
        ],
        'higher_authority' => [
            'required_if:requires_higher_approval,1',
            'nullable',
            'string',
            'max:100',
        ],
    ]);

    $requiresHigherApproval =
        (bool) ($validated['requires_higher_approval'] ?? false);

    $administrativeRequest->update([
        'status' => $requiresHigherApproval
            ? 'higher_approval_required'
            : 'ms_approved',

        'approved_amount' => $validated['approved_amount'] ?? null,
        'ms_decided_by' => Auth::id(),
        'ms_decided_at' => now(),
        'ms_decision' => 'approved',
        'ms_remarks' => $validated['ms_remarks'] ?? null,

        'requires_higher_approval' => $requiresHigherApproval,

        'higher_authority' => $requiresHigherApproval
            ? ($validated['higher_authority'] ?? null)
            : null,
    ]);

    return redirect()
        ->route(
            'administration.requests.show',
            $administrativeRequest
        )
        ->with(
            'success',
            $requiresHigherApproval
                ? 'Request approved by the Medical Superintendent and marked for higher approval.'
                : 'Request approved by the Medical Superintendent.'
        );
}


public function msReject(
    Request $request,
    AdministrativeRequest $administrativeRequest
) {
        $this->requireAccess('approve', $administrativeRequest);

    if ($administrativeRequest->status !== 'pending_ms_approval') {
        return back()->with(
            'error',
            'Only requests pending MS approval can be rejected.'
        );
    }

    $validated = $request->validate([
        'ms_remarks' => [
            'required',
            'string',
            'max:2000',
        ],
    ]);

    $administrativeRequest->update([
        'status' => 'ms_rejected',
        'ms_decided_by' => Auth::id(),
        'ms_decided_at' => now(),
        'ms_decision' => 'rejected',
        'ms_remarks' => $validated['ms_remarks'],
    ]);

    return redirect()
        ->route(
            'administration.requests.show',
            $administrativeRequest
        )
        ->with(
            'success',
            'Request rejected by the Medical Superintendent.'
        );
}


public function msReturn(
    Request $request,
    AdministrativeRequest $administrativeRequest
) {
        $this->requireAccess('approve', $administrativeRequest);

    if ($administrativeRequest->status !== 'pending_ms_approval') {
        return back()->with(
            'error',
            'Only requests pending MS approval can be returned.'
        );
    }

    $validated = $request->validate([
        'ms_remarks' => [
            'required',
            'string',
            'max:2000',
        ],
    ]);

    $administrativeRequest->update([
        'status' => 'submitted',
        'ms_decided_by' => Auth::id(),
        'ms_decided_at' => now(),
        'ms_decision' => 'returned',
        'ms_remarks' => $validated['ms_remarks'],

        'verified_by' => null,
        'verified_at' => null,
        'verification_remarks' => null,
    ]);

    return redirect()
        ->route(
            'administration.requests.show',
            $administrativeRequest
        )
        ->with(
            'success',
            'Request returned for correction and re-verification.'
        );
}


private function assignmentData(Request $request, bool $reassign = false): array
{
    return $request->validate([
        'assigned_to' => ['required', 'integer'],
        'execution_category' => ['required', \Illuminate\Validation\Rule::in(array_keys(AdministrativeWorkAssignment::CATEGORIES))],
        'required_by' => ['nullable', 'date_format:Y-m-d'],
        'execution_remarks' => ['nullable', 'string', 'max:2000'],
        'reassignment_reason' => $reassign ? ['required', 'string', 'min:5', 'max:2000'] : ['nullable'],
        'expected_assignment_id' => $reassign ? ['required', 'integer', 'min:0'] : ['nullable'],
        'expected_assignee' => $reassign ? ['nullable', 'integer'] : ['nullable'],
    ]);
}

public function startExecution(Request $request, AdministrativeRequest $administrativeRequest)
{
    $this->requireAccess('assign', $administrativeRequest);
    app(AdministrativeWorkAssignment::class)->assign(Auth::user(), $administrativeRequest->id, $this->assignmentData($request));
    return redirect()->route('administration.requests.show', $administrativeRequest)
        ->with('success', 'Execution started and assigned to the selected responsible person.');
}

public function reassignExecution(Request $request, AdministrativeRequest $administrativeRequest)
{
    $this->requireAccess('assign', $administrativeRequest);
    app(AdministrativeWorkAssignment::class)->assign(Auth::user(), $administrativeRequest->id, $this->assignmentData($request, true), true);
    return redirect()->route('administration.requests.show', $administrativeRequest)
        ->with('success', 'Work reassigned. The previous assignee and reason have been recorded.');
}

public function markExecuted(
    Request $request,
    AdministrativeRequest $administrativeRequest
) {
        $this->requireAccess('complete', $administrativeRequest);

    if (
        $administrativeRequest->status !==
        'execution_in_progress'
    ) {
        return back()->with(
            'error',
            'Only requests under execution can be marked executed.'
        );
    }

    $validated = $request->validate([
        'execution_remarks' => [
            'nullable',
            'string',
            'max:2000',
        ],
    ]);

    DB::transaction(function () use ($administrativeRequest, $validated) {
        $item = AdministrativeRequest::query()->lockForUpdate()->findOrFail($administrativeRequest->id);
        $this->requireAccess('complete', $item);
        if ($item->status !== 'execution_in_progress') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => 'Only requests under execution can be marked executed.',
            ]);
        }
        $item->update([
            'status' => 'executed', 'executed_at' => now(), 'executed_by' => Auth::id(),
            'execution_remarks' => $validated['execution_remarks'] ?? $item->execution_remarks,
        ]);
    });

    return redirect()
        ->route(
            'administration.requests.show',
            $administrativeRequest
        )
        ->with(
            'success',
            'Request marked as executed.'
        );
}


public function verifyCompletion(Request $request, AdministrativeRequest $administrativeRequest)
{
    $this->requireAccess('verify-completion', $administrativeRequest);
    $validated = $request->validate([
        'completion_verification_remarks' => ['required', 'string', 'max:2000'],
    ]);
    DB::transaction(function () use ($administrativeRequest, $validated) {
        $item = AdministrativeRequest::query()->lockForUpdate()->findOrFail($administrativeRequest->id);
        $this->requireAccess('verify-completion', $item);
        if ($item->status !== 'executed') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => 'Only executed requests can have their completion verified.',
            ]);
        }
        $item->update([
            'status' => 'execution_verified',
            'completion_verified_by' => Auth::id(),
            'completion_verified_at' => now(),
            'completion_verification_remarks' => $validated['completion_verification_remarks'],
        ]);
    });
    return redirect()->route('administration.requests.show', $administrativeRequest)
        ->with('success', 'Completion verified. The request is ready for closure.');
}

public function closeRequest(Request $request, AdministrativeRequest $administrativeRequest)
{
    $this->requireAccess('close', $administrativeRequest);
    $validated = $request->validate(['closure_remarks' => ['nullable', 'string', 'max:2000']]);
    DB::transaction(function () use ($administrativeRequest, $validated) {
        $item = AdministrativeRequest::query()->lockForUpdate()->findOrFail($administrativeRequest->id);
        $this->requireAccess('close', $item);
        if ($item->status !== 'execution_verified' || ! $item->completion_verified_at || ! $item->completion_verified_by) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'status' => 'Completion must be verified before this request can be closed.',
            ]);
        }
        $item->update([
            'status' => 'closed', 'closed_at' => now(), 'closed_by' => Auth::id(),
            'closure_remarks' => $validated['closure_remarks'] ?? null,
        ]);
    });
    return redirect()->route('administration.requests.show', $administrativeRequest)
        ->with('success', 'Administrative request closed successfully.');
}

public function uploadDocument(
    Request $request,
    AdministrativeRequest $administrativeRequest
) {
        $this->requireAccess('upload', $administrativeRequest);

    $validated = $request->validate([
        'document_type' => [
            'nullable',
            'string',
            'max:100',
        ],

        'title' => [
            'required',
            'string',
            'max:255',
        ],

        'document' => [
            'required',
            'file',
            'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png',
            'max:10240',
        ],

        'remarks' => [
            'nullable',
            'string',
            'max:2000',
        ],
    ]);

    $file = $request->file('document');

    $path = $file->store(
        'administration/requests/' . $administrativeRequest->id,
        'local'
    );

    AdministrativeRequestDocument::create([
        'administrative_request_id' =>
            $administrativeRequest->id,

        'document_type' =>
            $validated['document_type'] ?? null,

        'title' =>
            $validated['title'],

        'file_name' =>
            $file->getClientOriginalName(),

        'file_path' =>
            $path,

        'storage_disk' =>
            'local',

        'mime_type' =>
            $file->getMimeType(),

        'file_size' =>
            $file->getSize(),

        'uploaded_by' =>
            Auth::id(),

        'uploaded_at' =>
            now(),

        'remarks' =>
            $validated['remarks'] ?? null,
    ]);

    return redirect()
        ->route(
            'administration.requests.show',
            $administrativeRequest
        )
        ->with(
            'success',
            'Document uploaded successfully.'
        );
}

public function viewDocument(
    AdministrativeRequest $administrativeRequest,
    AdministrativeRequestDocument $document
) {
        $this->requireAccess('view', $administrativeRequest);

    if (
        $document->administrative_request_id !==
        $administrativeRequest->id
    ) {
        abort(404);
    }

    $disk = $document->storage_disk ?: 'public';

    if (
        ! Storage::disk($disk)->exists(
            $document->file_path
        )
    ) {
        abort(404, 'Document file not found.');
    }

    $fullPath = Storage::disk($disk)->path(
        $document->file_path
    );

    return response()->file(
        $fullPath,
        [
            'Content-Type' =>
                $document->mime_type
                ?: 'application/octet-stream',

            'Content-Disposition' =>
                'inline; filename="' .
                addslashes($document->file_name) .
                '"',
        ]
    );
}

public function recordHigherApproval(
    Request $request,
    AdministrativeRequest $administrativeRequest
) {
        $this->requireAccess('higher', $administrativeRequest);

    if (
        $administrativeRequest->status !==
        'higher_approval_required'
    ) {
        return back()->with(
            'error',
            'Only requests awaiting higher approval can be approved.'
        );
    }

    $validated = $request->validate([
        'higher_authority' => [
            'required',
            'string',
            'max:100',
        ],

        'higher_approval_reference' => [
            'nullable',
            'string',
            'max:255',
        ],

        'higher_approved_at' => [
            'required',
            'date',
            'before_or_equal:today',
        ],

        'higher_approval_remarks' => [
            'nullable',
            'string',
            'max:2000',
        ],
    ]);

    $administrativeRequest->update([
        'status' => 'higher_approved',

        'higher_authority' =>
            $validated['higher_authority'],

        'higher_approval_reference' =>
            $validated['higher_approval_reference'] ?? null,

        'higher_approved_at' =>
            $validated['higher_approved_at'],

        'higher_approval_recorded_by' =>
            Auth::id(),

        'higher_approval_remarks' =>
            $validated['higher_approval_remarks'] ?? null,
    ]);

    return redirect()
        ->route(
            'administration.requests.show',
            $administrativeRequest
        )
        ->with(
            'success',
            'Higher approval recorded successfully. The request is now ready for execution.'
        );
}
public function myWork(Request $request): View
{
        $this->requireAccess('view');

    $query = AdministrativeRequest::query()
        ->where('assigned_to', Auth::id())
        ->with([
            'department',
            'createdBy',
            'msDecidedBy',
            'higherApprovalRecordedBy',
        ])
        ->latest('execution_started_at');

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('search')) {
        $search = trim($request->search);

        $query->where(function ($q) use ($search) {
            $q->where('request_no', 'ilike', "%{$search}%")
                ->orWhere('title', 'ilike', "%{$search}%")
                ->orWhere('description', 'ilike', "%{$search}%");
        });
    }

    $requests = $query
        ->paginate(20)
        ->withQueryString();

    $summary = [
        'pending' => AdministrativeRequest::query()
            ->where('assigned_to', Auth::id())
            ->where('status', 'execution_in_progress')
            ->count(),

        'executed' => AdministrativeRequest::query()
            ->where('assigned_to', Auth::id())
            ->whereIn('status', ['executed', 'execution_verified'])
            ->count(),

        'closed' => AdministrativeRequest::query()
            ->where('assigned_to', Auth::id())
            ->where('status', 'closed')
            ->count(),
    ];

    return view(
        'administration.my-work.index',
        compact('requests', 'summary')
    );
}
}