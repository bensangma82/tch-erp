<?php

namespace App\Http\Controllers\Mis;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Bed;
use App\Models\BedAllocation;
use App\Models\Department;
use App\Models\EmergencyVisit;
use App\Models\Encounter;
use App\Models\HospitalExpense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MisDashboardController extends Controller
{
    public function index(Request $request): View
    {
        /*
        |--------------------------------------------------------------------------
        | Validate reporting period
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $today = now()->toDateString();

        $from = $validated['from']
            ?? now()->startOfMonth()->toDateString();

        $to = $validated['to']
            ?? $today;

        abort_if(
            Carbon::parse($from)->gt(Carbon::parse($to)),
            422,
            'The report start date cannot be after the end date.'
        );

        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | OPD attendance
        |--------------------------------------------------------------------------
        | Count registered OPD encounters in the selected period.
        */

        $opdQuery = Encounter::query()
            ->where('encounter_type', 'opd')
            ->whereBetween('encounter_date', [$from, $to]);

        $opdVisits = (clone $opdQuery)->count();

        $opdPatients = (clone $opdQuery)
            ->distinct()
            ->count('patient_id');

        /*
        |--------------------------------------------------------------------------
        | IPD admissions and discharges
        |--------------------------------------------------------------------------
        */

        $ipdAdmissions = Admission::query()
            ->whereBetween('admitted_at', [$start, $end])
            ->count();

        $ipdDischarges = Admission::query()
            ->whereNotNull('discharged_at')
            ->whereBetween('discharged_at', [$start, $end])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Emergency visits
        |--------------------------------------------------------------------------
        */

        $emergencyVisits = EmergencyVisit::query()
            ->whereBetween('arrival_at', [$start, $end])
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Average length of stay
        |--------------------------------------------------------------------------
        | Completed stays discharged in the selected period.
        | PostgreSQL calculates the duration in days.
        */

        $averageLengthOfStay = Admission::query()
            ->whereNotNull('admitted_at')
            ->whereNotNull('discharged_at')
            ->whereBetween('discharged_at', [$start, $end])
            ->whereColumn('discharged_at', '>=', 'admitted_at')
            ->selectRaw(
                'AVG(EXTRACT(EPOCH FROM (discharged_at - admitted_at)) / 86400.0) AS average_days'
            )
            ->value('average_days');

        /*
        |--------------------------------------------------------------------------
        | Department-wise OPD activity
        |--------------------------------------------------------------------------
        */

        $departmentOpd = Department::query()
            ->leftJoin(
                'encounters',
                function ($join) use ($from, $to) {
                    $join->on(
                        'encounters.department_id',
                        '=',
                        'departments.id'
                    )
                        ->where('encounters.encounter_type', 'opd')
                        ->whereNull('encounters.deleted_at')
                        ->whereBetween(
                            'encounters.encounter_date',
                            [$from, $to]
                        );
                }
            )
            ->select(
                'departments.id',
                'departments.name'
            )
            ->selectRaw(
                'COUNT(encounters.id) AS opd_visits'
            )
            ->groupBy(
                'departments.id',
                'departments.name'
            )
            ->orderByDesc('opd_visits')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Department-wise IPD admissions
        |--------------------------------------------------------------------------
        */

        $ipdDepartmentCodes = [
            'MED',
            'NEPH',
            'SURG',
            'OBG',
            'PAED',
        ];

        $departmentIpd = Department::query()
            ->where('is_active', true)
            ->whereIn('code', $ipdDepartmentCodes)
            ->leftJoin('admissions', function ($join) use ($start, $end) {
                $join->on(
                    'admissions.department_id',
                    '=',
                    'departments.id'
                )
                    ->whereBetween(
                        'admissions.admitted_at',
                        [$start, $end]
                    );
            })
            ->select(
                'departments.id',
                'departments.name'
            )
            ->selectRaw('COUNT(admissions.id) AS admissions')
            ->groupBy(
                'departments.id',
                'departments.name'
            )
            ->orderBy('departments.name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Current Bed Occupancy
        |--------------------------------------------------------------------------
        |
        | Snapshot of active beds at the time the report is generated.
        | This is independent of the selected historical reporting period.
        |
        */

        $bedStatusCounts = Bed::query()
            ->where('is_active', true)
            ->selectRaw('LOWER(status) AS bed_status, COUNT(*) AS total')
            ->groupByRaw('LOWER(status)')
            ->pluck('total', 'bed_status');

        $totalActiveBeds = (int) $bedStatusCounts->sum();

        $occupiedBeds = (int) ($bedStatusCounts['occupied'] ?? 0);
        $availableBeds = (int) ($bedStatusCounts['available'] ?? 0);
        $cleaningBeds = (int) ($bedStatusCounts['cleaning'] ?? 0);
        $reservedBeds = (int) ($bedStatusCounts['reserved'] ?? 0);
        $maintenanceBeds = (int) ($bedStatusCounts['maintenance'] ?? 0);

        /*
        |--------------------------------------------------------------------------
        | Snapshot occupancy denominator
        |--------------------------------------------------------------------------
        |
        | Exclude maintenance beds from the currently operational bed pool.
        | Cleaning and reserved beds remain included but are displayed
        | separately. This is an operational snapshot, not a formal
        | historical bed occupancy calculation.
        |
        */

        $operationalBeds = max(
            0,
            $totalActiveBeds - $maintenanceBeds
        );

        $currentOccupancyPercentage = $operationalBeds > 0
            ? round(($occupiedBeds / $operationalBeds) * 100, 2)
            : null;

        /*
        |--------------------------------------------------------------------------
        | Ward-wise bed occupancy
        |--------------------------------------------------------------------------
        */

        $wardOccupancy = Bed::query()
            ->join('wards', 'wards.id', '=', 'beds.ward_id')
            ->where('beds.is_active', true)
            ->select(
                'wards.id',
                'wards.name'
            )
            ->selectRaw('COUNT(beds.id) AS total_beds')
            ->selectRaw(
                "SUM(CASE WHEN LOWER(beds.status) = 'occupied' THEN 1 ELSE 0 END) AS occupied_beds"
            )
            ->selectRaw(
                "SUM(CASE WHEN LOWER(beds.status) = 'available' THEN 1 ELSE 0 END) AS available_beds"
            )
            ->selectRaw(
                "SUM(CASE WHEN LOWER(beds.status) = 'cleaning' THEN 1 ELSE 0 END) AS cleaning_beds"
            )
            ->selectRaw(
                "SUM(CASE WHEN LOWER(beds.status) = 'reserved' THEN 1 ELSE 0 END) AS reserved_beds"
            )
            ->selectRaw(
                "SUM(CASE WHEN LOWER(beds.status) = 'maintenance' THEN 1 ELSE 0 END) AS maintenance_beds"
            )
            ->groupBy('wards.id', 'wards.name')
            ->orderBy('wards.name')
            ->get()
            ->map(function ($ward) {
                $ward->operational_beds = max(
                    0,
                    (int) $ward->total_beds
                        - (int) $ward->maintenance_beds
                );

                $ward->occupancy_percentage = $ward->operational_beds > 0
                    ? round(
                        ((int) $ward->occupied_beds / $ward->operational_beds) * 100,
                        2
                    )
                    : null;

                return $ward;
            });

        /*
        |--------------------------------------------------------------------------
        | Historical Bed Utilization — Provisional
        |--------------------------------------------------------------------------
        |
        | Uses bed-allocation intervals overlapping the selected period.
        | Open allocations are calculated up to the current time.
        |
        | Results represent 24-hour bed-day equivalents, not midnight
        | census bed-days.
        |
        | Historical operational-bed capacity is not available, so no
        | historical occupancy percentage is calculated here.
        |
        */

        $periodStart = $start->copy();

        $periodEndExclusive = $end->copy()->addSecond();

        $calculationEnd = now()->lt($periodEndExclusive)
            ? now()
            : $periodEndExclusive;

        $elapsedSeconds = $calculationEnd->greaterThan($periodStart)
            ? $periodStart->diffInSeconds($calculationEnd)
            : 0;

        $elapsedDays = $elapsedSeconds / 86400;

        $occupiedSeconds = 0;

        if ($elapsedSeconds > 0) {

            $historicalAllocations = BedAllocation::query()
                ->where('allocated_at', '<', $calculationEnd)
                ->where(function ($query) use ($periodStart) {
                    $query->whereNull('released_at')
                        ->orWhere('released_at', '>', $periodStart);
                })
                ->orderBy('bed_id')
                ->orderBy('allocated_at')
                ->get();

            /*
            |----------------------------------------------------------
            | Calculate occupied time by bed.
            |----------------------------------------------------------
            |
            | Merge overlapping allocation intervals for the same bed
            | so duplicated allocations cannot inflate occupied time.
            |
            */

            $intervalsByBed = [];

            foreach ($historicalAllocations as $allocation) {

                $allocationStart = $allocation->allocated_at;

                $allocationEnd = $allocation->released_at
                    ?? $calculationEnd;

                $overlapStart = $allocationStart->greaterThan($periodStart)
                    ? $allocationStart
                    : $periodStart;

                $overlapEnd = $allocationEnd->lessThan($calculationEnd)
                    ? $allocationEnd
                    : $calculationEnd;

                if ($overlapEnd->greaterThan($overlapStart)) {
                    $intervalsByBed[$allocation->bed_id][] = [
                        $overlapStart->timestamp,
                        $overlapEnd->timestamp,
                    ];
                }
            }

            foreach ($intervalsByBed as $intervals) {

                usort($intervals, fn ($a, $b) => $a[0] <=> $b[0]);

                $mergedStart = null;
                $mergedEnd = null;

                foreach ($intervals as [$intervalStart, $intervalEnd]) {

                    if ($mergedStart === null) {
                        $mergedStart = $intervalStart;
                        $mergedEnd = $intervalEnd;

                        continue;
                    }

                    if ($intervalStart <= $mergedEnd) {
                        $mergedEnd = max($mergedEnd, $intervalEnd);
                    } else {
                        $occupiedSeconds += $mergedEnd - $mergedStart;

                        $mergedStart = $intervalStart;
                        $mergedEnd = $intervalEnd;
                    }
                }

                if ($mergedStart !== null) {
                    $occupiedSeconds += $mergedEnd - $mergedStart;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Provisional historical utilization
        |--------------------------------------------------------------------------
        */

        $occupiedBedDays = round($occupiedSeconds / 86400, 2);

        $averageDailyCensus = $elapsedDays > 0
            ? round(($occupiedSeconds / 86400) / $elapsedDays, 2)
            : null;

        $historicalPeriodComplete = $periodEndExclusive->lessThanOrEqualTo(now());

        /*
        |--------------------------------------------------------------------------
        | Diagnostics MIS — Read-only Reporting
        |--------------------------------------------------------------------------
        |
        | Reporting period is based on service_orders.ordered_at.
        | Quantities are counted from service_order_items.
        | This represents the current workflow status of items
        | ordered within the selected period.
        |
        */

        $diagnosticItems = DB::table('service_order_items as items')
            ->join(
                'service_orders as orders',
                'orders.id',
                '=',
                'items.service_order_id'
            )
            ->whereBetween('orders.ordered_at', [$start, $end])
            ->whereNotNull('items.category')
            ->whereRaw(
                'LOWER(TRIM(items.category)) NOT IN (?, ?)',
                ['consultation', 'other']
            )
            ->select([
                'items.service_id',
                'items.service_name',
                'items.category',
                'items.status',
                'items.quantity',
                'orders.encounter_id',
                'orders.admission_id',
            ])
            ->get();

        $diagnosticTotal = (float) $diagnosticItems->sum('quantity');

        $diagnosticCompleted = (float) $diagnosticItems
            ->filter(fn ($item) => $item->status === 'completed')
            ->sum('quantity');

        $diagnosticInProcess = (float) $diagnosticItems
            ->filter(fn ($item) => $item->status === 'in_process')
            ->sum('quantity');

        $diagnosticOrdered = (float) $diagnosticItems
            ->filter(fn ($item) => $item->status === 'ordered')
            ->sum('quantity');

        $diagnosticOtherStatuses = max(
            0,
            $diagnosticTotal
                - $diagnosticCompleted
                - $diagnosticInProcess
                - $diagnosticOrdered
        );

        $diagnosticCompletionRate = $diagnosticTotal > 0
            ? round(($diagnosticCompleted / $diagnosticTotal) * 100, 2)
            : null;

        /*
        |--------------------------------------------------------------------------
        | Investigation Categories
        |--------------------------------------------------------------------------
        */

        $diagnosticCategories = $diagnosticItems
            ->groupBy(
                fn ($item) => strtolower(trim((string) $item->category))
            )
            ->map(function ($items, $category) {
                return [
                    'category' => ucfirst($category),
                    'total' => (float) $items->sum('quantity'),
                    'completed' => (float) $items
                        ->where('status', 'completed')
                        ->sum('quantity'),
                    'in_process' => (float) $items
                        ->where('status', 'in_process')
                        ->sum('quantity'),
                    'ordered' => (float) $items
                        ->where('status', 'ordered')
                        ->sum('quantity'),
                ];
            })
            ->sortByDesc('total')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | OPD / IPD Investigation Sources
        |--------------------------------------------------------------------------
        */

        $diagnosticOpd = (float) $diagnosticItems
            ->filter(fn ($item) => $item->encounter_id !== null)
            ->sum('quantity');

        $diagnosticIpd = (float) $diagnosticItems
            ->filter(fn ($item) => $item->admission_id !== null
                && $item->encounter_id === null
            )
            ->sum('quantity');

        $diagnosticUnlinked = (float) $diagnosticItems
            ->filter(fn ($item) => $item->encounter_id === null
                && $item->admission_id === null
            )
            ->sum('quantity');

        /*
        |--------------------------------------------------------------------------
        | Service-wise Investigation Workload
        |--------------------------------------------------------------------------
        */

        $diagnosticServices = $diagnosticItems
            ->groupBy(
                fn ($item) => $item->service_id
                    ?? $item->service_name
                    ?? 'Unknown'
            )
            ->map(function ($items) {
                return [
                    'name' => $items->first()->service_name
                        ?? 'Unnamed Investigation',
                    'category' => ucfirst(
                        strtolower((string) $items->first()->category)
                    ),
                    'total' => (float) $items->sum('quantity'),
                    'completed' => (float) $items
                        ->where('status', 'completed')
                        ->sum('quantity'),
                ];
            })
            ->sortByDesc('total')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Advanced Diagnostics MIS — Sample Collection
        |--------------------------------------------------------------------------
        |
        | Reporting cohort: laboratory investigation items ordered
        | during the selected MIS reporting period.
        |
        | Count sample records separately from investigation quantities.
        | Absence of a sample record means collection is undocumented,
        | not necessarily that the specimen was never collected.
        |
        */

        $sampleItems = DB::table('service_order_items as items')
            ->join(
                'service_orders as orders',
                'orders.id',
                '=',
                'items.service_order_id'
            )
            ->leftJoin(
                'diagnostic_samples as samples',
                'samples.service_order_item_id',
                '=',
                'items.id'
            )
            ->whereBetween('orders.ordered_at', [$start, $end])
            ->whereRaw(
                'LOWER(TRIM(items.category)) = ?',
                ['laboratory']
            )
            ->where('items.requires_sample', true)
            ->select([
                'items.id',
                'items.service_name',
                'items.specimen_type',
                'orders.ordered_at',
                'samples.id as sample_id',
                'samples.specimen_type as collected_specimen_type',
                'samples.status as sample_status',
                'samples.collected_at',
                'samples.rejected_at',
            ])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Sample Collection Summary
        |--------------------------------------------------------------------------
        */

        $sampleRequired = $sampleItems->count();

        $sampleDocumented = $sampleItems
            ->whereNotNull('sample_id')
            ->count();

        $sampleUndocumented = $sampleItems
            ->whereNull('sample_id')
            ->count();

        $sampleCollected = $sampleItems
            ->filter(fn ($item) => $item->sample_id !== null
                && $item->sample_status === 'collected'
                && $item->collected_at !== null
            )
            ->count();

        $sampleRejected = $sampleItems
            ->filter(fn ($item) => $item->sample_id !== null
                && (
                    $item->sample_status === 'rejected'
                    || $item->rejected_at !== null
                )
            )
            ->count();

        $sampleCoverageRate = $sampleRequired > 0
            ? round(
                ($sampleDocumented / $sampleRequired) * 100,
                2
            )
            : null;

        /*
        |--------------------------------------------------------------------------
        | Order-to-Collection Turnaround Time
        |--------------------------------------------------------------------------
        |
        | Exclude records with missing timestamps or negative intervals.
        | TAT is calculated in minutes.
        |
        */

        $collectionTimes = $sampleItems
            ->filter(fn ($item) => $item->ordered_at !== null
                && $item->collected_at !== null
            )
            ->map(function ($item) {
                $ordered = Carbon::parse($item->ordered_at);
                $collected = Carbon::parse($item->collected_at);

                return $ordered->diffInMinutes($collected, false);
            })
            ->filter(fn ($minutes) => $minutes >= 0)
            ->values();

        $sampleAverageCollectionTat = $collectionTimes->isNotEmpty()
            ? round($collectionTimes->avg(), 1)
            : null;

        $sampleTatRecords = $collectionTimes->count();

        /*
        |--------------------------------------------------------------------------
        | Specimen-wise Sample Performance
        |--------------------------------------------------------------------------
        */

        $specimenPerformance = $sampleItems
            ->groupBy(function ($item) {
                return $item->collected_specimen_type
                    ?: $item->specimen_type
                    ?: 'Not Specified';
            })
            ->map(function ($items, $specimen) {
                return [
                    'specimen' => $specimen,
                    'required' => $items->count(),
                    'documented' => $items
                        ->whereNotNull('sample_id')
                        ->count(),
                    'collected' => $items
                        ->filter(fn ($item) => $item->sample_status === 'collected'
                            && $item->collected_at !== null
                        )
                        ->count(),
                    'rejected' => $items
                        ->filter(fn ($item) => $item->sample_status === 'rejected'
                            || $item->rejected_at !== null
                        )
                        ->count(),
                ];
            })
            ->sortByDesc('required')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | Advanced Diagnostics MIS — Laboratory Report TAT
        |--------------------------------------------------------------------------
        |
        | Reporting cohort: laboratory investigations ordered within
        | the selected MIS period.
        |
        | Result status and verification are reported separately.
        | Collection-to-entry TAT uses valid timestamps only.
        |
        */

        $laboratoryResults = DB::table('diagnostic_results as results')
            ->join(
                'service_order_items as items',
                'items.id',
                '=',
                'results.service_order_item_id'
            )
            ->join(
                'service_orders as orders',
                'orders.id',
                '=',
                'items.service_order_id'
            )
            ->leftJoin(
                'diagnostic_samples as samples',
                'samples.service_order_item_id',
                '=',
                'items.id'
            )
            ->whereBetween('orders.ordered_at', [$start, $end])
            ->whereRaw(
                'LOWER(TRIM(items.category)) = ?',
                ['laboratory']
            )
            ->select([
                'results.id',
                'results.status',
                'results.entered_at',
                'results.verified_at',
                'samples.collected_at',
            ])
            ->get();

        $labResultCount = $laboratoryResults->count();

        $labFinalizedCount = $laboratoryResults
            ->where('status', 'final')
            ->count();

        $labEnteredCount = $laboratoryResults
            ->whereNotNull('entered_at')
            ->count();

        $labVerifiedTimestampCount = $laboratoryResults
            ->whereNotNull('verified_at')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Collection-to-Result-Entry Turnaround Time
        |--------------------------------------------------------------------------
        */

        $labTatMinutes = $laboratoryResults
            ->filter(fn ($result) => $result->entered_at !== null
                && $result->collected_at !== null
            )
            ->map(function ($result) {
                $collected = Carbon::parse(
                    $result->collected_at
                );

                $entered = Carbon::parse(
                    $result->entered_at
                );

                return $collected->diffInSeconds($entered, false) / 60;
            })
            ->filter(fn ($minutes) => $minutes >= 0)
            ->values();

        $labTatMeasuredCount = $labTatMinutes->count();

        $labTatMissingCount = max(
            0,
            $labResultCount - $labTatMeasuredCount
        );

        $labAverageTatMinutes = $labTatMeasuredCount > 0
            ? round($labTatMinutes->avg(), 2)
            : null;

        $labMinimumTatMinutes = $labTatMeasuredCount > 0
            ? round($labTatMinutes->min(), 2)
            : null;

        $labMaximumTatMinutes = $labTatMeasuredCount > 0
            ? round($labTatMinutes->max(), 2)
            : null;

        $labTatCoverageRate = $labResultCount > 0
            ? round(
                ($labTatMeasuredCount / $labResultCount) * 100,
                2
            )
            : null;

        /*
        |--------------------------------------------------------------------------
        | Finance MIS — General Ledger Executive Overview
        |--------------------------------------------------------------------------
        |
        | Read-only accrual reporting.
        |
        | Revenue: income credits minus debits.
        | Expenditure: expense debits minus credits.
        | Reporting period: journal_date.
        |
        | Reversals remain included as posted accounting entries.
        | Figures represent ERP-posted journals, not necessarily
        | the hospital's complete financial statements.
        |
        */

        $financeJournalQuery = DB::table('journal_entry_lines as lines')
            ->join(
                'journal_entries as journals',
                'journals.id',
                '=',
                'lines.journal_entry_id'
            )
            ->join(
                'general_ledger_accounts as accounts',
                'accounts.id',
                '=',
                'lines.general_ledger_account_id'
            )
            ->where('journals.status', 'posted')
            ->whereBetween('journals.journal_date', [$from, $to]);

        /*
        |--------------------------------------------------------------------------
        | Recognized Revenue and Expenditure
        |--------------------------------------------------------------------------
        */

        $financeAccountTotals = (clone $financeJournalQuery)
            ->selectRaw('
        accounts.account_type,
        COALESCE(SUM(lines.debit), 0) AS total_debit,
        COALESCE(SUM(lines.credit), 0) AS total_credit
    ')
            ->groupBy('accounts.account_type')
            ->get()
            ->keyBy('account_type');

        $financeIncome = $financeAccountTotals->get('income');

        $financeExpense = $financeAccountTotals->get('expense');

        $financeRecognizedRevenue = round(
            (float) ($financeIncome->total_credit ?? 0)
            - (float) ($financeIncome->total_debit ?? 0),
            2
        );

        $financeRecordedExpenditure = round(
            (float) ($financeExpense->total_debit ?? 0)
            - (float) ($financeExpense->total_credit ?? 0),
            2
        );

        $financeProvisionalSurplus = round(
            $financeRecognizedRevenue - $financeRecordedExpenditure,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Department-wise Recognized Revenue
        |--------------------------------------------------------------------------
        |
        | Unassigned income must remain visible separately.
        | No automatic or estimated department allocation.
        |
        */

        $financeDepartmentRevenue = (clone $financeJournalQuery)
            ->leftJoin(
                'departments as departments',
                'departments.id',
                '=',
                'lines.department_id'
            )
            ->where('accounts.account_type', 'income')
            ->selectRaw("
        lines.department_id,
        COALESCE(departments.name, 'Unassigned') AS department_name,
        COUNT(*) AS journal_lines,
        COALESCE(
            SUM(lines.credit - lines.debit),
            0
        ) AS recognized_revenue
    ")
            ->groupBy(
                'lines.department_id',
                'departments.name'
            )
            ->orderByDesc('recognized_revenue')
            ->get();

        $financeAssignedRevenue = round(
            (float) $financeDepartmentRevenue
                ->filter(fn ($row) => $row->department_id !== null)
                ->sum('recognized_revenue'),
            2
        );

        $financeUnassignedRevenue = round(
            (float) $financeDepartmentRevenue
                ->filter(fn ($row) => $row->department_id === null)
                ->sum('recognized_revenue'),
            2
        );

        $financeAttributionRate = $financeRecognizedRevenue > 0
            ? round(
                ($financeAssignedRevenue / $financeRecognizedRevenue) * 100,
                2
            )
            : null;

        /*
        |--------------------------------------------------------------------------
        | Journal Posting and Balancing Indicators
        |--------------------------------------------------------------------------
        |
        | Verify total debits and credits for posted journals
        | within the selected reporting period.
        |
        */

        $financeJournalSummary = DB::table('journal_entries as journals')
            ->leftJoin(
                'journal_entry_lines as lines',
                'lines.journal_entry_id',
                '=',
                'journals.id'
            )
            ->where('journals.status', 'posted')
            ->whereBetween('journals.journal_date', [$from, $to])
            ->selectRaw('
        COUNT(DISTINCT journals.id) AS journal_count,
        COALESCE(SUM(lines.debit), 0) AS total_debit,
        COALESCE(SUM(lines.credit), 0) AS total_credit
    ')
            ->first();

        $financePostedJournals = (int) (
            $financeJournalSummary->journal_count ?? 0
        );

        $financeTotalDebits = round(
            (float) ($financeJournalSummary->total_debit ?? 0),
            2
        );

        $financeTotalCredits = round(
            (float) ($financeJournalSummary->total_credit ?? 0),
            2
        );

        $financeJournalDifference = round(
            $financeTotalDebits - $financeTotalCredits,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Financial Reporting Coverage
        |--------------------------------------------------------------------------
        |
        | These indicators describe the current GL setup.
        | They are not an assertion of complete accounting coverage.
        |
        */

        $financeIncomeAccountCount = DB::table('general_ledger_accounts')
            ->where('account_type', 'income')
            ->count();

        $financeExpenseAccountCount = DB::table('general_ledger_accounts')
            ->where('account_type', 'expense')
            ->count();

        $financeIncomeLines = (clone $financeJournalQuery)
            ->where('accounts.account_type', 'income')
            ->count();

        $financeAssignedIncomeLines = (clone $financeJournalQuery)
            ->where('accounts.account_type', 'income')
            ->whereNotNull('lines.department_id')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Finance MIS — Cash Collections & Reconciliation
        |--------------------------------------------------------------------------
        |
        | Posted Finance receipt vouchers only.
        | Reporting date: finance_vouchers.voucher_date.
        |
        | These are recorded receipt vouchers, NOT a complete
        | hospital collections total or additional accrual revenue.
        |
        */

        $receiptBaseQuery = DB::table('finance_vouchers as vouchers')
            ->where('vouchers.voucher_type', 'receipt')
            ->where('vouchers.status', 'posted')
            ->whereBetween('vouchers.voucher_date', [$from, $to]);

        /*
        |--------------------------------------------------------------------------
        | Posted Receipt Summary
        |--------------------------------------------------------------------------
        */

        $receiptSummary = (clone $receiptBaseQuery)
            ->selectRaw('
        COUNT(*) AS voucher_count,
        COALESCE(SUM(vouchers.amount), 0) AS total_amount
    ')
            ->first();

        $financeReceiptCount = (int) $receiptSummary->voucher_count;

        $financeReceiptTotal = round(
            (float) $receiptSummary->total_amount,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Account-wise Receipts
        |--------------------------------------------------------------------------
        */

        $financeReceiptAccounts = (clone $receiptBaseQuery)
            ->leftJoin(
                'finance_accounts as accounts',
                'accounts.id',
                '=',
                'vouchers.finance_account_id'
            )
            ->selectRaw("
        COALESCE(accounts.name, 'Unassigned Account') AS account_name,
        COUNT(*) AS voucher_count,
        COALESCE(SUM(vouchers.amount), 0) AS total_amount
    ")
            ->groupBy('accounts.id', 'accounts.name')
            ->orderByDesc('total_amount')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Payment-mode Distribution
        |--------------------------------------------------------------------------
        |
        | Normalize capitalization and whitespace.
        |
        */

        $financeReceiptModes = (clone $receiptBaseQuery)
            ->selectRaw("
        COALESCE(
            NULLIF(LOWER(TRIM(vouchers.payment_mode)), ''),
            'unspecified'
        ) AS payment_mode,
        COUNT(*) AS voucher_count,
        COALESCE(SUM(vouchers.amount), 0) AS total_amount
    ")
            ->groupByRaw("
        COALESCE(
            NULLIF(LOWER(TRIM(vouchers.payment_mode)), ''),
            'unspecified'
        )
    ")
            ->orderByDesc('total_amount')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Receipt Origin Analysis
        |--------------------------------------------------------------------------
        */

        $financeReceiptOrigins = (clone $receiptBaseQuery)
            ->selectRaw("
        COALESCE(
            NULLIF(TRIM(vouchers.origin), ''),
            'unspecified'
        ) AS receipt_origin,
        COUNT(*) AS voucher_count,
        COALESCE(SUM(vouchers.amount), 0) AS total_amount
    ")
            ->groupByRaw("
        COALESCE(
            NULLIF(TRIM(vouchers.origin), ''),
            'unspecified'
        )
    ")
            ->orderByDesc('total_amount')
            ->get();

        $financeUnspecifiedReceiptTotal = round(
            (float) $financeReceiptOrigins
                ->where('receipt_origin', 'unspecified')
                ->sum('total_amount'),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Direct General Ledger Linkage
        |--------------------------------------------------------------------------
        |
        | EXISTS prevents receipt duplication where multiple
        | journal entries reference the same Finance voucher.
        |
        | This checks direct finance_voucher_id linkage only.
        | It is NOT full bank/Tally reconciliation.
        |
        */

        $financeLinkedReceipts = (clone $receiptBaseQuery)
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('journal_entries as journals')
                    ->whereColumn(
                        'journals.finance_voucher_id',
                        'vouchers.id'
                    )
                    ->where('journals.status', 'posted');
            })
            ->selectRaw('
        COUNT(*) AS voucher_count,
        COALESCE(SUM(vouchers.amount), 0) AS total_amount
    ')
            ->first();

        $financeLinkedReceiptCount = (int) (
            $financeLinkedReceipts->voucher_count ?? 0
        );

        $financeLinkedReceiptAmount = round(
            (float) ($financeLinkedReceipts->total_amount ?? 0),
            2
        );

        $financeUnlinkedReceiptCount = max(
            0,
            $financeReceiptCount - $financeLinkedReceiptCount
        );

        $financeUnlinkedReceiptAmount = round(
            $financeReceiptTotal - $financeLinkedReceiptAmount,
            2
        );

        $financeReceiptLinkageRate = $financeReceiptCount > 0
            ? round(
                ($financeLinkedReceiptCount / $financeReceiptCount) * 100,
                2
            )
            : null;

        /*
        |--------------------------------------------------------------------------
        | Finance MIS — Patient Receivables & Advances
        |--------------------------------------------------------------------------
        |
        | Read-only reporting.
        |
        | GL balances are calculated as of the selected end date.
        | Billing account balances are CURRENT snapshots, because
        | the stored account balance does not provide a historical
        | balance at arbitrary dates.
        |
        | Do not combine patient balances with MHIS claims.
        |
        */

        $receivablesAsOfDate = $to;

        /*
        |--------------------------------------------------------------------------
        | Patient Billing Snapshot
        |--------------------------------------------------------------------------
        */

        $ipdReceivableAccounts = DB::table('ip_billing_accounts')
            ->where('status', 'finalized')
            ->select([
                'id',
                'account_no',
                'mhis_category',
                'net_amount',
                'advance_amount',
                'paid_amount',
                'balance_amount',
                'finalized_at',
            ])
            ->get();

        $ipdFinalizedAccountCount = $ipdReceivableAccounts->count();

        $ipdFinalizedBillTotal = round(
            (float) $ipdReceivableAccounts->sum('net_amount'),
            2
        );

        $ipdStoredOutstanding = round(
            (float) $ipdReceivableAccounts->sum('balance_amount'),
            2
        );

        $ipdSelfPayOutstanding = round(
            (float) $ipdReceivableAccounts
                ->filter(fn ($account) => $account->mhis_category === null
                    || trim((string) $account->mhis_category) === ''
                )
                ->sum('balance_amount'),
            2
        );

        $ipdMhisAccountOutstanding = round(
            (float) $ipdReceivableAccounts
                ->filter(fn ($account) => $account->mhis_category !== null
                    && trim((string) $account->mhis_category) !== ''
                )
                ->sum('balance_amount'),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | General Ledger Balances — As of Selected End Date
        |--------------------------------------------------------------------------
        */

        $receivableLedgerBalances = DB::table('journal_entry_lines as lines')
            ->join(
                'journal_entries as journals',
                'journals.id',
                '=',
                'lines.journal_entry_id'
            )
            ->join(
                'general_ledger_accounts as accounts',
                'accounts.id',
                '=',
                'lines.general_ledger_account_id'
            )
            ->where('journals.status', 'posted')
            ->whereDate('journals.journal_date', '<=', $receivablesAsOfDate)
            ->whereIn('accounts.account_code', ['1200', '2100'])
            ->selectRaw('
        accounts.account_code,
        COALESCE(SUM(lines.debit), 0) AS total_debit,
        COALESCE(SUM(lines.credit), 0) AS total_credit
    ')
            ->groupBy('accounts.account_code')
            ->get()
            ->keyBy('account_code');

        $patientReceivableLedger = $receivableLedgerBalances->get('1200');

        $patientAdvanceLedger = $receivableLedgerBalances->get('2100');

        $glPatientReceivables = round(
            (float) ($patientReceivableLedger->total_debit ?? 0)
            - (float) ($patientReceivableLedger->total_credit ?? 0),
            2
        );

        $glPatientAdvanceLiability = round(
            (float) ($patientAdvanceLedger->total_credit ?? 0)
            - (float) ($patientAdvanceLedger->total_debit ?? 0),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Billing-to-GL Reconciliation
        |--------------------------------------------------------------------------
        |
        | This compares a current billing snapshot with a GL balance
        | at the selected end date.
        |
        | Therefore it is provisional unless the end date is current
        | and the underlying accounting scope is comparable.
        |
        */

        $patientReceivableDifference = round(
            $glPatientReceivables - $ipdStoredOutstanding,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Recorded Patient Advances
        |--------------------------------------------------------------------------
        |
        | Advance receipts within the selected reporting period.
        | Do not treat applied advances as new collections.
        |
        */

        $patientAdvancePeriod = DB::table('ip_billing_advances')
            ->where('status', 'active')
            ->whereBetween('payment_date', [$start, $end])
            ->selectRaw('
        COUNT(*) AS advance_count,
        COALESCE(SUM(amount), 0) AS advance_amount
    ')
            ->first();

        $patientAdvanceReceiptCount = (int) (
            $patientAdvancePeriod->advance_count ?? 0
        );

        $patientAdvancesReceived = round(
            (float) ($patientAdvancePeriod->advance_amount ?? 0),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Posted Advance Applications — Reporting Period
        |--------------------------------------------------------------------------
        |
        | Use the debit to GL 2100 to measure advances applied.
        | Each application is counted once per journal.
        |
        */

        $advanceApplicationSummary = DB::table('journal_entries as journals')
            ->join(
                'journal_entry_lines as lines',
                'lines.journal_entry_id',
                '=',
                'journals.id'
            )
            ->join(
                'general_ledger_accounts as accounts',
                'accounts.id',
                '=',
                'lines.general_ledger_account_id'
            )
            ->where('journals.status', 'posted')
            ->where('journals.source_type', 'ip_billing_account')
            ->where('journals.source_event', 'advance_applied')
            ->whereBetween('journals.journal_date', [$from, $to])
            ->where('accounts.account_code', '2100')
            ->selectRaw('
        COUNT(DISTINCT journals.id) AS application_count,
        COALESCE(SUM(lines.debit), 0) AS applied_amount
    ')
            ->first();

        $patientAdvanceApplicationCount = (int) (
            $advanceApplicationSummary->application_count ?? 0
        );

        $patientAdvancesApplied = round(
            (float) ($advanceApplicationSummary->applied_amount ?? 0),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | MHIS Claims — Current Operational Snapshot
        |--------------------------------------------------------------------------
        |
        | Expected and approved claims must remain distinct.
        | Reimbursement receipts are reported separately.
        |
        */

        $mhisClaimSummary = DB::table('ip_billing_mhis_claims')
            ->selectRaw('
        COUNT(*) AS claim_count,
        COALESCE(SUM(claim_amount), 0) AS claimed_amount,
        COALESCE(SUM(expected_amount), 0) AS expected_amount,
        COALESCE(SUM(approved_amount), 0) AS approved_amount
    ')
            ->first();

        $mhisClaimCount = (int) (
            $mhisClaimSummary->claim_count ?? 0
        );

        $mhisTotalClaimed = round(
            (float) ($mhisClaimSummary->claimed_amount ?? 0),
            2
        );

        $mhisTotalExpected = round(
            (float) ($mhisClaimSummary->expected_amount ?? 0),
            2
        );

        $mhisTotalApproved = round(
            (float) ($mhisClaimSummary->approved_amount ?? 0),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | MHIS Receipts — Active Receipts to Date
        |--------------------------------------------------------------------------
        */

        $mhisReceiptSummary = DB::table('ip_billing_mhis_receipts')
            ->where('status', 'active')
            ->whereDate('receipt_date', '<=', $receivablesAsOfDate)
            ->selectRaw('
        COUNT(*) AS receipt_count,
        COALESCE(SUM(amount), 0) AS received_amount
    ')
            ->first();

        $mhisReceiptCount = (int) (
            $mhisReceiptSummary->receipt_count ?? 0
        );

        $mhisAmountReceived = round(
            (float) ($mhisReceiptSummary->received_amount ?? 0),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | MHIS Claim Status Summary
        |--------------------------------------------------------------------------
        */

        $mhisClaimsByStatus = DB::table('ip_billing_mhis_claims')
            ->selectRaw('
        status,
        COUNT(*) AS claim_count,
        COALESCE(SUM(expected_amount), 0) AS expected_amount,
        COALESCE(SUM(approved_amount), 0) AS approved_amount
    ')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | MHIS Outstanding — Provisional Claim-level Calculation
        |--------------------------------------------------------------------------
        |
        | An approved claim balance is calculated from approved
        | amounts less active receipts linked to that claim.
        |
        | Submitted but unapproved claims are shown separately.
        |
        */

        $mhisClaimBalances = DB::table('ip_billing_mhis_claims as claims')
            ->leftJoinSub(
                DB::table('ip_billing_mhis_receipts')
                    ->where('status', 'active')
                    ->whereDate('receipt_date', '<=', $receivablesAsOfDate)
                    ->selectRaw('
                ip_billing_mhis_claim_id,
                COALESCE(SUM(amount), 0) AS received_amount
            ')
                    ->groupBy('ip_billing_mhis_claim_id'),
                'receipts',
                'receipts.ip_billing_mhis_claim_id',
                '=',
                'claims.id'
            )
            ->select([
                'claims.id',
                'claims.claim_no',
                'claims.status',
                'claims.expected_amount',
                'claims.approved_amount',
                DB::raw(
                    'COALESCE(receipts.received_amount, 0) AS received_amount'
                ),
            ])
            ->get();

        $mhisApprovedOutstanding = round(
            (float) $mhisClaimBalances->sum(function ($claim) {
                return max(
                    0,
                    (float) $claim->approved_amount
                    - (float) $claim->received_amount
                );
            }),
            2
        );

        $mhisPendingApprovalExpected = round(
            (float) $mhisClaimBalances
                ->filter(fn ($claim) => $claim->status === 'submitted'
                    && (float) $claim->approved_amount <= 0
                )
                ->sum('expected_amount'),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Finance MIS — MHIS Receivable Reconciliation
        |--------------------------------------------------------------------------
        |
        | Read-only reconciliation.
        |
        | Operational billing balances represent current patient
        | liability after expected MHIS coverage.
        |
        | Posted GL receivables are measured through the selected
        | end date.
        |
        | This report identifies pending MHIS-related GL exposure.
        | It does not recognize unapproved claims as income.
        |
        */

        $mhisPendingGlExposureRows = DB::table(
            'ip_billing_accounts as billing'
        )
            ->join(
                'ip_billing_mhis_claims as claims',
                'claims.ip_billing_account_id',
                '=',
                'billing.id'
            )
            ->where('billing.status', 'finalized')
            ->where('claims.status', 'submitted')
            ->whereRaw(
                'COALESCE(claims.approved_amount, 0) <= 0'
            )
            ->select([
                'billing.id as billing_account_id',
                'billing.account_no',
                'billing.net_amount',
                'billing.balance_amount',
                'claims.id as claim_id',
                'claims.claim_no',
                'claims.expected_amount',
                'claims.approved_amount',
            ])
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Posted Patient Receivable Journals by Billing Account
        |--------------------------------------------------------------------------
        |
        | Resolve originating account IDs for:
        | - finalized IPD bills;
        | - advance applications;
        | - direct IPD payments;
        | - approved MHIS claim adjustments.
        |
        | Journal source IDs are not always billing account IDs.
        |
        */

        $ipdAccountJournals = DB::table(
            'journal_entry_lines as lines'
        )
            ->join(
                'journal_entries as journals',
                'journals.id',
                '=',
                'lines.journal_entry_id'
            )
            ->join(
                'general_ledger_accounts as accounts',
                'accounts.id',
                '=',
                'lines.general_ledger_account_id'
            )
            ->where('journals.status', 'posted')
            ->whereDate(
                'journals.journal_date',
                '<=',
                $receivablesAsOfDate
            )
            ->where('accounts.account_code', '1200')
            ->where('journals.source_type', 'ip_billing_account')
            ->selectRaw('
        journals.source_id AS billing_account_id,
        COALESCE(SUM(lines.debit - lines.credit), 0)
            AS net_receivable
    ')
            ->groupBy('journals.source_id')
            ->get()
            ->keyBy('billing_account_id');

        $ipdPaymentJournals = DB::table(
            'journal_entry_lines as lines'
        )
            ->join(
                'journal_entries as journals',
                'journals.id',
                '=',
                'lines.journal_entry_id'
            )
            ->join(
                'general_ledger_accounts as accounts',
                'accounts.id',
                '=',
                'lines.general_ledger_account_id'
            )
            ->join('ip_billing_payments as payments', function ($join) {
                $join->on(
                    'payments.id',
                    '=',
                    DB::raw(
                        "CASE WHEN journals.source_id ~ '^[0-9]+$'
                  THEN journals.source_id::bigint
                  ELSE NULL END"
                    )
                );
            })
            ->where('journals.status', 'posted')
            ->whereDate(
                'journals.journal_date',
                '<=',
                $receivablesAsOfDate
            )
            ->where('accounts.account_code', '1200')
            ->where('journals.source_type', 'ip_billing_payment')
            ->selectRaw('
        payments.ip_billing_account_id AS billing_account_id,
        COALESCE(SUM(lines.debit - lines.credit), 0)
            AS net_receivable
    ')
            ->groupBy('payments.ip_billing_account_id')
            ->get()
            ->keyBy('billing_account_id');

        $ipdMhisApprovalJournals = DB::table(
            'journal_entry_lines as lines'
        )
            ->join(
                'journal_entries as journals',
                'journals.id',
                '=',
                'lines.journal_entry_id'
            )
            ->join(
                'general_ledger_accounts as accounts',
                'accounts.id',
                '=',
                'lines.general_ledger_account_id'
            )
            ->join('ip_billing_mhis_claims as claims', function ($join) {
                $join->on(
                    'claims.id',
                    '=',
                    DB::raw(
                        "CASE
                WHEN journals.source_id ~ '^[0-9]+$'
                THEN journals.source_id::bigint
                ELSE NULL
             END"
                    )
                );
            })
            ->where('journals.status', 'posted')
            ->whereDate(
                'journals.journal_date',
                '<=',
                $receivablesAsOfDate
            )
            ->where('accounts.account_code', '1200')
            ->where('journals.source_type', 'ip_billing_mhis_claim')
            ->selectRaw('
        claims.ip_billing_account_id AS billing_account_id,
        COALESCE(SUM(lines.debit - lines.credit), 0)
            AS net_receivable
    ')
            ->groupBy('claims.ip_billing_account_id')
            ->get()
            ->keyBy('billing_account_id');

        /*
        |--------------------------------------------------------------------------
        | Pending MHIS Receivable Exposure
        |--------------------------------------------------------------------------
        */

        $mhisPendingReceivableDetails = $mhisPendingGlExposureRows
            ->groupBy('billing_account_id')
            ->map(function ($claims, $billingAccountId) use (
                $ipdAccountJournals,
                $ipdPaymentJournals,
                $ipdMhisApprovalJournals
            ) {
                $billing = $claims->first();

                $glNet = round(
                    (float) (
                        $ipdAccountJournals
                            ->get($billingAccountId)
                            ->net_receivable ?? 0
                    )
                    + (float) (
                        $ipdPaymentJournals
                            ->get($billingAccountId)
                            ->net_receivable ?? 0
                    )
                    + (float) (
                        $ipdMhisApprovalJournals
                            ->get($billingAccountId)
                            ->net_receivable ?? 0
                    ),
                    2
                );

                $billingBalance = (float) $billing->balance_amount;

                $unsettledGlDifference = max(
                    0,
                    round($glNet - $billingBalance, 2)
                );

                return [
                    'account_no' => $billing->account_no,
                    'claim_count' => $claims->count(),
                    'expected_amount' => round(
                        (float) $claims->sum('expected_amount'),
                        2
                    ),
                    'billing_balance' => $billingBalance,
                    'gl_receivable' => $glNet,
                    'pending_exposure' => $unsettledGlDifference,
                ];
            })
            ->values();

        $mhisPendingReceivableExposure = round(
            (float) $mhisPendingReceivableDetails
                ->sum('pending_exposure'),
            2
        );

        $patientUnexplainedReconciliationDifference = round(
            $patientReceivableDifference
            - $mhisPendingReceivableExposure,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Finance MIS — Charity Assistance
        |--------------------------------------------------------------------------
        |
        | Read-only reporting.
        |
        | Request counts use requested_at.
        | Approved amounts use approved_at.
        | Applied amounts use applied_at.
        |
        | These are separate event-based indicators.
        | Do not add requested, approved and applied amounts together.
        |
        */

        $charityBaseQuery = DB::table('charity_adjustments');

        $charityRequests = (clone $charityBaseQuery)
            ->whereBetween('requested_at', [$start, $end])
            ->selectRaw('
        COUNT(*) AS request_count,
        COALESCE(SUM(requested_amount), 0) AS requested_amount
    ')
            ->first();

        $charityRequestCount = (int) $charityRequests->request_count;

        $charityRequestedAmount = round(
            (float) $charityRequests->requested_amount,
            2
        );

        $charityApprovedAmount = round(
            (float) (clone $charityBaseQuery)
                ->whereIn('status', ['approved', 'applied'])
                ->whereBetween('approved_at', [$start, $end])
                ->sum('approved_amount'),
            2
        );

        $charityAppliedAmount = round(
            (float) (clone $charityBaseQuery)
                ->where('status', 'applied')
                ->whereBetween('applied_at', [$start, $end])
                ->sum('approved_amount'),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Current Charity Workflow Backlog
        |--------------------------------------------------------------------------
        |
        | Current statuses, not historical status snapshots.
        |
        */

        $charityPendingCount = (clone $charityBaseQuery)
            ->where('status', 'pending')
            ->count();

        $charityApprovedNotAppliedCount = (clone $charityBaseQuery)
            ->where('status', 'approved')
            ->count();

        $charityApprovedNotAppliedAmount = round(
            (float) (clone $charityBaseQuery)
                ->where('status', 'approved')
                ->sum('approved_amount'),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Charity Assistance by Billing Source
        |--------------------------------------------------------------------------
        |
        | IPD has priority if a billing account/admission is present.
        | OPD uses invoice/encounter references otherwise.
        | Unlinked records remain visible separately.
        |
        */

        $charityBySource = (clone $charityBaseQuery)
            ->where('status', 'applied')
            ->whereBetween('applied_at', [$start, $end])
            ->selectRaw("
        CASE
            WHEN ip_billing_account_id IS NOT NULL
              OR admission_id IS NOT NULL
                THEN 'IPD'
            WHEN invoice_id IS NOT NULL
              OR encounter_id IS NOT NULL
                THEN 'OPD / Encounter'
            ELSE 'Unlinked'
        END AS source_name,
        COUNT(*) AS adjustments,
        COALESCE(SUM(approved_amount), 0) AS applied_amount
    ")
            ->groupByRaw("
        CASE
            WHEN ip_billing_account_id IS NOT NULL
              OR admission_id IS NOT NULL
                THEN 'IPD'
            WHEN invoice_id IS NOT NULL
              OR encounter_id IS NOT NULL
                THEN 'OPD / Encounter'
            ELSE 'Unlinked'
        END
    ")
            ->orderByDesc('applied_amount')
            ->get();

        $charityPatientsAssisted = (clone $charityBaseQuery)
            ->where('status', 'applied')
            ->whereBetween('applied_at', [$start, $end])
            ->whereNotNull('patient_id')
            ->distinct()
            ->count('patient_id');

        /*
        |--------------------------------------------------------------------------
        | Finance MIS — Staff Medical Benefit Utilization
        |--------------------------------------------------------------------------
        |
        | Utilization consumes entitlement.
        | Reversals restore entitlement.
        | Only active transactions affect net utilization.
        |
        */

        $staffBenefitPeriodQuery = DB::table(
            'staff_medical_benefit_transactions'
        )
            ->where('status', 'active')
            ->whereBetween('transaction_date', [$from, $to]);

        $staffBenefitSummary = (clone $staffBenefitPeriodQuery)
            ->selectRaw("
        COUNT(*) FILTER (
            WHERE transaction_type = 'utilization'
        ) AS utilization_count,

        COALESCE(SUM(amount) FILTER (
            WHERE transaction_type = 'utilization'
        ), 0) AS utilization_amount,

        COUNT(*) FILTER (
            WHERE transaction_type = 'reversal'
        ) AS reversal_count,

        COALESCE(SUM(amount) FILTER (
            WHERE transaction_type = 'reversal'
        ), 0) AS reversal_amount
    ")
            ->first();

        $staffBenefitUtilizationCount = (int) (
            $staffBenefitSummary->utilization_count ?? 0
        );

        $staffBenefitUtilizedAmount = round(
            (float) ($staffBenefitSummary->utilization_amount ?? 0),
            2
        );

        $staffBenefitReversalCount = (int) (
            $staffBenefitSummary->reversal_count ?? 0
        );

        $staffBenefitReversedAmount = round(
            (float) ($staffBenefitSummary->reversal_amount ?? 0),
            2
        );

        $staffBenefitNetUtilization = round(
            $staffBenefitUtilizedAmount
            - $staffBenefitReversedAmount,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Employee vs Dependent Utilization
        |--------------------------------------------------------------------------
        */

        $staffBenefitByBeneficiary = (clone $staffBenefitPeriodQuery)
            ->selectRaw("
        beneficiary_type,

        COUNT(*) FILTER (
            WHERE transaction_type = 'utilization'
        ) AS utilization_count,

        COALESCE(SUM(
            CASE
                WHEN transaction_type = 'utilization'
                    THEN amount
                WHEN transaction_type = 'reversal'
                    THEN -amount
                ELSE 0
            END
        ), 0) AS net_utilization
    ")
            ->groupBy('beneficiary_type')
            ->orderBy('beneficiary_type')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Staff Benefit Entitlement Position
        |--------------------------------------------------------------------------
        |
        | Position as of selected end date.
        | Limit to accounts in their financial-year and eligibility window.
        |
        | Entitlements are authorized limits, not expenditure.
        |
        */

        $staffEntitlementAccounts = DB::table(
            'staff_medical_benefit_accounts as accounts'
        )
            ->where('accounts.status', 'active')
            ->whereDate(
                'accounts.financial_year_start',
                '<=',
                $to
            )
            ->whereDate(
                'accounts.financial_year_end',
                '>=',
                $to
            )
            ->where(function ($query) use ($to) {
                $query->whereNull('accounts.eligible_from')
                    ->orWhereDate(
                        'accounts.eligible_from',
                        '<=',
                        $to
                    );
            })
            ->where(function ($query) use ($to) {
                $query->whereNull('accounts.eligible_until')
                    ->orWhereDate(
                        'accounts.eligible_until',
                        '>=',
                        $to
                    );
            })
            ->select([
                'accounts.id',
                'accounts.employee_id',
                'accounts.employee_entitlement',
                'accounts.dependent_family_entitlement',
            ])
            ->get();

        $staffEntitlementAccountCount =
            $staffEntitlementAccounts->count();

        $staffEmployeeEntitlement = round(
            (float) $staffEntitlementAccounts
                ->sum('employee_entitlement'),
            2
        );

        $staffDependentEntitlement = round(
            (float) $staffEntitlementAccounts
                ->sum('dependent_family_entitlement'),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Cumulative Entitlement Utilization
        |--------------------------------------------------------------------------
        |
        | Sum transactions for eligible accounts through the selected
        | end date, including reversals.
        |
        */

        $eligibleBenefitAccountIds = $staffEntitlementAccounts
            ->pluck('id')
            ->all();

        $staffCumulativeUtilization = DB::table(
            'staff_medical_benefit_transactions'
        )
            ->whereIn(
                'benefit_account_id',
                $eligibleBenefitAccountIds
            )
            ->where('status', 'active')
            ->whereDate('transaction_date', '<=', $to)
            ->selectRaw("
        beneficiary_type,
        COALESCE(SUM(
            CASE
                WHEN transaction_type = 'utilization'
                    THEN amount
                WHEN transaction_type = 'reversal'
                    THEN -amount
                ELSE 0
            END
        ), 0) AS net_utilization
    ")
            ->groupBy('beneficiary_type')
            ->get()
            ->keyBy('beneficiary_type');

        $staffEmployeeCumulativeUsed = round(
            (float) (
                $staffCumulativeUtilization
                    ->get('employee')
                    ->net_utilization ?? 0
            ),
            2
        );

        $staffDependentCumulativeUsed = round(
            (float) (
                $staffCumulativeUtilization
                    ->get('dependent')
                    ->net_utilization ?? 0
            ),
            2
        );

        $staffEmployeeRemaining = max(
            0,
            round(
                $staffEmployeeEntitlement
                - $staffEmployeeCumulativeUsed,
                2
            )
        );

        $staffDependentRemaining = max(
            0,
            round(
                $staffDependentEntitlement
                - $staffDependentCumulativeUsed,
                2
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Pharmacy MIS — Sales & Collections
        |--------------------------------------------------------------------------
        |
        | Operational reporting only.
        | Completed and credit sales are included.
        | IPD billing is not counted as pharmacy counter collection.
        |
        */

        $pharmacySalesBase = DB::table('pharmacy_sales')
            ->whereIn('status', ['completed', 'credit'])
            ->whereBetween('sale_at', [$start, $end]);

        $pharmacySalesSummary = (clone $pharmacySalesBase)
            ->selectRaw("
        COUNT(*) AS sale_count,
        COALESCE(SUM(subtotal), 0) AS subtotal,
        COALESCE(SUM(discount), 0) AS discounts,
        COALESCE(SUM(total_amount), 0) AS total_sales,
        COALESCE(SUM(
            CASE
                WHEN payment_mode IN ('cash', 'upi', 'card')
                THEN paid_amount
                ELSE 0
            END
        ), 0) AS counter_collections,
        COALESCE(SUM(
            CASE
                WHEN payment_mode = 'ip_billing'
                THEN total_amount
                ELSE 0
            END
        ), 0) AS ipd_charges,
        COALESCE(SUM(
            CASE
                WHEN payment_mode IN ('credit', 'mhis')
                THEN balance_amount
                ELSE 0
            END
        ), 0) AS initial_credit_balance
    ")
            ->first();

        $pharmacySaleCount = (int) $pharmacySalesSummary->sale_count;
        $pharmacyGrossSales = (float) $pharmacySalesSummary->subtotal;
        $pharmacyDiscounts = (float) $pharmacySalesSummary->discounts;
        $pharmacyTotalSales = (float) $pharmacySalesSummary->total_sales;
        $pharmacyCounterCollections = (float) $pharmacySalesSummary->counter_collections;
        $pharmacyIpdCharges = (float) $pharmacySalesSummary->ipd_charges;
        $pharmacyInitialCreditBalance = (float) $pharmacySalesSummary->initial_credit_balance;

        /*
        |--------------------------------------------------------------------------
        | Pharmacy Sales by Payment Mode
        |--------------------------------------------------------------------------
        */

        $pharmacySalesByMode = (clone $pharmacySalesBase)
            ->selectRaw("
        COALESCE(payment_mode, 'unspecified') AS mode,
        COUNT(*) AS sale_count,
        COALESCE(SUM(total_amount), 0) AS sale_amount,
        COALESCE(SUM(paid_amount), 0) AS recorded_paid
    ")
            ->groupBy('payment_mode')
            ->orderByDesc('sale_amount')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Pharmacy Sales by Patient Source
        |--------------------------------------------------------------------------
        |
        | Admission reference takes precedence over encounter reference.
        |
        */

        $pharmacySalesBySource = (clone $pharmacySalesBase)
            ->selectRaw("
        CASE
            WHEN admission_id IS NOT NULL THEN 'IPD'
            WHEN encounter_id IS NOT NULL THEN 'OPD / Encounter'
            ELSE 'Other'
        END AS source_name,
        COUNT(*) AS sale_count,
        COALESCE(SUM(total_amount), 0) AS sale_amount
    ")
            ->groupByRaw("
        CASE
            WHEN admission_id IS NOT NULL THEN 'IPD'
            WHEN encounter_id IS NOT NULL THEN 'OPD / Encounter'
            ELSE 'Other'
        END
    ")
            ->orderByDesc('sale_amount')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Pharmacy Sales by Workflow Status
        |--------------------------------------------------------------------------
        */

        $pharmacySalesByStatus = (clone $pharmacySalesBase)
            ->selectRaw('
        status,
        COUNT(*) AS sale_count,
        COALESCE(SUM(total_amount), 0) AS sale_amount
    ')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Dashboard response
        |--------------------------------------------------------------------------
        */

        /*
        |--------------------------------------------------------------------------
        | Pharmacy MIS — Patient Returns & Net Sales
        |--------------------------------------------------------------------------
        |
        | Returns are measured by returned_at.
        | Only completed returns are included.
        | Refund amounts represent sales adjustments,
        | not necessarily actual cash refunds.
        |
        */

        $pharmacyReturnsBase = DB::table('pharmacy_returns')
            ->where('status', 'completed')
            ->whereBetween('returned_at', [$start, $end]);

        $pharmacyReturnSummary = (clone $pharmacyReturnsBase)
            ->selectRaw("
        COUNT(*) AS return_count,
        COALESCE(SUM(gross_amount), 0) AS gross_returns,
        COALESCE(SUM(refund_amount), 0) AS return_adjustments,

        COALESCE(SUM(
            CASE
                WHEN refund_mode = 'cash'
                THEN refund_amount
                ELSE 0
            END
        ), 0) AS cash_refunds,

        COALESCE(SUM(
            CASE
                WHEN refund_mode IN ('upi', 'card')
                THEN refund_amount
                ELSE 0
            END
        ), 0) AS digital_refunds
    ")
            ->first();

        $pharmacyReturnCount =
            (int) $pharmacyReturnSummary->return_count;

        $pharmacyGrossReturns =
            round((float) $pharmacyReturnSummary->gross_returns, 2);

        $pharmacyReturnAdjustments =
            round((float) $pharmacyReturnSummary->return_adjustments, 2);

        $pharmacyCashRefunds =
            round((float) $pharmacyReturnSummary->cash_refunds, 2);

        $pharmacyDigitalRefunds =
            round((float) $pharmacyReturnSummary->digital_refunds, 2);

        /*
        |--------------------------------------------------------------------------
        | IPD Billing Return Adjustments
        |--------------------------------------------------------------------------
        |
        | IPD returns use refund_mode = none.
        | Identify IPD using the original sale admission_id.
        |
        */

        $pharmacyIpdReturnAdjustments = DB::table(
            'pharmacy_returns as returns'
        )
            ->join(
                'pharmacy_sales as sales',
                'sales.id',
                '=',
                'returns.pharmacy_sale_id'
            )
            ->where('returns.status', 'completed')
            ->whereNotNull('sales.admission_id')
            ->whereBetween('returns.returned_at', [$start, $end])
            ->sum('returns.refund_amount');

        $pharmacyIpdReturnAdjustments = round(
            (float) $pharmacyIpdReturnAdjustments,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Net Operational Pharmacy Sales
        |--------------------------------------------------------------------------
        |
        | Sales are grouped by sale_at.
        | Returns are grouped by returned_at.
        |
        | This is a period movement calculation, not a
        | same-cohort sales profitability calculation.
        |
        */

        $pharmacyNetSales = round(
            $pharmacyTotalSales - $pharmacyReturnAdjustments,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Pharmacy Returns by Refund Mode
        |--------------------------------------------------------------------------
        */

        $pharmacyReturnsByMode = (clone $pharmacyReturnsBase)
            ->selectRaw("
        COALESCE(refund_mode, 'unspecified') AS refund_mode,
        COUNT(*) AS return_count,
        COALESCE(SUM(refund_amount), 0) AS return_amount
    ")
            ->groupBy('refund_mode')
            ->orderByDesc('return_amount')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Medicine Quantities Returned
        |--------------------------------------------------------------------------
        */

        $pharmacyReturnedUnits = DB::table(
            'pharmacy_return_items as items'
        )
            ->join(
                'pharmacy_returns as returns',
                'returns.id',
                '=',
                'items.pharmacy_return_id'
            )
            ->where('returns.status', 'completed')
            ->whereBetween('returns.returned_at', [$start, $end])
            ->sum('items.quantity');

        $pharmacyReturnedUnits = (int) $pharmacyReturnedUnits;

        /*
        |--------------------------------------------------------------------------
        | Pharmacy MIS — Procurement
        |--------------------------------------------------------------------------
        |
        | Completed GRNs and supplier returns only.
        | Procurement is not treated as an operating expense.
        |
        */

        $pharmacyGrnsBase = DB::table('pharmacy_grns')
            ->where('status', 'completed')
            ->whereBetween('grn_date', [$from, $to]);

        $pharmacyGrnSummary = (clone $pharmacyGrnsBase)
            ->selectRaw('
        COUNT(*) AS grn_count,
        COALESCE(SUM(subtotal), 0) AS subtotal,
        COALESCE(SUM(discount_amount), 0) AS discounts,
        COALESCE(SUM(total_amount), 0) AS purchase_total
    ')
            ->first();

        $pharmacyGrnCount = (int) $pharmacyGrnSummary->grn_count;
        $pharmacyPurchaseTotal = round(
            (float) $pharmacyGrnSummary->purchase_total, 2
        );
        $pharmacyPurchaseDiscounts = round(
            (float) $pharmacyGrnSummary->discounts, 2
        );

        /*
        |--------------------------------------------------------------------------
        | Supplier Purchase Returns
        |--------------------------------------------------------------------------
        */

        $pharmacyPurchaseReturnsBase = DB::table(
            'pharmacy_purchase_returns'
        )
            ->where('status', 'completed')
            ->whereBetween('return_date', [$from, $to]);

        $pharmacyPurchaseReturnSummary =
            (clone $pharmacyPurchaseReturnsBase)
                ->selectRaw('
            COUNT(*) AS return_count,
            COALESCE(SUM(total_amount), 0) AS return_total
        ')
                ->first();

        $pharmacyPurchaseReturnCount =
            (int) $pharmacyPurchaseReturnSummary->return_count;

        $pharmacyPurchaseReturnTotal = round(
            (float) $pharmacyPurchaseReturnSummary->return_total,
            2
        );

        $pharmacyNetProcurement = round(
            $pharmacyPurchaseTotal - $pharmacyPurchaseReturnTotal,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | GRN Medicine Quantities
        |--------------------------------------------------------------------------
        |
        | received_units includes purchased and bonus stock.
        | These quantities must not be added to financial values.
        |
        */

        $pharmacyGrnQuantities = DB::table(
            'pharmacy_grn_items as items'
        )
            ->join(
                'pharmacy_grns as grns',
                'grns.id',
                '=',
                'items.pharmacy_grn_id'
            )
            ->where('grns.status', 'completed')
            ->whereBetween('grns.grn_date', [$from, $to])
            ->selectRaw('
        COALESCE(SUM(items.received_units), 0) AS received_units,
        COALESCE(SUM(
            items.bonus_qty * items.units_per_pack
        ), 0) AS bonus_units
    ')
            ->first();

        $pharmacyReceivedUnits =
            (int) $pharmacyGrnQuantities->received_units;

        $pharmacyBonusUnits =
            (int) $pharmacyGrnQuantities->bonus_units;

        /*
        |--------------------------------------------------------------------------
        | Supplier-Wise Procurement
        |--------------------------------------------------------------------------
        */

        $pharmacySupplierPurchases = DB::table(
            'pharmacy_grns as grns'
        )
            ->leftJoin(
                'pharmacy_suppliers as suppliers',
                'suppliers.id',
                '=',
                'grns.pharmacy_supplier_id'
            )
            ->where('grns.status', 'completed')
            ->whereBetween('grns.grn_date', [$from, $to])
            ->selectRaw('
        grns.pharmacy_supplier_id AS supplier_id,
        COALESCE(MAX(suppliers.name), \'Unknown Supplier\')
            AS supplier_name,
        COUNT(grns.id) AS grn_count,
        COALESCE(SUM(grns.total_amount), 0)
            AS purchase_total
    ')
            ->groupBy('grns.pharmacy_supplier_id')
            ->orderByDesc('purchase_total')
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Pharmacy MIS — Current Inventory Snapshot
        |--------------------------------------------------------------------------
        |
        | Uses location balances as the stock quantity source.
        | Inventory is valued at batch purchase price.
        | All values represent current inventory, not historical stock.
        |
        */

        $pharmacyInventoryBase = DB::table(
            'pharmacy_stock_location_balances as balances'
        )
            ->join(
                'pharmacy_stock_batches as batches',
                'batches.id',
                '=',
                'balances.pharmacy_stock_batch_id'
            )
            ->where('balances.quantity_available', '>', 0);

        $pharmacyInventorySummary = (clone $pharmacyInventoryBase)
            ->selectRaw('
        COUNT(DISTINCT batches.medicine_id) AS medicine_count,
        COUNT(DISTINCT batches.id) AS batch_count,
        COALESCE(SUM(balances.quantity_available), 0)
            AS stock_units,
        COALESCE(SUM(
            balances.quantity_available
            * COALESCE(batches.purchase_price, 0)
        ), 0) AS inventory_cost,
        COALESCE(SUM(
            balances.quantity_available
            * COALESCE(batches.selling_price, 0)
        ), 0) AS inventory_retail_value
    ')
            ->first();

        $pharmacyStockMedicines =
            (int) $pharmacyInventorySummary->medicine_count;

        $pharmacyStockBatches =
            (int) $pharmacyInventorySummary->batch_count;

        $pharmacyStockUnits =
            (int) $pharmacyInventorySummary->stock_units;

        $pharmacyInventoryCost = round(
            (float) $pharmacyInventorySummary->inventory_cost,
            2
        );

        $pharmacyInventoryRetailValue = round(
            (float) $pharmacyInventorySummary->inventory_retail_value,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Stock by Location
        |--------------------------------------------------------------------------
        |
        | Include locations with zero stock.
        |
        */

        $pharmacyStockByLocation = DB::table(
            'pharmacy_stock_locations as locations'
        )
            ->leftJoin(
                'pharmacy_stock_location_balances as balances',
                'balances.pharmacy_stock_location_id',
                '=',
                'locations.id'
            )
            ->leftJoin(
                'pharmacy_stock_batches as batches',
                'batches.id',
                '=',
                'balances.pharmacy_stock_batch_id'
            )
            ->selectRaw('
        locations.id,
        locations.code,
        locations.name,
        locations.location_type,

        COALESCE(SUM(
            GREATEST(
                COALESCE(balances.quantity_available, 0),
                0
            )
        ), 0) AS stock_units,

        COALESCE(SUM(
            GREATEST(
                COALESCE(balances.quantity_available, 0),
                0
            ) * COALESCE(batches.purchase_price, 0)
        ), 0) AS inventory_cost
    ')
            ->groupBy(
                'locations.id',
                'locations.code',
                'locations.name',
                'locations.location_type'
            )
            ->orderBy('locations.id')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Low-Stock and Zero-Stock Locations
        |--------------------------------------------------------------------------
        |
        | Reorder thresholds are defined per location/batch.
        |
        */

        $pharmacyLowStockCount = DB::table(
            'pharmacy_stock_location_balances'
        )
            ->whereColumn('quantity_available', '<=', 'reorder_level')
            ->count();

        $pharmacyZeroStockCount = DB::table(
            'pharmacy_stock_location_balances'
        )
            ->where('quantity_available', '<=', 0)
            ->count();

        /*
        |--------------------------------------------------------------------------
        | Batch Expiry Monitoring
        |--------------------------------------------------------------------------
        |
        | Mutually exclusive date bands.
        | Counts only quantities currently available in locations.
        |
        */

        $pharmacyExpirySummary = (clone $pharmacyInventoryBase)
            ->whereNotNull('batches.expiry_date')
            ->selectRaw('
        COALESCE(SUM(
            CASE
                WHEN batches.expiry_date < CURRENT_DATE
                THEN balances.quantity_available
                ELSE 0
            END
        ), 0) AS expired_units,

        COALESCE(SUM(
            CASE
                WHEN batches.expiry_date >= CURRENT_DATE
                 AND batches.expiry_date <= CURRENT_DATE + 30
                THEN balances.quantity_available
                ELSE 0
            END
        ), 0) AS expiry_0_30_units,

        COALESCE(SUM(
            CASE
                WHEN batches.expiry_date > CURRENT_DATE + 30
                 AND batches.expiry_date <= CURRENT_DATE + 60
                THEN balances.quantity_available
                ELSE 0
            END
        ), 0) AS expiry_31_60_units,

        COALESCE(SUM(
            CASE
                WHEN batches.expiry_date > CURRENT_DATE + 60
                 AND batches.expiry_date <= CURRENT_DATE + 90
                THEN balances.quantity_available
                ELSE 0
            END
        ), 0) AS expiry_61_90_units,

        COALESCE(SUM(
            CASE
                WHEN batches.expiry_date < CURRENT_DATE
                THEN balances.quantity_available
                     * COALESCE(batches.purchase_price, 0)
                ELSE 0
            END
        ), 0) AS expired_stock_value
    ')
            ->first();

        $pharmacyExpiredUnits =
            (int) $pharmacyExpirySummary->expired_units;

        $pharmacyExpiring30Units =
            (int) $pharmacyExpirySummary->expiry_0_30_units;

        $pharmacyExpiring60Units =
            (int) $pharmacyExpirySummary->expiry_31_60_units;

        $pharmacyExpiring90Units =
            (int) $pharmacyExpirySummary->expiry_61_90_units;

        $pharmacyExpiredStockValue = round(
            (float) $pharmacyExpirySummary->expired_stock_value,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Pharmacy MIS — Stock Reconciliation
        |--------------------------------------------------------------------------
        |
        | Compare batch-level stock with the sum of location balances.
        | Includes batches even if they have no location balance records.
        |
        */

        $pharmacyStockReconciliation = DB::table(
            'pharmacy_stock_batches as batches'
        )
            ->leftJoin(
                'pharmacy_stock_location_balances as balances',
                'balances.pharmacy_stock_batch_id',
                '=',
                'batches.id'
            )
            ->selectRaw('
        batches.id,
        batches.batch_number,
        batches.quantity_available AS batch_quantity,
        COALESCE(SUM(balances.quantity_available), 0)
            AS location_quantity
    ')
            ->groupBy(
                'batches.id',
                'batches.batch_number',
                'batches.quantity_available'
            )
            ->get();

        $pharmacyReconciliationBatchCount =
            $pharmacyStockReconciliation->count();

        $pharmacyReconciliationMismatchCount = 0;
        $pharmacyReconciliationDifferenceUnits = 0;

        $pharmacyStockMismatchDetails = collect();

        foreach ($pharmacyStockReconciliation as $batch) {
            $difference =
                (int) $batch->batch_quantity
                - (int) $batch->location_quantity;

            if ($difference !== 0) {
                $pharmacyReconciliationMismatchCount++;

                $pharmacyReconciliationDifferenceUnits +=
                    abs($difference);

                $pharmacyStockMismatchDetails->push([
                    'batch_number' => $batch->batch_number,
                    'batch_quantity' => (int) $batch->batch_quantity,
                    'location_quantity' => (int) $batch->location_quantity,
                    'difference' => $difference,
                ]);
            }
        }

        $pharmacyStockReconciled =
            $pharmacyReconciliationMismatchCount === 0;

        /*
        |--------------------------------------------------------------------------
        | Pharmacy MIS — Stock Transfers
        |--------------------------------------------------------------------------
        |
        | Include received transfers only.
        | Use received_at as the reporting event date.
        | Internal transfers are not purchases or revenue.
        |
        */

        $pharmacyTransfersBase = DB::table(
            'pharmacy_stock_transfers'
        )
            ->where('status', 'received')
            ->whereBetween('received_at', [$start, $end]);

        $pharmacyTransferCount = (clone $pharmacyTransfersBase)
            ->count();

        $pharmacyTransferredUnits = DB::table(
            'pharmacy_stock_transfer_items as items'
        )
            ->join(
                'pharmacy_stock_transfers as transfers',
                'transfers.id',
                '=',
                'items.pharmacy_stock_transfer_id'
            )
            ->where('transfers.status', 'received')
            ->whereBetween('transfers.received_at', [$start, $end])
            ->sum('items.quantity_received');

        $pharmacyTransferredUnits = (int) $pharmacyTransferredUnits;

        /*
        |--------------------------------------------------------------------------
        | Stock Transfers by Destination
        |--------------------------------------------------------------------------
        */

        $pharmacyTransfersByDestination = DB::table(
            'pharmacy_stock_transfer_items as items'
        )
            ->join(
                'pharmacy_stock_transfers as transfers',
                'transfers.id',
                '=',
                'items.pharmacy_stock_transfer_id'
            )
            ->leftJoin(
                'pharmacy_stock_locations as locations',
                'locations.id',
                '=',
                'transfers.to_location_id'
            )
            ->where('transfers.status', 'received')
            ->whereBetween('transfers.received_at', [$start, $end])
            ->selectRaw("
        transfers.to_location_id AS location_id,
        COALESCE(
            MAX(locations.name),
            'Unknown Location'
        ) AS location_name,
        COUNT(DISTINCT transfers.id) AS transfer_count,
        COALESCE(SUM(items.quantity_received), 0)
            AS received_units
    ")
            ->groupBy('transfers.to_location_id')
            ->orderByDesc('received_units')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Pharmacy MIS — Medicine Disposals
        |--------------------------------------------------------------------------
        |
        | Completed disposals only.
        | Values are operational inventory losses.
        | They are not automatically recognized GL expenses.
        |
        */

        $pharmacyDisposalsBase = DB::table(
            'pharmacy_disposals'
        )
            ->where('status', 'completed')
            ->whereBetween('disposed_at', [$start, $end]);

        $pharmacyDisposalCount = (clone $pharmacyDisposalsBase)
            ->count();

        $pharmacyDisposalSummary = DB::table(
            'pharmacy_disposal_items as items'
        )
            ->join(
                'pharmacy_disposals as disposals',
                'disposals.id',
                '=',
                'items.pharmacy_disposal_id'
            )
            ->where('disposals.status', 'completed')
            ->whereBetween('disposals.disposed_at', [$start, $end])
            ->selectRaw('
        COALESCE(SUM(items.quantity), 0)
            AS disposed_units,
        COALESCE(SUM(items.stock_value), 0)
            AS disposal_value
    ')
            ->first();

        $pharmacyDisposedUnits = (int) (
            $pharmacyDisposalSummary->disposed_units ?? 0
        );

        $pharmacyDisposalValue = round(
            (float) (
                $pharmacyDisposalSummary->disposal_value ?? 0
            ),
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Disposal Reasons
        |--------------------------------------------------------------------------
        */

        $pharmacyDisposalsByReason = DB::table(
            'pharmacy_disposals as disposals'
        )
            ->leftJoin(
                'pharmacy_disposal_items as items',
                'items.pharmacy_disposal_id',
                '=',
                'disposals.id'
            )
            ->where('disposals.status', 'completed')
            ->whereBetween('disposals.disposed_at', [$start, $end])
            ->selectRaw("
        COALESCE(
            disposals.reason,
            'Unspecified'
        ) AS disposal_reason,
        COUNT(DISTINCT disposals.id) AS disposal_count,
        COALESCE(SUM(items.quantity), 0)
            AS disposed_units,
        COALESCE(SUM(items.stock_value), 0)
            AS disposal_value
    ")
            ->groupBy('disposals.reason')
            ->orderByDesc('disposal_value')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Pharmacy MIS — Supplier Finance
        |--------------------------------------------------------------------------
        |
        | Payables and credits are current snapshots.
        | Payments follow the selected reporting period.
        | No accounting journals are created.
        |
        */

        $pharmacyPayableSummary = DB::table(
            'pharmacy_supplier_payables'
        )
            ->selectRaw("
        COUNT(*) AS invoice_count,

        COALESCE(SUM(original_amount), 0)
            AS original_amount,

        COALESCE(SUM(return_adjustment), 0)
            AS return_adjustments,

        COALESCE(SUM(other_adjustment), 0)
            AS other_adjustments,

        COALESCE(SUM(paid_amount), 0)
            AS cumulative_paid,

        COALESCE(SUM(outstanding_amount), 0)
            AS outstanding_amount,

        COUNT(*) FILTER (
            WHERE status = 'unpaid'
        ) AS unpaid_count,

        COUNT(*) FILTER (
            WHERE status = 'partially_paid'
        ) AS partially_paid_count
    ")
            ->first();

        $pharmacyPayableCount =
            (int) $pharmacyPayableSummary->invoice_count;

        $pharmacySupplierOutstanding = round(
            (float) $pharmacyPayableSummary->outstanding_amount,
            2
        );

        $pharmacyUnpaidPayables =
            (int) $pharmacyPayableSummary->unpaid_count;

        $pharmacyPartiallyPaidPayables =
            (int) $pharmacyPayableSummary->partially_paid_count;

        /*
        |--------------------------------------------------------------------------
        | Overdue Supplier Invoices
        |--------------------------------------------------------------------------
        */

        $pharmacyOverdueSummary = DB::table(
            'pharmacy_supplier_payables'
        )
            ->where('outstanding_amount', '>', 0)
            ->whereDate('due_date', '<', today())
            ->selectRaw('
        COUNT(*) AS overdue_count,
        COALESCE(SUM(outstanding_amount), 0)
            AS overdue_amount
    ')
            ->first();

        $pharmacyOverduePayables =
            (int) $pharmacyOverdueSummary->overdue_count;

        $pharmacyOverdueAmount = round(
            (float) $pharmacyOverdueSummary->overdue_amount,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Supplier Payments — Selected Period
        |--------------------------------------------------------------------------
        */

        $pharmacySupplierPaymentSummary = DB::table(
            'pharmacy_supplier_payments'
        )
            ->whereBetween('payment_date', [$from, $to])
            ->selectRaw('
        COUNT(*) AS payment_count,
        COALESCE(SUM(amount), 0) AS payment_total
    ')
            ->first();

        $pharmacySupplierPaymentCount =
            (int) $pharmacySupplierPaymentSummary->payment_count;

        $pharmacySupplierPaymentTotal = round(
            (float) $pharmacySupplierPaymentSummary->payment_total,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Supplier Outstanding by Supplier
        |--------------------------------------------------------------------------
        */

        $pharmacyOutstandingBySupplier = DB::table(
            'pharmacy_supplier_payables as payables'
        )
            ->leftJoin(
                'pharmacy_suppliers as suppliers',
                'suppliers.id',
                '=',
                'payables.pharmacy_supplier_id'
            )
            ->where('payables.outstanding_amount', '>', 0)
            ->selectRaw("
        payables.pharmacy_supplier_id AS supplier_id,
        COALESCE(
            MAX(suppliers.name),
            'Unknown Supplier'
        ) AS supplier_name,

        COUNT(payables.id) AS invoice_count,

        COALESCE(
            SUM(payables.outstanding_amount),
            0
        ) AS outstanding_amount
    ")
            ->groupBy('payables.pharmacy_supplier_id')
            ->orderByDesc('outstanding_amount')
            ->limit(10)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Supplier Credit Balances
        |--------------------------------------------------------------------------
        |
        | Only active credits are included.
        | Available credit is not subtracted again from payables.
        |
        */

        $pharmacySupplierCreditSummary = DB::table(
            'pharmacy_supplier_credits'
        )
            ->whereIn('status', ['available', 'partially_utilised'])
            ->selectRaw('
        COUNT(*) AS credit_count,

        COALESCE(SUM(original_credit_amount), 0)
            AS original_credit,

        COALESCE(SUM(utilised_amount), 0)
            AS utilized_credit,

        COALESCE(SUM(available_amount), 0)
            AS available_credit
    ')
            ->first();

        $pharmacySupplierCreditCount =
            (int) $pharmacySupplierCreditSummary->credit_count;

        $pharmacyAvailableSupplierCredit = round(
            (float) $pharmacySupplierCreditSummary->available_credit,
            2
        );

        /*
        |--------------------------------------------------------------------------
        | Hospital Expenses MIS
        |--------------------------------------------------------------------------
        | Expense register figures are separated by workflow status.
        | Draft, submitted and approved records are not treated as
        | recognized GL expenditure.
        |--------------------------------------------------------------------------
        */

        $expenseBase = HospitalExpense::query()
            ->whereBetween('accounting_date', [$from, $to]);

        $expenseStatusSummary = (clone $expenseBase)
            ->selectRaw('status, COUNT(*) as expense_count, SUM(total_amount) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        $expenseClassificationSummary = (clone $expenseBase)
            ->selectRaw(
                'expense_classification, status, COUNT(*) as expense_count, SUM(total_amount) as total'
            )
            ->groupBy('expense_classification', 'status')
            ->orderBy('expense_classification')
            ->get();

        $expenseDepartmentSummary = \DB::table('hospital_expense_allocations as a')
            ->join('hospital_expenses as e', 'e.id', '=', 'a.hospital_expense_id')
            ->join('departments as d', 'd.id', '=', 'a.department_id')
            ->whereBetween('e.accounting_date', [$from, $to])
            ->selectRaw(
                'd.id, d.name, e.status, SUM(a.allocated_amount) as total'
            )
            ->groupBy('d.id', 'd.name', 'e.status')
            ->orderBy('d.name')
            ->get();

        $expenseHeadSummary = \DB::table('hospital_expenses as e')
            ->join('finance_heads as h', 'h.id', '=', 'e.finance_head_id')
            ->whereBetween('e.accounting_date', [$from, $to])
            ->selectRaw(
                'h.id, h.name, e.status, SUM(e.total_amount) as total'
            )
            ->groupBy('h.id', 'h.name', 'e.status')
            ->orderBy('h.name')
            ->get();

        $expenseTotalDraft = (float) (clone $expenseBase)
            ->where('status', 'draft')
            ->sum('total_amount');

        $expenseTotalPosted = (float) (clone $expenseBase)
            ->where('status', 'posted')
            ->sum('total_amount');

        return view('mis.dashboard', [
            'from' => $from,
            'to' => $to,
            'opdVisits' => $opdVisits,
            'opdPatients' => $opdPatients,
            'ipdAdmissions' => $ipdAdmissions,
            'ipdDischarges' => $ipdDischarges,
            'emergencyVisits' => $emergencyVisits,
            'averageLengthOfStay' => $averageLengthOfStay,
            'departmentOpd' => $departmentOpd,
            'departmentIpd' => $departmentIpd,
            'totalActiveBeds' => $totalActiveBeds,
            'operationalBeds' => $operationalBeds,
            'occupiedBeds' => $occupiedBeds,
            'availableBeds' => $availableBeds,
            'cleaningBeds' => $cleaningBeds,
            'reservedBeds' => $reservedBeds,
            'maintenanceBeds' => $maintenanceBeds,
            'currentOccupancyPercentage' => $currentOccupancyPercentage,
            'wardOccupancy' => $wardOccupancy,
            'occupiedBedDays' => $occupiedBedDays,
            'averageDailyCensus' => $averageDailyCensus,
            'elapsedDays' => $elapsedDays,
            'historicalPeriodComplete' => $historicalPeriodComplete,
            'diagnosticTotal' => $diagnosticTotal,
            'diagnosticCompleted' => $diagnosticCompleted,
            'diagnosticInProcess' => $diagnosticInProcess,
            'diagnosticOrdered' => $diagnosticOrdered,
            'diagnosticOtherStatuses' => $diagnosticOtherStatuses,
            'diagnosticCompletionRate' => $diagnosticCompletionRate,
            'diagnosticCategories' => $diagnosticCategories,
            'diagnosticOpd' => $diagnosticOpd,
            'diagnosticIpd' => $diagnosticIpd,
            'diagnosticUnlinked' => $diagnosticUnlinked,
            'diagnosticServices' => $diagnosticServices,
            'sampleRequired' => $sampleRequired,
            'sampleDocumented' => $sampleDocumented,
            'sampleUndocumented' => $sampleUndocumented,
            'sampleCollected' => $sampleCollected,
            'sampleRejected' => $sampleRejected,
            'sampleCoverageRate' => $sampleCoverageRate,
            'sampleAverageCollectionTat' => $sampleAverageCollectionTat,
            'sampleTatRecords' => $sampleTatRecords,
            'specimenPerformance' => $specimenPerformance,
            'labResultCount' => $labResultCount,
            'labFinalizedCount' => $labFinalizedCount,
            'labEnteredCount' => $labEnteredCount,
            'labVerifiedTimestampCount' => $labVerifiedTimestampCount,
            'labTatMeasuredCount' => $labTatMeasuredCount,
            'labTatMissingCount' => $labTatMissingCount,
            'labAverageTatMinutes' => $labAverageTatMinutes,
            'labMinimumTatMinutes' => $labMinimumTatMinutes,
            'labMaximumTatMinutes' => $labMaximumTatMinutes,
            'labTatCoverageRate' => $labTatCoverageRate,
            'financeRecognizedRevenue' => $financeRecognizedRevenue,
            'financeRecordedExpenditure' => $financeRecordedExpenditure,
            'financeProvisionalSurplus' => $financeProvisionalSurplus,
            'financeDepartmentRevenue' => $financeDepartmentRevenue,
            'financeAssignedRevenue' => $financeAssignedRevenue,
            'financeUnassignedRevenue' => $financeUnassignedRevenue,
            'financeAttributionRate' => $financeAttributionRate,
            'financePostedJournals' => $financePostedJournals,
            'financeTotalDebits' => $financeTotalDebits,
            'financeTotalCredits' => $financeTotalCredits,
            'financeJournalDifference' => $financeJournalDifference,
            'financeIncomeAccountCount' => $financeIncomeAccountCount,
            'financeExpenseAccountCount' => $financeExpenseAccountCount,
            'financeIncomeLines' => $financeIncomeLines,
            'financeAssignedIncomeLines' => $financeAssignedIncomeLines,
            'financeReceiptCount' => $financeReceiptCount,
            'financeReceiptTotal' => $financeReceiptTotal,
            'financeReceiptAccounts' => $financeReceiptAccounts,
            'financeReceiptModes' => $financeReceiptModes,
            'financeReceiptOrigins' => $financeReceiptOrigins,
            'financeUnspecifiedReceiptTotal' => $financeUnspecifiedReceiptTotal,
            'financeLinkedReceiptCount' => $financeLinkedReceiptCount,
            'financeLinkedReceiptAmount' => $financeLinkedReceiptAmount,
            'financeUnlinkedReceiptCount' => $financeUnlinkedReceiptCount,
            'financeUnlinkedReceiptAmount' => $financeUnlinkedReceiptAmount,
            'financeReceiptLinkageRate' => $financeReceiptLinkageRate,
            'receivablesAsOfDate' => $receivablesAsOfDate,
            'ipdFinalizedAccountCount' => $ipdFinalizedAccountCount,
            'ipdFinalizedBillTotal' => $ipdFinalizedBillTotal,
            'ipdStoredOutstanding' => $ipdStoredOutstanding,
            'ipdSelfPayOutstanding' => $ipdSelfPayOutstanding,
            'ipdMhisAccountOutstanding' => $ipdMhisAccountOutstanding,
            'glPatientReceivables' => $glPatientReceivables,
            'glPatientAdvanceLiability' => $glPatientAdvanceLiability,
            'patientReceivableDifference' => $patientReceivableDifference,
            'patientAdvanceReceiptCount' => $patientAdvanceReceiptCount,
            'patientAdvancesReceived' => $patientAdvancesReceived,
            'patientAdvanceApplicationCount' => $patientAdvanceApplicationCount,
            'patientAdvancesApplied' => $patientAdvancesApplied,
            'mhisClaimCount' => $mhisClaimCount,
            'mhisTotalClaimed' => $mhisTotalClaimed,
            'mhisTotalExpected' => $mhisTotalExpected,
            'mhisTotalApproved' => $mhisTotalApproved,
            'mhisReceiptCount' => $mhisReceiptCount,
            'mhisAmountReceived' => $mhisAmountReceived,
            'mhisClaimsByStatus' => $mhisClaimsByStatus,
            'mhisApprovedOutstanding' => $mhisApprovedOutstanding,
            'mhisPendingApprovalExpected' => $mhisPendingApprovalExpected,
            'mhisPendingReceivableDetails' => $mhisPendingReceivableDetails,
            'mhisPendingReceivableExposure' => $mhisPendingReceivableExposure,
            'patientUnexplainedReconciliationDifference' => $patientUnexplainedReconciliationDifference,
            'charityRequestCount' => $charityRequestCount,
            'charityRequestedAmount' => $charityRequestedAmount,
            'charityApprovedAmount' => $charityApprovedAmount,
            'charityAppliedAmount' => $charityAppliedAmount,
            'charityPendingCount' => $charityPendingCount,
            'charityApprovedNotAppliedCount' => $charityApprovedNotAppliedCount,
            'charityApprovedNotAppliedAmount' => $charityApprovedNotAppliedAmount,
            'charityBySource' => $charityBySource,
            'charityPatientsAssisted' => $charityPatientsAssisted,

            'staffBenefitUtilizationCount' => $staffBenefitUtilizationCount,
            'staffBenefitUtilizedAmount' => $staffBenefitUtilizedAmount,
            'staffBenefitReversalCount' => $staffBenefitReversalCount,
            'staffBenefitReversedAmount' => $staffBenefitReversedAmount,
            'staffBenefitNetUtilization' => $staffBenefitNetUtilization,
            'staffBenefitByBeneficiary' => $staffBenefitByBeneficiary,

            'staffEntitlementAccountCount' => $staffEntitlementAccountCount,
            'staffEmployeeEntitlement' => $staffEmployeeEntitlement,
            'staffDependentEntitlement' => $staffDependentEntitlement,
            'staffEmployeeCumulativeUsed' => $staffEmployeeCumulativeUsed,
            'staffDependentCumulativeUsed' => $staffDependentCumulativeUsed,
            'staffEmployeeRemaining' => $staffEmployeeRemaining,
            'staffDependentRemaining' => $staffDependentRemaining,
            'pharmacySaleCount' => $pharmacySaleCount,
            'pharmacyGrossSales' => $pharmacyGrossSales,
            'pharmacyDiscounts' => $pharmacyDiscounts,
            'pharmacyTotalSales' => $pharmacyTotalSales,
            'pharmacyCounterCollections' => $pharmacyCounterCollections,
            'pharmacyIpdCharges' => $pharmacyIpdCharges,
            'pharmacyInitialCreditBalance' => $pharmacyInitialCreditBalance,
            'pharmacySalesByMode' => $pharmacySalesByMode,
            'pharmacySalesBySource' => $pharmacySalesBySource,
            'pharmacySalesByStatus' => $pharmacySalesByStatus,
            'pharmacyReturnCount' => $pharmacyReturnCount,
            'pharmacyGrossReturns' => $pharmacyGrossReturns,
            'pharmacyReturnAdjustments' => $pharmacyReturnAdjustments,
            'pharmacyCashRefunds' => $pharmacyCashRefunds,
            'pharmacyDigitalRefunds' => $pharmacyDigitalRefunds,
            'pharmacyIpdReturnAdjustments' => $pharmacyIpdReturnAdjustments,
            'pharmacyNetSales' => $pharmacyNetSales,
            'pharmacyReturnsByMode' => $pharmacyReturnsByMode,
            'pharmacyReturnedUnits' => $pharmacyReturnedUnits,
            'pharmacyGrnCount' => $pharmacyGrnCount,
            'pharmacyPurchaseTotal' => $pharmacyPurchaseTotal,
            'pharmacyPurchaseDiscounts' => $pharmacyPurchaseDiscounts,
            'pharmacyPurchaseReturnCount' => $pharmacyPurchaseReturnCount,
            'pharmacyPurchaseReturnTotal' => $pharmacyPurchaseReturnTotal,
            'pharmacyNetProcurement' => $pharmacyNetProcurement,
            'pharmacyReceivedUnits' => $pharmacyReceivedUnits,
            'pharmacyBonusUnits' => $pharmacyBonusUnits,
            'pharmacySupplierPurchases' => $pharmacySupplierPurchases,
            'pharmacyStockMedicines' => $pharmacyStockMedicines,
            'pharmacyStockBatches' => $pharmacyStockBatches,
            'pharmacyStockUnits' => $pharmacyStockUnits,
            'pharmacyInventoryCost' => $pharmacyInventoryCost,
            'pharmacyInventoryRetailValue' => $pharmacyInventoryRetailValue,
            'pharmacyStockByLocation' => $pharmacyStockByLocation,
            'pharmacyLowStockCount' => $pharmacyLowStockCount,
            'pharmacyZeroStockCount' => $pharmacyZeroStockCount,
            'pharmacyExpiredUnits' => $pharmacyExpiredUnits,
            'pharmacyExpiring30Units' => $pharmacyExpiring30Units,
            'pharmacyExpiring60Units' => $pharmacyExpiring60Units,
            'pharmacyExpiring90Units' => $pharmacyExpiring90Units,
            'pharmacyExpiredStockValue' => $pharmacyExpiredStockValue,
            'pharmacyReconciliationBatchCount' => $pharmacyReconciliationBatchCount,
            'pharmacyReconciliationMismatchCount' => $pharmacyReconciliationMismatchCount,
            'pharmacyReconciliationDifferenceUnits' => $pharmacyReconciliationDifferenceUnits,
            'pharmacyStockMismatchDetails' => $pharmacyStockMismatchDetails,
            'pharmacyStockReconciled' => $pharmacyStockReconciled,
            'pharmacyTransferCount' => $pharmacyTransferCount,
            'pharmacyTransferredUnits' => $pharmacyTransferredUnits,
            'pharmacyTransfersByDestination' => $pharmacyTransfersByDestination,
            'pharmacyDisposalCount' => $pharmacyDisposalCount,
            'pharmacyDisposedUnits' => $pharmacyDisposedUnits,
            'pharmacyDisposalValue' => $pharmacyDisposalValue,
            'pharmacyDisposalsByReason' => $pharmacyDisposalsByReason,
            'pharmacyPayableCount' => $pharmacyPayableCount,
            'pharmacySupplierOutstanding' => $pharmacySupplierOutstanding,
            'pharmacyUnpaidPayables' => $pharmacyUnpaidPayables,
            'pharmacyPartiallyPaidPayables' => $pharmacyPartiallyPaidPayables,
            'pharmacyOverduePayables' => $pharmacyOverduePayables,
            'pharmacyOverdueAmount' => $pharmacyOverdueAmount,
            'pharmacySupplierPaymentCount' => $pharmacySupplierPaymentCount,
            'pharmacySupplierPaymentTotal' => $pharmacySupplierPaymentTotal,
            'pharmacyOutstandingBySupplier' => $pharmacyOutstandingBySupplier,
            'pharmacySupplierCreditCount' => $pharmacySupplierCreditCount,
            'pharmacyAvailableSupplierCredit' => $pharmacyAvailableSupplierCredit,
            'expenseStatusSummary' => $expenseStatusSummary,
            'expenseClassificationSummary' => $expenseClassificationSummary,
            'expenseDepartmentSummary' => $expenseDepartmentSummary,
            'expenseHeadSummary' => $expenseHeadSummary,
            'expenseTotalDraft' => $expenseTotalDraft,
            'expenseTotalPosted' => $expenseTotalPosted,

        ]);
    }
}
