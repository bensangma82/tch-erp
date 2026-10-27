<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DischargeRecordsAccessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_discharge_records(): void
    {
        $this->get(route('medical-records.discharge-history.index'))
            ->assertRedirect(route('login'));
    }

    public function test_reception_user_without_permission_cannot_access_discharge_records(): void
    {
        $user = User::factory()->create([
            'role' => 'reception',
        ]);

        $this->actingAs($user);

        $this->get(route('medical-records.discharge-history.index'))
            ->assertForbidden();

        $patient = \App\Models\Patient::create([
            'uhid' => 'RBAC-TEST-' . uniqid(),
            'first_name' => 'Test',
            'last_name' => 'Patient',
            'sex' => 'Male',
        ]);

        $this->get(route(
            'medical-records.discharge-history.patient',
            $patient->id
        ))->assertForbidden();
    }

    public function test_clinical_record_does_not_show_other_admission_investigations(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $patientA = \App\Models\Patient::create([
            'uhid' => 'TEST-ISOLATION-A',
            'first_name' => 'Patient',
            'last_name' => 'Alpha',
            'sex' => 'Male',
        ]);

        $patientB = \App\Models\Patient::create([
            'uhid' => 'TEST-ISOLATION-B',
            'first_name' => 'Patient',
            'last_name' => 'Beta',
            'sex' => 'Female',
        ]);

        $admissionA = \App\Models\Admission::create([
            'admission_no' => 'TEST-IPD-A',
            'patient_id' => $patientA->id,
            'admitted_at' => now(),
            'status' => 'admitted',
        ]);

        $admissionB = \App\Models\Admission::create([
            'admission_no' => 'TEST-IPD-B',
            'patient_id' => $patientB->id,
            'admitted_at' => now(),
            'status' => 'admitted',
        ]);

        $service = \App\Models\Service::create([
            'code' => 'TEST-ISOLATION-SERVICE',
            'name' => 'Isolation Test Investigation',
            'category' => 'laboratory',
            'price' => 0,
        ]);

        foreach ([
            [$admissionA, $patientA, 'ALPHA ONLY RESULT'],
            [$admissionB, $patientB, 'BETA ONLY RESULT'],
        ] as $index => [$admission, $patient, $value]) {

            $order = \App\Models\ServiceOrder::create([
                'order_no' => 'TEST-ORDER-' . $index,
                'patient_id' => $patient->id,
                'admission_id' => $admission->id,
                'ordered_at' => now(),
                'status' => 'authorized',
            ]);

            $item = $order->items()->create([
                'service_id' => $service->id,
                'service_code' => 'TEST-' . $index,
                'service_name' => $value,
                'category' => 'laboratory',
                'unit_price' => 0,
                'amount' => 0,
                'status' => 'completed',
            ]);

            $item->diagnosticResult()->create([
                'status' => 'final',
                'entered_by' => $user->id,
                'entered_at' => now(),
                'result_text' => $value,
            ]);
        }

        $this->actingAs($user)
            ->get(route(
                'medical-records.discharge-history.clinical-record',
                $admissionA
            ))
            ->assertOk()
            ->assertSee('ALPHA ONLY RESULT')
            ->assertDontSee('BETA ONLY RESULT');
    }


    public function test_finalised_discharge_summary_cannot_be_modified(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $patient = \App\Models\Patient::create([
            'uhid' => 'TEST-FINAL-LOCK',
            'first_name' => 'Test',
            'last_name' => 'Patient',
            'sex' => 'Male',
        ]);

        $admission = \App\Models\Admission::create([
            'admission_no' => 'TEST-IPD-FINAL-LOCK',
            'patient_id' => $patient->id,
            'admitted_at' => now(),
            'status' => 'discharged',
        ]);

        $summary = \App\Models\DischargeSummary::create([
            'admission_id' => $admission->id,
            'final_diagnosis' => 'Original Diagnosis',
            'status' => 'finalised',
            'prepared_by' => $user->id,
            'finalised_by' => $user->id,
            'finalised_at' => now(),
        ]);

        $this->actingAs($user)
            ->put(
                route('ipd.discharge-summary.update', $admission),
                ['final_diagnosis' => 'Modified Diagnosis']
            )
            ->assertStatus(409);

        $this->assertSame(
            'Original Diagnosis',
            $summary->fresh()->final_diagnosis
        );
    }

}
