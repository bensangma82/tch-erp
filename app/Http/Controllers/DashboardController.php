<?php

namespace App\Http\Controllers;

use App\Models\Encounter;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;

class DashboardController extends Controller
{
    public function index()
    {
        $metrics = $this->getMetrics();

        return view('dashboard', $metrics);
    }


    public function metrics()
    {
        return response()->json(
            array_merge(
                $this->getMetrics(),
                [
                    'updated_at' => now()->format('h:i:s A'),
                ]
            )
        );
    }


    private function getMetrics(): array
    {
        $todayOpd = Encounter::whereDate(
            'encounter_date',
            today()
        )->count();


        $awaitingVitals = Encounter::whereDate(
            'encounter_date',
            today()
        )
            ->where('status', 'waiting')
            ->count();


        $waitingForDoctor = Encounter::whereDate(
            'encounter_date',
            today()
        )
            ->where('status', 'waiting_for_doctor')
            ->count();


        $pendingLaboratory = ServiceOrderItem::where(
            'category',
            'laboratory'
        )
            ->whereIn(
                'status',
                [
                    'ordered',
                    'in_process',
                ]
            )
            ->whereHas(
                'serviceOrder',
                function ($query) {
                    $query->whereIn(
                        'status',
                        [
                            'paid',
                            'authorized',
                        ]
                    );
                }
            )
            ->count();


        $pendingImaging = ServiceOrderItem::where(
            'category',
            'radiology'
        )
            ->whereIn(
                'status',
                [
                    'ordered',
                    'in_process',
                ]
            )
            ->whereHas(
                'serviceOrder',
                function ($query) {
                    $query->whereIn(
                        'status',
                        [
                            'paid',
                            'authorized',
                        ]
                    );
                }
            )
            ->count();


        $awaitingPayment = ServiceOrder::where(
            'status',
            'pending_payment'
        )->count();


        return [
            'todayOpd' => $todayOpd,
            'awaitingVitals' => $awaitingVitals,
            'waitingForDoctor' => $waitingForDoctor,
            'pendingLaboratory' => $pendingLaboratory,
            'pendingImaging' => $pendingImaging,
            'awaitingPayment' => $awaitingPayment,
        ];
    }
}