<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientController extends Controller
{
    /**
     * Display patient list with search.
     */
    public function index(Request $request)
    {
        $query = Patient::query();

        if ($request->filled('search')) {

            $search =
                trim(
                    $request->search
                );

            $normalizedPhone =
                $this->normalizePhone(
                    $search
                );


            $query->where(
                function ($q) use (
                    $search,
                    $normalizedPhone
                ) {

                    $q->where(
                        'uhid',
                        'ilike',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'mrd_number',
                        'ilike',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'first_name',
                        'ilike',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'middle_name',
                        'ilike',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'last_name',
                        'ilike',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'mhis_number',
                        'ilike',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'abha_number',
                        'ilike',
                        "%{$search}%"
                    );


                    if ($normalizedPhone) {

                        $q->orWhere(
                            'phone',
                            'ilike',
                            "%{$normalizedPhone}%"
                        );
                    }
                }
            );
        }


        $patients =
            $query
                ->latest()
                ->paginate(20)
                ->withQueryString();


        return view(
            'patients.index',
            compact('patients')
        );
    }


    /**
     * Patient card.
     */
    public function card(Patient $patient)
    {
        return view(
            'patients.card',
            compact('patient')
        );
    }


    /**
     * Show patient registration form
     * and check for possible duplicates.
     */
    public function create(Request $request)
    {
        $duplicates =
            collect();


        /*
        |--------------------------------------------------------------------------
        | Registration return destination
        |--------------------------------------------------------------------------
        |
        | Used when Patient Registration was opened from another module,
        | currently Emergency.
        |
        */

        $returnTo =
            $this->sanitizeReturnTo(
                $request->input(
                    'return_to'
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Duplicate search input
        |--------------------------------------------------------------------------
        */

        $firstName =
            trim(
                (string) $request->input(
                    'first_name'
                )
            );


        $lastName =
            trim(
                (string) $request->input(
                    'last_name'
                )
            );


        $dob =
            $request->input(
                'date_of_birth'
            );


        $phone =
            $this->normalizePhone(
                $request->input(
                    'phone'
                )
            );


        if (
            $firstName
            ||
            $lastName
            ||
            $dob
            ||
            $phone
        ) {

            $duplicates =
                Patient::query()
                    ->where(
                        function ($query) use (
                            $firstName,
                            $lastName,
                            $dob,
                            $phone
                        ) {

                            /*
                             * Same phone.
                             */

                            if ($phone) {

                                $query->orWhere(
                                    'phone',
                                    $phone
                                );
                            }


                            /*
                             * Same first + last name.
                             */

                            if (
                                $firstName
                                &&
                                $lastName
                            ) {

                                $query->orWhere(
                                    function ($q) use (
                                        $firstName,
                                        $lastName
                                    ) {

                                        $q->where(
                                            'first_name',
                                            'ilike',
                                            $firstName
                                        )
                                        ->where(
                                            'last_name',
                                            'ilike',
                                            $lastName
                                        );
                                    }
                                );
                            }


                            /*
                             * Same first name + DOB.
                             */

                            if (
                                $firstName
                                &&
                                $dob
                            ) {

                                $query->orWhere(
                                    function ($q) use (
                                        $firstName,
                                        $dob
                                    ) {

                                        $q->where(
                                            'first_name',
                                            'ilike',
                                            $firstName
                                        )
                                        ->where(
                                            'date_of_birth',
                                            $dob
                                        );
                                    }
                                );
                            }
                        }
                    )
                    ->latest()
                    ->limit(10)
                    ->get();
        }


        return view(
            'patients.create',
            compact(
                'duplicates',
                'returnTo'
            )
        );
    }


    /**
     * Store a newly registered patient.
     */
    public function store(Request $request)
    {
        $validated =
            $request->validate([

                'title' => [
                    'nullable',
                    'string',
                    'max:20',
                ],

                'first_name' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'middle_name' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'last_name' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'date_of_birth' => [
                    'nullable',
                    'date',
                    'before_or_equal:today',
                ],

                'age' => [
                    'nullable',
                    'integer',
                    'min:0',
                    'max:130',
                ],

                'sex' => [
                    'required',
                    'string',
                    'max:20',
                ],

                'phone' => [
                    'nullable',
                    'string',
                    'max:20',
                ],

                'alternate_phone' => [
                    'nullable',
                    'string',
                    'max:20',
                ],

                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                ],

                'address' => [
                    'nullable',
                    'string',
                ],

                'locality' => [
                    'nullable',
                    'string',
                    'max:150',
                ],

                'district' => [
                    'nullable',
                    'string',
                    'max:150',
                ],

                'state' => [
                    'nullable',
                    'string',
                    'max:150',
                ],

                'pin_code' => [
                    'nullable',
                    'string',
                    'max:10',
                ],

                'blood_group' => [
                    'nullable',
                    'string',
                    'max:10',
                ],

                'emergency_contact_name' => [
                    'nullable',
                    'string',
                    'max:150',
                ],

                'emergency_contact_phone' => [
                    'nullable',
                    'string',
                    'max:20',
                ],

                'emergency_contact_relation' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'abha_number' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'mhis_number' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'known_allergies' => [
                    'nullable',
                    'string',
                ],

                /*
                 * Workflow return target.
                 */
                'return_to' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
            ]);


        /*
        |--------------------------------------------------------------------------
        | Extract workflow return target
        |--------------------------------------------------------------------------
        |
        | Do not send this field to Patient::create().
        |
        */

        $returnTo =
            $this->sanitizeReturnTo(
                $validated['return_to']
                ?? null
            );


        unset(
            $validated['return_to']
        );


        /*
        |--------------------------------------------------------------------------
        | Clean name fields
        |--------------------------------------------------------------------------
        */

        $validated['first_name'] =
            trim(
                $validated['first_name']
            );


        $validated['middle_name'] =
            ! empty(
                $validated['middle_name']
            )
                ? trim(
                    $validated['middle_name']
                )
                : null;


        $validated['last_name'] =
            ! empty(
                $validated['last_name']
            )
                ? trim(
                    $validated['last_name']
                )
                : null;


        /*
        |--------------------------------------------------------------------------
        | Normalize phone numbers
        |--------------------------------------------------------------------------
        */

        $validated['phone'] =
            $this->normalizePhone(
                $validated['phone']
                ?? null
            );


        $validated['alternate_phone'] =
            $this->normalizePhone(
                $validated['alternate_phone']
                ?? null
            );


        $validated['emergency_contact_phone'] =
            $this->normalizePhone(
                $validated['emergency_contact_phone']
                ?? null
            );


        /*
        |--------------------------------------------------------------------------
        | HARD DUPLICATE CHECK
        |--------------------------------------------------------------------------
        */

        $duplicate =
            $this->findStrongDuplicate(
                $validated
            );


        if ($duplicate) {

            return back()
                ->withInput()
                ->withErrors([
                    'duplicate' =>
                        'Registration stopped. A matching patient already exists. '
                        . 'UHID: '
                        . $duplicate->uhid
                        . ' | MRD: '
                        . (
                            $duplicate->mrd_number
                            ?: 'Not assigned'
                        )
                        . ' | Patient: '
                        . $duplicate->full_name
                        . '. Please open the existing patient record instead of creating another UHID.',
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Create Patient
        |--------------------------------------------------------------------------
        */

        $patient =
            DB::transaction(
                function () use (
                    $validated
                ) {

                    $validated['uhid'] =
                        $this->generateUhid();


                    $validated['mrd_number'] =
                        $this->generateMrdNumber();


                    return Patient::create(
                        $validated
                    );
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Emergency Registration Return
        |--------------------------------------------------------------------------
        |
        | When the patient-registration form was opened from Emergency,
        | return directly to Emergency Registration and pass the newly
        | created patient ID so that patient can be selected automatically.
        |
        */

        if (
            $returnTo
            === route(
                'emergency.create'
            )
        ) {

            return redirect()
                ->route(
                    'emergency.create',
                    [
                        'patient_id' =>
                            $patient->id,
                    ]
                )
                ->with(
                    'success',
                    'Patient registered successfully. '
                    . 'UHID: '
                    . $patient->uhid
                    . ' | MRD: '
                    . $patient->mrd_number
                    . '. Complete the Emergency registration below.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Normal Patient Registration Return
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'patients.show',
                $patient
            )
            ->with(
                'success',
                'Patient registered successfully. '
                . 'UHID: '
                . $patient->uhid
                . ' | MRD: '
                . $patient->mrd_number
            );
    }


    /**
     * Display individual patient.
     */
    public function show(Patient $patient)
    {
        return view(
            'patients.show',
            compact('patient')
        );
    }


    /**
     * Find a strong duplicate.
     */
    private function findStrongDuplicate(
        array $data
    ): ?Patient {

        $firstName =
            trim(
                $data['first_name']
            );


        $lastName =
            ! empty(
                $data['last_name']
            )
                ? trim(
                    $data['last_name']
                )
                : null;


        $phone =
            $data['phone']
            ?? null;


        $dob =
            $data['date_of_birth']
            ?? null;


        /*
         * Rule 1:
         * Same phone + first name.
         */

        if (
            $phone
            &&
            $firstName
        ) {

            $patient =
                Patient::query()
                    ->where(
                        'phone',
                        $phone
                    )
                    ->where(
                        'first_name',
                        'ilike',
                        $firstName
                    )
                    ->first();


            if ($patient) {

                return $patient;
            }
        }


        /*
         * Rule 2:
         * Same full name + DOB.
         */

        if (
            $firstName
            &&
            $lastName
            &&
            $dob
        ) {

            $patient =
                Patient::query()
                    ->where(
                        'first_name',
                        'ilike',
                        $firstName
                    )
                    ->where(
                        'last_name',
                        'ilike',
                        $lastName
                    )
                    ->where(
                        'date_of_birth',
                        $dob
                    )
                    ->first();


            if ($patient) {

                return $patient;
            }
        }


        return null;
    }


    /**
     * Normalize an allowed return destination.
     *
     * At present only Emergency Registration is allowed.
     *
     * This prevents arbitrary external redirects.
     */
    private function sanitizeReturnTo(
        ?string $returnTo
    ): ?string {

        if (! $returnTo) {

            return null;
        }


        $emergencyRegistrationUrl =
            route(
                'emergency.create'
            );


        /*
         * Exact generated Laravel route URL.
         */

        if (
            $returnTo
            === $emergencyRegistrationUrl
        ) {

            return $emergencyRegistrationUrl;
        }


        /*
         * Also allow the local path form.
         */

        $path =
            parse_url(
                $returnTo,
                PHP_URL_PATH
            );


        $expectedPath =
            parse_url(
                $emergencyRegistrationUrl,
                PHP_URL_PATH
            );


        if (
            $path
            &&
            $path === $expectedPath
        ) {

            return $emergencyRegistrationUrl;
        }


        return null;
    }


    /**
     * Normalize Indian phone numbers.
     *
     * Examples:
     *
     * +91 7005636161
     * 917005636161
     * 07005636161
     * 7005636161
     *
     * all become:
     *
     * 7005636161
     */
    private function normalizePhone(
        ?string $phone
    ): ?string {

        if (! $phone) {

            return null;
        }


        /*
         * Remove everything except digits.
         */

        $digits =
            preg_replace(
                '/\D+/',
                '',
                $phone
            );


        if (! $digits) {

            return null;
        }


        /*
         * Remove Indian country code 91.
         */

        if (
            strlen(
                $digits
            ) === 12
            &&
            str_starts_with(
                $digits,
                '91'
            )
        ) {

            $digits =
                substr(
                    $digits,
                    2
                );
        }


        /*
         * Remove leading zero.
         */

        if (
            strlen(
                $digits
            ) === 11
            &&
            str_starts_with(
                $digits,
                '0'
            )
        ) {

            $digits =
                substr(
                    $digits,
                    1
                );
        }


        /*
         * If still more than 10 digits,
         * keep the last 10 digits.
         */

        if (
            strlen(
                $digits
            ) > 10
        ) {

            $digits =
                substr(
                    $digits,
                    -10
                );
        }


        return $digits
            ?: null;
    }


    /**
     * Generate UHID.
     *
     * Example:
     * TCH-2026-000001
     */
    private function generateUhid(): string
    {
        $year =
            now()->format(
                'Y'
            );


        $lastPatient =
            Patient::withTrashed()
                ->where(
                    'uhid',
                    'like',
                    "TCH-{$year}-%"
                )
                ->orderByDesc(
                    'id'
                )
                ->first();


        $nextNumber =
            1;


        if ($lastPatient) {

            $parts =
                explode(
                    '-',
                    $lastPatient->uhid
                );


            $lastNumber =
                (int) end(
                    $parts
                );


            $nextNumber =
                $lastNumber + 1;
        }


        return sprintf(
            'TCH-%s-%06d',
            $year,
            $nextNumber
        );
    }


    /**
     * Generate MRD number.
     *
     * Example:
     * MRD-2026-000001
     */
    private function generateMrdNumber(): string
    {
        $year =
            now()->format(
                'Y'
            );


        $lastPatient =
            Patient::withTrashed()
                ->whereNotNull(
                    'mrd_number'
                )
                ->where(
                    'mrd_number',
                    'like',
                    "MRD-{$year}-%"
                )
                ->orderByDesc(
                    'id'
                )
                ->first();


        $nextNumber =
            1;


        if ($lastPatient) {

            $parts =
                explode(
                    '-',
                    $lastPatient->mrd_number
                );


            $lastNumber =
                (int) end(
                    $parts
                );


            $nextNumber =
                $lastNumber + 1;
        }


        return sprintf(
            'MRD-%s-%06d',
            $year,
            $nextNumber
        );
    }
}