<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DischargeHistoryController extends Controller
{
    /**
     * Search patients with admission history.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $patients = Patient::query()
            ->whereHas('admissions')
            ->when(
                mb_strlen($search) >= 2,
                function ($query) use ($search) {
                    $term = '%' . addcslashes($search, '%_\\') . '%';

                    $query->where(function ($q) use ($term) {
                        $q->where('uhid', 'ILIKE', $term)
                            ->orWhere('mrd_number', 'ILIKE', $term)
                            ->orWhere('first_name', 'ILIKE', $term)
                            ->orWhere('middle_name', 'ILIKE', $term)
                            ->orWhere('last_name', 'ILIKE', $term)
                            ->orWhere('phone', 'ILIKE', $term);
                    });
                }
            )
            ->when(
                mb_strlen($search) < 2,
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        return view(
            'medical-records.discharge-history.index',
            compact('patients', 'search')
        );
    }

    /**
     * Show previous admissions and discharge summaries.
     */
    public function patient(Patient $patient): View
    {
        $admissions = Admission::query()
            ->where('patient_id', $patient->id)
            ->with([
                'department',
                'consultant',
                'dischargeSummary' => fn ($query) =>
                    $query->where('status', 'finalised'),
            ])
            ->orderByDesc('admitted_at')
            ->get();

        return view(
            'medical-records.discharge-history.patient',
            compact('patient', 'admissions')
        );
    }

    /**
     * Read-only admission clinical record.
     */
    public function clinicalRecord(Admission $admission): View
    {
        $admission->load([
            'patient',
            'department',
            'consultant',
            'dischargeSummary' => fn ($query) =>
                $query->where('status', 'finalised'),
            'serviceOrders.items' => function ($query) {
                $query->whereHas(
                    'diagnosticResult',
                    fn ($result) => $result->where('status', 'final')
                )->with([
                    'diagnosticResult' => fn ($result) =>
                        $result->where('status', 'final')
                            ->with('items'),
                ]);
            },
        ]);

        $investigations = $admission->serviceOrders
            ->flatMap(fn ($order) => $order->items)
            ->filter(fn ($item) => $item->diagnosticResult !== null)
            ->values();

        return view(
            'medical-records.discharge-history.clinical-record',
            compact('admission', 'investigations')
        );
    }


}
