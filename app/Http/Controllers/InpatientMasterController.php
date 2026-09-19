<?php

namespace App\Http\Controllers;

use App\Models\Bed;
use App\Models\Room;
use App\Models\Ward;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InpatientMasterController extends Controller
{
    /**
     * Display Ward, Room/Cabin and Bed masters.
     */
    public function index()
    {
        $wards = Ward::query()
            ->with([
                'rooms' => fn ($query) => $query->orderBy('name'),
                'beds' => fn ($query) => $query->orderBy('bed_number'),
            ])
            ->orderBy('name')
            ->get();

        $rooms = Room::query()
            ->with(['ward', 'beds'])
            ->orderBy('ward_id')
            ->orderBy('name')
            ->get();

        $beds = Bed::query()
            ->with(['ward', 'room', 'activeAllocation'])
            ->orderBy('ward_id')
            ->orderBy('bed_number')
            ->get();

        return view(
            'masters.inpatient.index',
            compact('wards', 'rooms', 'beds')
        );
    }

    /**
     * Create a ward.
     */
    public function storeWard(Request $request)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'unique:wards,code',
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'ward_type' => [
                'required',
                'string',
                'max:50',
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $validated['is_active'] = true;

        Ward::create($validated);

        return redirect()
            ->route('inpatient-master.index')
            ->with('success', 'Ward added successfully.');
    }

    /**
     * Update a ward.
     */
    public function updateWard(Request $request, Ward $ward)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('wards', 'code')->ignore($ward->id),
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'ward_type' => [
                'required',
                'string',
                'max:50',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        /*
         * Do not deactivate a ward while it contains an occupied bed.
         */
        if (!$validated['is_active']) {
            $hasOccupiedBed = $ward->beds()
                ->whereHas('activeAllocation')
                ->exists();

            if ($hasOccupiedBed) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'is_active' => 'This ward contains an occupied bed and cannot be made inactive.',
                    ]);
            }
        }

        $ward->update($validated);

        return redirect()
            ->route('inpatient-master.index')
            ->with('success', 'Ward updated successfully.');
    }

    /**
     * Create a room / cabin.
     */
    public function storeRoom(Request $request)
    {
        $validated = $request->validate([
            'ward_id' => [
                'required',
                'exists:wards,id',
            ],
            'code' => [
                'required',
                'string',
                'max:50',
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'room_type' => [
                'required',
                'string',
                'max:50',
            ],
            'floor' => [
                'nullable',
                'string',
                'max:50',
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $duplicate = Room::query()
            ->where('ward_id', $validated['ward_id'])
            ->where('code', $validated['code'])
            ->exists();

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors([
                    'code' => 'This room/cabin code already exists in the selected ward.',
                ]);
        }

        $validated['is_active'] = true;

        Room::create($validated);

        return redirect()
            ->route('inpatient-master.index')
            ->with('success', 'Room / cabin added successfully.');
    }

    /**
     * Update a room / cabin.
     */
    public function updateRoom(Request $request, Room $room)
    {
        $validated = $request->validate([
            'ward_id' => [
                'required',
                'exists:wards,id',
            ],
            'code' => [
                'required',
                'string',
                'max:50',
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'room_type' => [
                'required',
                'string',
                'max:50',
            ],
            'floor' => [
                'nullable',
                'string',
                'max:50',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $duplicate = Room::query()
            ->where('ward_id', $validated['ward_id'])
            ->where('code', $validated['code'])
            ->where('id', '!=', $room->id)
            ->exists();

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors([
                    'code' => 'This room/cabin code already exists in the selected ward.',
                ]);
        }

        /*
         * Do not deactivate a room/cabin while it contains an occupied bed.
         */
        if (!$validated['is_active']) {
            $hasOccupiedBed = $room->beds()
                ->whereHas('activeAllocation')
                ->exists();

            if ($hasOccupiedBed) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'is_active' => 'This room/cabin contains an occupied bed and cannot be made inactive.',
                    ]);
            }
        }

        /*
         * Do not allow a room containing actively allocated beds
         * to be moved to another ward.
         */
        if ((int) $room->ward_id !== (int) $validated['ward_id']) {
            $hasOccupiedBed = $room->beds()
                ->whereHas('activeAllocation')
                ->exists();

            if ($hasOccupiedBed) {
                return back()->withErrors([
                    'ward_id' => 'This room/cabin contains an occupied bed and cannot be moved to another ward.',
                ]);
            }

            /*
             * Keep the ward_id of beds consistent with their room.
             */
            $room->beds()->update([
                'ward_id' => $validated['ward_id'],
            ]);
        }

        $room->update($validated);

        return redirect()
            ->route('inpatient-master.index')
            ->with('success', 'Room / cabin updated successfully.');
    }

    /**
     * Create a bed.
     */
    public function storeBed(Request $request)
    {
        $validated = $request->validate([
            'ward_id' => [
                'required',
                'exists:wards,id',
            ],
            'room_id' => [
                'nullable',
                'exists:rooms,id',
            ],
            'bed_number' => [
                'required',
                'string',
                'max:50',
            ],
            'bed_type' => [
                'required',
                'string',
                'max:50',
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        if (!empty($validated['room_id'])) {
            $room = Room::findOrFail($validated['room_id']);

            if ((int) $room->ward_id !== (int) $validated['ward_id']) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'room_id' => 'The selected room/cabin does not belong to the selected ward.',
                    ]);
            }
        }

        $duplicate = Bed::query()
            ->where('ward_id', $validated['ward_id'])
            ->where('bed_number', $validated['bed_number'])
            ->exists();

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors([
                    'bed_number' => 'This bed number already exists in the selected ward.',
                ]);
        }

        $validated['status'] = 'available';
        $validated['is_active'] = true;

        Bed::create($validated);

        return redirect()
            ->route('inpatient-master.index')
            ->with('success', 'Bed added successfully.');
    }

    /**
     * Update a bed.
     */
    public function updateBed(Request $request, Bed $bed)
    {
        $validated = $request->validate([
            'ward_id' => [
                'required',
                'exists:wards,id',
            ],
            'room_id' => [
                'nullable',
                'exists:rooms,id',
            ],
            'bed_number' => [
                'required',
                'string',
                'max:50',
            ],
            'bed_type' => [
                'required',
                'string',
                'max:50',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        if (!empty($validated['room_id'])) {
            $room = Room::findOrFail($validated['room_id']);

            if ((int) $room->ward_id !== (int) $validated['ward_id']) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'room_id' => 'The selected room/cabin does not belong to the selected ward.',
                    ]);
            }
        }

        $duplicate = Bed::query()
            ->where('ward_id', $validated['ward_id'])
            ->where('bed_number', $validated['bed_number'])
            ->where('id', '!=', $bed->id)
            ->exists();

        if ($duplicate) {
            return back()
                ->withInput()
                ->withErrors([
                    'bed_number' => 'This bed number already exists in the selected ward.',
                ]);
        }

        /*
         * Protect beds currently assigned to admitted patients.
         */
        if ($bed->activeAllocation) {
            $locationChanged =
                (int) $bed->ward_id !== (int) $validated['ward_id'] ||
                (int) ($bed->room_id ?? 0) !== (int) ($validated['room_id'] ?? 0);

            if ($locationChanged) {
                return back()->withErrors([
                    'bed_number' => 'An occupied bed cannot be moved to another ward or room/cabin.',
                ]);
            }

            if (!$validated['is_active']) {
                return back()->withErrors([
                    'is_active' => 'An occupied bed cannot be made inactive.',
                ]);
            }
        }

        $bed->update($validated);

        return redirect()
            ->route('inpatient-master.index')
            ->with('success', 'Bed updated successfully.');
    }
}