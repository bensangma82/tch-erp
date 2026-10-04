<?php

namespace App\Http\Controllers;

use App\Models\DiagnosticSample;
use App\Models\ServiceOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiagnosticSampleController extends Controller
{
    /**
     * Show the sample collection form.
     */
    public function create(ServiceOrderItem $serviceOrderItem)
    {
        $serviceOrderItem->load([
            'serviceOrder.patient',
            'serviceOrder.encounter.department',
            'serviceOrder.encounter.doctor',
            'diagnosticSample.collectedBy',
            'diagnosticSample.rejectedBy',
        ]);

        if ($serviceOrderItem->category !== 'laboratory') {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'sample' => 'This investigation is not a laboratory test.',
                ]);
        }

        if (
            ! in_array(
                $serviceOrderItem->serviceOrder?->status,
                ['paid', 'authorized'],
                true
            )
        ) {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'sample' => 'This investigation has not been released for laboratory processing.',
                ]);
        }

        if (! $serviceOrderItem->requires_sample) {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'sample' => 'This investigation does not require sample collection.',
                ]);
        }

        $sample = $serviceOrderItem->diagnosticSample;

        return view(
            'diagnostics.sample-collection',
            compact(
                'serviceOrderItem',
                'sample'
            )
        );
    }


    /**
     * Record sample collection.
     */
    public function store(
        Request $request,
        ServiceOrderItem $serviceOrderItem
    ) {
        $serviceOrderItem->load([
            'serviceOrder',
            'diagnosticSample',
        ]);

        if ($serviceOrderItem->category !== 'laboratory') {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'sample' => 'This investigation is not a laboratory test.',
                ]);
        }

        if (
            ! in_array(
                $serviceOrderItem->serviceOrder?->status,
                ['paid', 'authorized'],
                true
            )
        ) {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'sample' => 'This investigation has not been released for laboratory processing.',
                ]);
        }

        if (! $serviceOrderItem->requires_sample) {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'sample' => 'This investigation does not require sample collection.',
                ]);
        }

        if (
            $serviceOrderItem->diagnosticSample
            && $serviceOrderItem->diagnosticSample->status === 'collected'
        ) {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'sample' => 'A sample has already been collected for this investigation.',
                ]);
        }

        $validated = $request->validate([
            'specimen_type' => [
                'required',
                'string',
                'max:100',
            ],

            'collected_at' => [
                'required',
                'date',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        DB::transaction(function () use (
            $validated,
            $serviceOrderItem
        ) {
            $serviceOrderItem->refresh();

            $sample = DiagnosticSample::query()
                ->where(
                    'service_order_item_id',
                    $serviceOrderItem->id
                )
                ->lockForUpdate()
                ->first();

            if (
                $sample
                && $sample->status === 'collected'
            ) {
                return;
            }

            if (! $sample) {
                $sample = new DiagnosticSample();

                $sample->service_order_item_id =
                    $serviceOrderItem->id;

                $sample->sample_no =
                    $this->generateSampleNumber();
            }

            $sample->specimen_type =
                $validated['specimen_type'];

            $sample->collected_at =
                $validated['collected_at'];

            $sample->collected_by =
                auth()->id();

            $sample->status =
                'collected';

            $sample->rejected_at =
                null;

            $sample->rejected_by =
                null;

            $sample->rejection_reason =
                null;

            $sample->remarks =
                $validated['remarks']
                ?? null;

            $sample->save();
        });

        return redirect()
            ->route('laboratory.index')
            ->with(
                'success',
                'Sample collected successfully.'
            );
    }

/**
 * Record sample collection for multiple laboratory investigations.
 *
 * The specimen type is taken from the ServiceOrderItem snapshot.
 * This allows one-click collection by specimen group while preserving
 * one DiagnosticSample record per investigation.
 */
public function bulkStore(
    Request $request,
    int $serviceOrderId
) {
    $validated = $request->validate([
        'item_ids' => [
            'required',
            'array',
            'min:1',
        ],

        'item_ids.*' => [
            'required',
            'integer',
            'distinct',
        ],
    ]);

    $requestedIds = collect(
        $validated['item_ids']
    )
        ->map(fn ($id) => (int) $id)
        ->unique()
        ->values();

    $items = ServiceOrderItem::with([
        'serviceOrder',
        'diagnosticSample',
    ])
        ->where(
            'service_order_id',
            $serviceOrderId
        )
        ->whereIn(
            'id',
            $requestedIds
        )
        ->get();

    /*
    |--------------------------------------------------------------------------
    | Ensure every requested investigation belongs to this order
    |--------------------------------------------------------------------------
    */

    if ($items->count() !== $requestedIds->count()) {
        return redirect()
            ->route('laboratory.index')
            ->withErrors([
                'sample' =>
                    'One or more selected investigations do not belong to this laboratory order.',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Validate investigations before collection
    |--------------------------------------------------------------------------
    */

    foreach ($items as $item) {
        if ($item->category !== 'laboratory') {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'sample' =>
                        'Only laboratory investigations can be collected here.',
                ]);
        }

        if (
            ! in_array(
                $item->serviceOrder?->status,
                ['paid', 'authorized'],
                true
            )
        ) {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'sample' =>
                        'This laboratory order has not been released for processing.',
                ]);
        }

        if (! $item->requires_sample) {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'sample' =>
                        $item->service_name .
                        ' does not require sample collection.',
                ]);
        }

        if ($item->status !== 'ordered') {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'sample' =>
                        $item->service_name .
                        ' is no longer awaiting sample collection.',
                ]);
        }

        if (blank($item->specimen_type)) {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'sample' =>
                        'Specimen type has not been configured for ' .
                        $item->service_name .
                        '. Please update the Service Master first.',
                ]);
        }
    }

    $collectedAt = now();

    DB::transaction(function () use (
    $items,
    $collectedAt
) {
    /*
    |--------------------------------------------------------------------------
    | Group by physical specimen
    |--------------------------------------------------------------------------
    |
    | All investigations using the same specimen type in this collection
    | event share one accession / sample number.
    |
    */

    $specimenGroups =
        $items->groupBy(
            fn ($item) => $item->specimen_type
        );

    foreach ($specimenGroups as $specimenType => $groupItems) {
        /*
         * One accession number per physical specimen group.
         */
        $sharedSampleNumber =
            $this->generateSampleNumber();

        foreach ($groupItems as $item) {
            $lockedItem =
                ServiceOrderItem::query()
                    ->whereKey($item->id)
                    ->lockForUpdate()
                    ->firstOrFail();

            $sample =
                DiagnosticSample::query()
                    ->where(
                        'service_order_item_id',
                        $lockedItem->id
                    )
                    ->lockForUpdate()
                    ->first();

            /*
             * Skip if another request already collected it.
             */
            if (
                $sample
                && $sample->status === 'collected'
            ) {
                continue;
            }

            if (! $sample) {
                $sample =
                    new DiagnosticSample();

                $sample->service_order_item_id =
                    $lockedItem->id;
            }

            /*
             * Every investigation from the same physical specimen
             * receives the same accession number.
             */
            $sample->sample_no =
                $sharedSampleNumber;

            $sample->specimen_type =
                $lockedItem->specimen_type;

            $sample->collected_at =
                $collectedAt;

            $sample->collected_by =
                auth()->id();

            $sample->status =
                'collected';

            $sample->rejected_at =
                null;

            $sample->rejected_by =
                null;

            $sample->rejection_reason =
                null;

            $sample->remarks =
                null;

            $sample->save();
        }
    }
});

    $count =
        $items->count();

    return redirect()
    ->to(
        route(
            'laboratory.index',
            ['open_order' => $serviceOrderId]
        )
        . '#lab-order-' . $serviceOrderId
    )
    ->with(
        'success',
        'Sample collection recorded for ' .
        $count .
        ($count === 1
            ? ' investigation.'
            : ' investigations.')
    );
}

/**
 * Print one laboratory specimen label for a shared accession number.
 */
public function printLabel(string $sampleNo)
{
    $samples = DiagnosticSample::with([
        'serviceOrderItem.serviceOrder.patient',
        'serviceOrderItem.serviceOrder.encounter.department',
        'serviceOrderItem.serviceOrder.encounter.doctor',
        'serviceOrderItem.serviceOrder.admission.department',
        'serviceOrderItem.serviceOrder.admission.consultant',
        'collectedBy',
    ])
        ->where('sample_no', $sampleNo)
        ->where('status', 'collected')
        ->orderBy('service_order_item_id')
        ->get();

    if ($samples->isEmpty()) {
        return redirect()
            ->route('laboratory.index')
            ->withErrors([
                'sample' => 'Collected specimen not found.',
            ]);
    }

    $firstSample = $samples->first();

    $firstItem =
        $firstSample->serviceOrderItem;

    $order =
        $firstItem?->serviceOrder;

    /*
    |--------------------------------------------------------------------------
    | Safety check
    |--------------------------------------------------------------------------
    |
    | A shared accession number must belong to one laboratory order only.
    |
    */

    $orderIds =
        $samples
            ->pluck('serviceOrderItem.service_order_id')
            ->filter()
            ->unique();

    if ($orderIds->count() !== 1) {
        return redirect()
            ->route('laboratory.index')
            ->withErrors([
                'sample' =>
                    'This accession number is linked to more than one laboratory order.',
            ]);
    }

    $patient =
        $order?->patient;

    $encounter =
        $order?->encounter;

    $admission =
        $order?->admission;

    $orderSource =
        $admission
            ? 'IPD'
            : ($encounter ? 'OPD' : 'OTHER');

    $investigations =
        $samples
            ->map(
                fn ($sample) =>
                    $sample->serviceOrderItem?->service_name
            )
            ->filter()
            ->values();

    return view(
        'diagnostics.sample-label',
        compact(
            'samples',
            'firstSample',
            'order',
            'patient',
            'orderSource',
            'investigations'
        )
    );
}

/**
     * Show sample rejection form.
     */
    public function rejectForm(
        ServiceOrderItem $serviceOrderItem
    ) {
        $serviceOrderItem->load([
            'serviceOrder.patient',
            'serviceOrder.encounter.department',
            'serviceOrder.encounter.doctor',
            'diagnosticSample.collectedBy',
        ]);

        if ($serviceOrderItem->category !== 'laboratory') {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'sample' => 'This investigation is not a laboratory test.',
                ]);
        }

        $sample = $serviceOrderItem->diagnosticSample;

        if (! $sample) {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'sample' => 'No sample record exists for this investigation.',
                ]);
        }

        if ($sample->status !== 'collected') {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'sample' => 'Only a collected sample can be rejected.',
                ]);
        }

        return view(
            'diagnostics.sample-rejection',
            compact(
                'serviceOrderItem',
                'sample'
            )
        );
    }


    /**
     * Reject a collected sample.
     */
    public function reject(
        Request $request,
        ServiceOrderItem $serviceOrderItem
    ) {
        $serviceOrderItem->load([
            'diagnosticSample',
        ]);

        $sample =
            $serviceOrderItem->diagnosticSample;

        if (! $sample) {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'sample' => 'No sample record exists for this investigation.',
                ]);
        }

        if ($sample->status !== 'collected') {
            return redirect()
                ->route('laboratory.index')
                ->withErrors([
                    'sample' => 'Only a collected sample can be rejected.',
                ]);
        }

        $validated = $request->validate([
            'rejection_reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        DB::transaction(function () use (
            $validated,
            $sample
        ) {
            $lockedSample =
                DiagnosticSample::query()
                    ->whereKey($sample->id)
                    ->lockForUpdate()
                    ->firstOrFail();

            if ($lockedSample->status !== 'collected') {
                return;
            }

            $lockedSample->update([
                'status' =>
                    'rejected',

                'rejected_at' =>
                    now(),

                'rejected_by' =>
                    auth()->id(),

                'rejection_reason' =>
                    $validated['rejection_reason'],
            ]);
        });

        return redirect()
            ->route('laboratory.index')
            ->with(
                'success',
                'Sample rejected successfully.'
            );
    }


    /**
     * Generate a unique laboratory sample number.
     *
     * Format:
     * LAB-YYYYMMDD-000001
     */
    private function generateSampleNumber(): string
    {
        $date =
            now()->format('Ymd');

        $prefix =
            'LAB-' . $date . '-';

        $lastSample =
            DiagnosticSample::query()
                ->where(
                    'sample_no',
                    'like',
                    $prefix . '%'
                )
                ->lockForUpdate()
                ->orderByDesc('sample_no')
                ->first();

        $sequence = 1;

        if ($lastSample) {
            $lastSequence =
                (int) substr(
                    $lastSample->sample_no,
                    -6
                );

            $sequence =
                $lastSequence + 1;
        }

        return
            $prefix
            .
            str_pad(
                (string) $sequence,
                6,
                '0',
                STR_PAD_LEFT
            );
    }
}