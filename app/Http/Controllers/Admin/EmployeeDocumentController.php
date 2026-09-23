<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployeeDocumentController extends Controller
{
    /**
     * Supported HR document categories.
     */
    private const DOCUMENT_TYPES = [
        'appointment_letter',
        'contract',
        'qualification',
        'professional_registration',
        'identity_document',
        'experience_certificate',
        'training_cme',
        'leave_document',
        'appraisal_acr',
        'other',
    ];


    /**
     * Display the employee document register.
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'employee_id' => [
                'nullable',
                'integer',
                'exists:employees,id',
            ],

            'document_type' => [
                'nullable',
                Rule::in(self::DOCUMENT_TYPES),
            ],

            'verification_status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'verified',
                    'rejected',
                ]),
            ],

            'expiry' => [
                'nullable',
                Rule::in([
                    '30',
                    'expired',
                    'none',
                ]),
            ],
        ]);


        $query = EmployeeDocument::query()
            ->with([
                'employee.department',
                'verifiedBy',
                'createdBy',
            ]);


        if (! empty($validated['employee_id'])) {
            $query->where(
                'employee_id',
                $validated['employee_id']
            );
        }


        if (! empty($validated['document_type'])) {
            $query->where(
                'document_type',
                $validated['document_type']
            );
        }


        if (! empty($validated['verification_status'])) {
            $query->where(
                'verification_status',
                $validated['verification_status']
            );
        }


        if (($validated['expiry'] ?? null) === '30') {
            $query
                ->whereNotNull('expiry_date')
                ->whereDate(
                    'expiry_date',
                    '>=',
                    today()
                )
                ->whereDate(
                    'expiry_date',
                    '<=',
                    today()->copy()->addDays(30)
                );
        }


        if (($validated['expiry'] ?? null) === 'expired') {
            $query
                ->whereNotNull('expiry_date')
                ->whereDate(
                    'expiry_date',
                    '<',
                    today()
                );
        }


        if (($validated['expiry'] ?? null) === 'none') {
            $query->whereNull('expiry_date');
        }


        $documents = $query
            ->orderByRaw(
                'CASE
                    WHEN expiry_date IS NULL THEN 1
                    ELSE 0
                END'
            )
            ->orderBy('expiry_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();


        $employees = Employee::query()
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('last_name')
            ->get();


        $documentTypes =
            self::DOCUMENT_TYPES;


        $pendingVerificationCount =
            EmployeeDocument::query()
                ->where(
                    'verification_status',
                    'pending'
                )
                ->count();


        $verifiedCount =
            EmployeeDocument::query()
                ->where(
                    'verification_status',
                    'verified'
                )
                ->count();


        $expiringSoonCount =
            EmployeeDocument::query()
                ->whereNotNull('expiry_date')
                ->whereDate(
                    'expiry_date',
                    '>=',
                    today()
                )
                ->whereDate(
                    'expiry_date',
                    '<=',
                    today()->copy()->addDays(30)
                )
                ->count();


        $expiredCount =
            EmployeeDocument::query()
                ->whereNotNull('expiry_date')
                ->whereDate(
                    'expiry_date',
                    '<',
                    today()
                )
                ->count();


        return view(
            'admin.hr.documents.index',
            compact(
                'documents',
                'employees',
                'documentTypes',
                'pendingVerificationCount',
                'verifiedCount',
                'expiringSoonCount',
                'expiredCount'
            )
        );
    }


    /**
     * Show the upload form.
     */
    public function create(
        Request $request
    ): View {

        $employees = Employee::query()
            ->with('department')
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('last_name')
            ->get();


        $documentTypes =
            self::DOCUMENT_TYPES;


        $selectedEmployeeId =
            $request->integer('employee_id')
                ?: null;


        return view(
            'admin.hr.documents.create',
            compact(
                'employees',
                'documentTypes',
                'selectedEmployeeId'
            )
        );
    }


    /**
     * Store a new employee document securely.
     */
    public function store(
        Request $request
    ): RedirectResponse {

        $validated = $request->validate([
            'employee_id' => [
                'required',
                'integer',
                'exists:employees,id',
            ],

            'document_type' => [
                'required',
                Rule::in(self::DOCUMENT_TYPES),
            ],

            'title' => [
                'required',
                'string',
                'max:200',
            ],

            'reference_no' => [
                'nullable',
                'string',
                'max:100',
            ],

            'issue_date' => [
                'nullable',
                'date',
            ],

            'expiry_date' => [
                'nullable',
                'date',
                'after_or_equal:issue_date',
            ],

            'document_file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:10240',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);


        $file =
            $request->file('document_file');


        $extension =
            strtolower(
                $file->getClientOriginalExtension()
            );


        $storedFilename =
            (string) Str::uuid()
            . '.'
            . $extension;


        $directory =
            'employee-documents/'
            . now()->format('Y')
            . '/employee-'
            . $validated['employee_id'];


        $disk =
            'local';


        $filePath =
            $directory
            . '/'
            . $storedFilename;


        Storage::disk($disk)->putFileAs(
            $directory,
            $file,
            $storedFilename
        );


        try {

            DB::transaction(
                function () use (
                    $validated,
                    $file,
                    $disk,
                    $filePath
                ) {

                    EmployeeDocument::create([
                        'employee_id' =>
                            $validated[
                                'employee_id'
                            ],

                        'document_type' =>
                            $validated[
                                'document_type'
                            ],

                        'title' =>
                            trim(
                                $validated[
                                    'title'
                                ]
                            ),

                        'reference_no' =>
                            filled(
                                $validated[
                                    'reference_no'
                                ]
                                ?? null
                            )
                                ? trim(
                                    $validated[
                                        'reference_no'
                                    ]
                                )
                                : null,

                        'issue_date' =>
                            $validated[
                                'issue_date'
                            ]
                            ?? null,

                        'expiry_date' =>
                            $validated[
                                'expiry_date'
                            ]
                            ?? null,

                        'disk' =>
                            $disk,

                        'file_path' =>
                            $filePath,

                        'original_filename' =>
                            $file
                                ->getClientOriginalName(),

                        'mime_type' =>
                            $file->getMimeType(),

                        'file_size' =>
                            $file->getSize(),

                        'verification_status' =>
                            'pending',

                        'remarks' =>
                            filled(
                                $validated[
                                    'remarks'
                                ]
                                ?? null
                            )
                                ? trim(
                                    $validated[
                                        'remarks'
                                    ]
                                )
                                : null,

                        'created_by' =>
                            auth()->id(),

                        'updated_by' =>
                            auth()->id(),
                    ]);
                }
            );

        } catch (\Throwable $exception) {

            Storage::disk($disk)
                ->delete($filePath);

            throw $exception;
        }


        return redirect()
            ->route(
                'admin.hr.documents.index'
            )
            ->with(
                'success',
                'Employee document uploaded successfully.'
            );
    }


    /**
     * Display a stored PDF or image inline.
     */
    public function viewFile(
        EmployeeDocument $document
    ) {
        abort_unless(
            Storage::disk(
                $document->disk
            )->exists(
                $document->file_path
            ),
            404,
            'Document file not found.'
        );


        return Storage::disk(
            $document->disk
        )->response(
            $document->file_path,
            $document->original_filename,
            [
                'Content-Type' =>
                    $document->mime_type
                    ?: 'application/octet-stream',

                'Content-Disposition' =>
                    'inline; filename="'
                    . str_replace(
                        '"',
                        '',
                        $document->original_filename
                    )
                    . '"',
            ]
        );
    }


    /**
     * Download a stored employee document.
     */
    public function download(
        EmployeeDocument $document
    ) {
        abort_unless(
            Storage::disk(
                $document->disk
            )->exists(
                $document->file_path
            ),
            404,
            'Document file not found.'
        );


        return Storage::disk(
            $document->disk
        )->download(
            $document->file_path,
            $document->original_filename
        );
    }


    /**
     * Verify an employee document.
     */
    public function verify(
        Request $request,
        EmployeeDocument $document
    ): RedirectResponse {

        $validated = $request->validate([
            'verification_remarks' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);


        $document->update([
            'verification_status' =>
                'verified',

            'verified_by' =>
                auth()->id(),

            'verified_at' =>
                now(),

            'verification_remarks' =>
                filled(
                    $validated[
                        'verification_remarks'
                    ]
                    ?? null
                )
                    ? trim(
                        $validated[
                            'verification_remarks'
                        ]
                    )
                    : null,

            'updated_by' =>
                auth()->id(),
        ]);


        return back()->with(
            'success',
            'Document verified successfully.'
        );
    }


    /**
     * Reject verification of an employee document.
     */
    public function reject(
        Request $request,
        EmployeeDocument $document
    ): RedirectResponse {

        $validated = $request->validate([
            'verification_remarks' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);


        $document->update([
            'verification_status' =>
                'rejected',

            'verified_by' =>
                auth()->id(),

            'verified_at' =>
                now(),

            'verification_remarks' =>
                trim(
                    $validated[
                        'verification_remarks'
                    ]
                ),

            'updated_by' =>
                auth()->id(),
        ]);


        return back()->with(
            'success',
            'Document verification rejected.'
        );
    }


    /**
     * Return document type labels for UI use.
     */
    public static function documentTypeLabels(): array
    {
        return [
            'appointment_letter' =>
                'Appointment Letter',

            'contract' =>
                'Contract',

            'qualification' =>
                'Qualification',

            'professional_registration' =>
                'Professional Registration',

            'identity_document' =>
                'Identity Document',

            'experience_certificate' =>
                'Experience Certificate',

            'training_cme' =>
                'Training / CME',

            'leave_document' =>
                'Leave Document',

            'appraisal_acr' =>
                'Appraisal / ACR',

            'other' =>
                'Other',
        ];
    }
}
