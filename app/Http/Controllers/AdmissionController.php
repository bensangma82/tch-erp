<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdmissionController extends Controller
{
    /**
     * IPD census / admitted patient list.
     */
    public function index(Request $request): View
    {
        $query = Admission::query()
            ->with([
                'patient',
                'department',
                'consultant',
                'bed.ward',
                'currentBedAllocation.bed.ward',
            ]);


        /*
        |--------------------------------------------------------------------------
        | Status filter
        |--------------------------------------------------------------------------
        |
        | By default show only currently admitted patients.
        |
        */

        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->string('status')->toString()
            );

        } else {

            $query->where(
                'status',
                'admitted'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Department filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('department_id')) {

            $query->where(
                'department_id',
                $request->integer('department_id')
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Consultant filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('consultant_id')) {

            $query->where(
                'consultant_id',
                $request->integer('consultant_id')
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Ward filter
        |--------------------------------------------------------------------------
        */

        if ($request->filled('ward_id')) {

            $wardId =
                $request->integer('ward_id');


            $query->where(
                function ($wardQuery) use ($wardId) {

                    /*
                     * Current active bed allocation.
                     */

                    $wardQuery->whereHas(
                        'currentBedAllocation.bed',
                        function ($bedQuery) use ($wardId) {

                            $bedQuery->where(
                                'ward_id',
                                $wardId
                            );

                        }
                    )
                    ->orWhere(
                        function ($fallbackQuery) use ($wardId) {

                            /*
                             * Fallback to admissions.bed_id.
                             *
                             * Useful for older admissions where current
                             * allocation may not yet exist.
                             */

                            $fallbackQuery
                                ->whereDoesntHave(
                                    'currentBedAllocation'
                                )
                                ->whereHas(
                                    'bed',
                                    function ($bedQuery) use ($wardId) {

                                        $bedQuery->where(
                                            'ward_id',
                                            $wardId
                                        );

                                    }
                                );

                        }
                    );

                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        |
        | Search using the real Patient schema:
        |
        | admission_no
        | UHID
        | MRD
        | first / middle / last name
        | phone
        |
        */

        if ($request->filled('search')) {

            $search =
                trim(
                    $request->string('search')->toString()
                );


            $query->where(
                function ($subQuery) use ($search) {

                    $subQuery
                        ->where(
                            'admission_no',
                            'ilike',
                            '%' . $search . '%'
                        )
                        ->orWhereHas(
                            'patient',
                            function ($patientQuery) use ($search) {

                                $patientQuery
                                    ->where(
                                        'uhid',
                                        'ilike',
                                        '%' . $search . '%'
                                    )
                                    ->orWhere(
                                        'mrd_number',
                                        'ilike',
                                        '%' . $search . '%'
                                    )
                                    ->orWhere(
                                        'first_name',
                                        'ilike',
                                        '%' . $search . '%'
                                    )
                                    ->orWhere(
                                        'middle_name',
                                        'ilike',
                                        '%' . $search . '%'
                                    )
                                    ->orWhere(
                                        'last_name',
                                        'ilike',
                                        '%' . $search . '%'
                                    )
                                    ->orWhere(
                                        'phone',
                                        'ilike',
                                        '%' . $search . '%'
                                    );

                            }
                        );

                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | IPD census
        |--------------------------------------------------------------------------
        */

        $admissions = $query
            ->orderByDesc('admitted_at')
            ->paginate(25)
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Filter masters
        |--------------------------------------------------------------------------
        */

        $wards = Ward::query()
            ->where(
                'is_active',
                true
            )
            ->orderBy('name')
            ->get();


        $departments = Department::query()
            ->orderBy('name')
            ->get();


        $consultants = Employee::query()
            ->where(
                'is_doctor',
                true
            )
            ->where(
                'is_active',
                true
            )
            ->orderBy('first_name')
            ->orderBy('middle_name')
            ->orderBy('last_name')
            ->get();


        return view(
            'ipd.index',
            compact(
                'admissions',
                'wards',
                'departments',
                'consultants'
            )
        );
    }


    /**
     * Show one IPD admission.
     */
    public function show(
        Admission $admission
    ): View {

        $admission->load([
            'patient',
            'department',
            'consultant',
            'bed.ward',
            'createdBy',
            'bedAllocations.bed.ward',
            'bedAllocations.allocatedBy',
            'bedAllocations.releasedBy',
            'currentBedAllocation.bed.ward',
            'emergencyVisit.latestTriage',
            'dischargeSummary',
        ]);


        return view(
            'ipd.show',
            compact(
                'admission'
            )
        );
    }
}