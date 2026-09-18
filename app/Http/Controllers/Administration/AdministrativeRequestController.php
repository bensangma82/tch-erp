<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\AdministrativeRequest;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Models\AdministrativeRequestDocument;
use Illuminate\Support\Facades\Storage;

class AdministrativeRequestController extends Controller
{
    public function index(Request $request): View
    {
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

        $requests = $query
            ->paginate(20)
            ->withQueryString();

        $summary = [
            'total' =>
                AdministrativeRequest::count(),

            'pending_ms' =>
                AdministrativeRequest::where(
                    'status',
                    'pending_ms_approval'
                )->count(),

            'pending_execution' =>
                AdministrativeRequest::whereIn(
                    'status',
                    [
                        'ms_approved',
                        'higher_approved',
                        'execution_in_progress',
                    ]
                )->count(),

            'higher_approval' =>
                AdministrativeRequest::where(
                    'status',
                    'higher_approval_required'
                )->count(),

            'closed' =>
                AdministrativeRequest::where(
                    'status',
                    'closed'
                )->count(),
        ];

        return view(
            'administration.requests.index',
            compact('requests', 'summary')
        );
    }


    public function create(): View
    {
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
        $validated = $request->validate([
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

        $administrativeRequest->load([
            'department',
            'createdBy',
            'verifiedBy',
            'msDecidedBy',
            'higherApprovalRecordedBy',
            'assignedTo',
            'executedBy',
            'closedBy',
            'documents.uploadedBy',
        ]);

        return view(
            'administration.requests.show',
            [
                'administrativeRequest' =>
                    $administrativeRequest,
            ]
        );
    }


    public function submit(
        AdministrativeRequest $administrativeRequest
    ) {
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
    if ($administrativeRequest->status !== 'pending_ms_approval') {
        return back()->with(
            'error',
            'Only requests pending MS approval can be approved.'
        );
    }

    $validated = $request->validate([
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
            'nullable',
            'string',
            'max:255',
        ],
    ]);

    $requiresHigherApproval =
        (bool) ($validated['requires_higher_approval'] ?? false);

    $administrativeRequest->update([
        'status' => $requiresHigherApproval
            ? 'higher_approval_required'
            : 'ms_approved',

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


public function startExecution(
    Request $request,
    AdministrativeRequest $administrativeRequest
) {
    if (
        ! in_array(
            $administrativeRequest->status,
            ['ms_approved', 'higher_approved'],
            true
        )
    ) {
        return back()->with(
            'error',
            'Only approved requests can enter execution.'
        );
    }

    $validated = $request->validate([
        'execution_category' => [
            'required',
            'in:clinical,nursing,non_clinical',
        ],

        'execution_remarks' => [
            'nullable',
            'string',
            'max:2000',
        ],
    ]);

    $assignedRole = match ($validated['execution_category']) {
        'clinical' => 'Deputy Medical Superintendent',
        'nursing' => 'Nursing Superintendent',
        'non_clinical' => 'Manager',
    };

    $assignedUser = User::query()
        ->where('designation', $assignedRole)
        ->where('is_active', true)
        ->first();

    if (! $assignedUser) {
        return back()->with(
            'error',
            "No active user is assigned the designation: {$assignedRole}."
        );
    }

    $administrativeRequest->update([
        'status' => 'execution_in_progress',

        'execution_category' =>
            $validated['execution_category'],

        'assigned_role' => $assignedRole,

        'assigned_to' => $assignedUser->id,

        'execution_started_at' => now(),

        'execution_remarks' =>
            $validated['execution_remarks'] ?? null,
    ]);

    return redirect()
        ->route(
            'administration.requests.show',
            $administrativeRequest
        )
        ->with(
            'success',
            "Execution started and assigned to {$assignedUser->name} ({$assignedRole})."
        );
}


public function markExecuted(
    Request $request,
    AdministrativeRequest $administrativeRequest
) {
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

    $administrativeRequest->update([
        'status' => 'executed',
        'executed_at' => now(),
        'executed_by' => Auth::id(),
        'execution_remarks' =>
            $validated['execution_remarks']
            ?? $administrativeRequest->execution_remarks,
    ]);

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


public function closeRequest(
    Request $request,
    AdministrativeRequest $administrativeRequest
) {
    if ($administrativeRequest->status !== 'executed') {
        return back()->with(
            'error',
            'Only executed requests can be closed.'
        );
    }

    $validated = $request->validate([
        'closure_remarks' => [
            'nullable',
            'string',
            'max:2000',
        ],
    ]);

    $administrativeRequest->update([
        'status' => 'closed',
        'closed_at' => now(),
        'closed_by' => Auth::id(),
        'closure_remarks' =>
            $validated['closure_remarks'] ?? null,
    ]);

    return redirect()
        ->route(
            'administration.requests.show',
            $administrativeRequest
        )
        ->with(
            'success',
            'Administrative request closed successfully.'
        );
}

public function uploadDocument(
    Request $request,
    AdministrativeRequest $administrativeRequest
) {
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
            'max:255',
        ],

        'higher_approval_reference' => [
            'nullable',
            'string',
            'max:255',
        ],

        'higher_approved_at' => [
            'required',
            'date',
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
            ->where('status', 'executed')
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