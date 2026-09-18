<?php

namespace App\Http\Controllers;

use App\Models\BedTariff;
use App\Models\Ward;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BedTariffController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Bed Tariff Master
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $wards = Ward::query()
            ->with([
                'beds' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->orderBy('bed_type')
                        ->orderBy('bed_number');
                },

                'bedTariffs' => function ($query) {
                    $query
                        ->orderByDesc('effective_from')
                        ->orderByDesc('id');
                },
            ])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'admin.bed-tariffs.index',
            compact('wards')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Store New Tariff
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'ward_id' => [
                'required',
                'integer',
                'exists:wards,id',
            ],

            'bed_type' => [
                'required',
                'string',
                'max:100',
            ],

            'rate_per_day' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999.99',
            ],

            'effective_from' => [
                'required',
                'date',
            ],

            'effective_to' => [
                'nullable',
                'date',
                'after_or_equal:effective_from',
            ],

            'remarks' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Load Active Ward
        |--------------------------------------------------------------------------
        */

        $ward = Ward::query()
            ->where('is_active', true)
            ->findOrFail(
                $validated['ward_id']
            );


        /*
        |--------------------------------------------------------------------------
        | Validate Bed Type
        |--------------------------------------------------------------------------
        |
        | The selected bed type must actually exist in the selected ward.
        |
        */

        $bedTypeExists = $ward->beds()
            ->where('is_active', true)
            ->where(
                'bed_type',
                $validated['bed_type']
            )
            ->exists();


        if (! $bedTypeExists) {
            throw ValidationException::withMessages([
                'bed_type' =>
                    'The selected bed type does not exist in this ward.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Normalize Dates
        |--------------------------------------------------------------------------
        */

        $effectiveFrom = Carbon::parse(
            $validated['effective_from']
        )->startOfDay();


        $requestedEffectiveTo = ! empty(
            $validated['effective_to']
        )
            ? Carbon::parse(
                $validated['effective_to']
            )->startOfDay()
            : null;


        DB::transaction(function () use (
            $validated,
            $ward,
            $effectiveFrom,
            $requestedEffectiveTo
        ) {

            /*
            |--------------------------------------------------------------------------
            | Prevent Duplicate Start Date
            |--------------------------------------------------------------------------
            |
            | A ward + bed type combination may not have two tariff records
            | starting on the same date.
            |
            */

            $duplicateExists = BedTariff::query()
                ->where(
                    'ward_id',
                    $ward->id
                )
                ->where(
                    'bed_type',
                    $validated['bed_type']
                )
                ->whereDate(
                    'effective_from',
                    $effectiveFrom->toDateString()
                )
                ->exists();


            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'effective_from' =>
                        'A tariff already exists for this ward and bed type with the same effective date.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Previous Enabled Tariff
            |--------------------------------------------------------------------------
            |
            | Find the most recent enabled tariff starting before the new one.
            |
            */

            $previousTariff = BedTariff::query()
                ->where(
                    'ward_id',
                    $ward->id
                )
                ->where(
                    'bed_type',
                    $validated['bed_type']
                )
                ->where(
                    'is_active',
                    true
                )
                ->whereDate(
                    'effective_from',
                    '<',
                    $effectiveFrom->toDateString()
                )
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();


            /*
            |--------------------------------------------------------------------------
            | Next Enabled Tariff
            |--------------------------------------------------------------------------
            |
            | This allows a tariff to be inserted between existing tariff
            | periods without creating overlapping date ranges.
            |
            */

            $nextTariff = BedTariff::query()
                ->where(
                    'ward_id',
                    $ward->id
                )
                ->where(
                    'bed_type',
                    $validated['bed_type']
                )
                ->where(
                    'is_active',
                    true
                )
                ->whereDate(
                    'effective_from',
                    '>',
                    $effectiveFrom->toDateString()
                )
                ->orderBy('effective_from')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();


            /*
            |--------------------------------------------------------------------------
            | Determine New Tariff End Date
            |--------------------------------------------------------------------------
            |
            | If the user does not provide an end date and another tariff
            | already begins later, automatically end this tariff one day
            | before the next tariff starts.
            |
            */

            $effectiveTo = $requestedEffectiveTo
                ?->copy();


            if (
                $effectiveTo === null
                && $nextTariff
            ) {
                $effectiveTo = $nextTariff
                    ->effective_from
                    ->copy()
                    ->subDay()
                    ->startOfDay();
            }


            /*
            |--------------------------------------------------------------------------
            | Prevent Overlap With Next Tariff
            |--------------------------------------------------------------------------
            */

            if (
                $nextTariff
                && $effectiveTo
                && $effectiveTo->gte(
                    $nextTariff->effective_from->startOfDay()
                )
            ) {
                throw ValidationException::withMessages([
                    'effective_to' =>
                        'This tariff overlaps with a later tariff beginning on ' .
                        $nextTariff->effective_from->format('d M Y') .
                        '.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Close Previous Tariff When Necessary
            |--------------------------------------------------------------------------
            |
            | Historical tariffs stay enabled. Their effective_to date
            | determines when they stop applying.
            |
            */

            if ($previousTariff) {

                $previousEndDate = $effectiveFrom
                    ->copy()
                    ->subDay()
                    ->startOfDay();


                $previousNeedsClosing =
                    $previousTariff->effective_to === null
                    || $previousTariff->effective_to
                        ->startOfDay()
                        ->gte($effectiveFrom);


                if ($previousNeedsClosing) {

                    $previousTariff->update([
                        'effective_to' =>
                            $previousEndDate->toDateString(),
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Additional Overlap Protection
            |--------------------------------------------------------------------------
            |
            | Confirm that the proposed date range does not overlap another
            | enabled tariff record.
            |
            */

            $overlapExists = BedTariff::query()
                ->where(
                    'ward_id',
                    $ward->id
                )
                ->where(
                    'bed_type',
                    $validated['bed_type']
                )
                ->where(
                    'is_active',
                    true
                )
                ->whereDate(
                    'effective_from',
                    '<=',
                    $effectiveTo
                        ? $effectiveTo->toDateString()
                        : '9999-12-31'
                )
                ->where(function ($query) use ($effectiveFrom) {

                    $query
                        ->whereNull('effective_to')
                        ->orWhereDate(
                            'effective_to',
                            '>=',
                            $effectiveFrom->toDateString()
                        );
                })
                ->exists();


            if ($overlapExists) {
                throw ValidationException::withMessages([
                    'effective_from' =>
                        'The proposed tariff period overlaps with an existing tariff.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Create Tariff
            |--------------------------------------------------------------------------
            |
            | is_active indicates whether this tariff record is enabled.
            |
            | effective_from / effective_to determine whether it applies to
            | a particular billing date.
            |
            */

            BedTariff::create([
                'ward_id' =>
                    $ward->id,

                'bed_type' =>
                    $validated['bed_type'],

                'rate_per_day' =>
                    round(
                        (float) $validated['rate_per_day'],
                        2
                    ),

                'effective_from' =>
                    $effectiveFrom->toDateString(),

                'effective_to' =>
                    $effectiveTo?->toDateString(),

                'is_active' =>
                    true,

                'remarks' =>
                    $validated['remarks']
                    ?? null,

                'created_by' =>
                    auth()->id(),
            ]);
        });


        return redirect()
            ->route('admin.bed-tariffs.index')
            ->with(
                'success',
                'Bed tariff saved successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Deactivate Tariff
    |--------------------------------------------------------------------------
    |
    | Deactivation means this tariff record should no longer be used by the
    | billing engine at all.
    |
    | Normal historical tariff changes should instead be handled by creating
    | a newer tariff, which automatically closes the older tariff's date range.
    |
    */

    public function deactivate(
        BedTariff $bedTariff
    ) {
        if (! $bedTariff->is_active) {

            return redirect()
                ->route('admin.bed-tariffs.index')
                ->with(
                    'success',
                    'Bed tariff is already inactive.'
                );
        }


        $bedTariff->update([
            'is_active' => false,
        ]);


        return redirect()
            ->route('admin.bed-tariffs.index')
            ->with(
                'success',
                'Bed tariff deactivated successfully.'
            );
    }
}