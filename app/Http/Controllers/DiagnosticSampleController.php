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