<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\IpBillingAccount;
use App\Models\Patient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IpdAdmissionBillingLinkageAcceptanceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (DB::connection()->getDatabaseName() !== 'testing') {
            throw new \RuntimeException('Refusing IPD acceptance tests outside database testing');
        }
    }

    private function makeAdmission(string $suffix): array
    {
        $key = 'IPD-ACCEPT-' . $suffix . '-' . bin2hex(random_bytes(4));
        $patient = Patient::create([
            'uhid' => $key,
            'first_name' => 'Acceptance',
            'last_name' => $suffix,
            'sex' => 'male',
            'is_active' => true,
        ]);
        $admission = Admission::create([
            'admission_no' => $key,
            'patient_id' => $patient->id,
            'admitted_at' => now(),
            'status' => 'admitted',
        ]);
        return [$patient, $admission, $key];
    }

    public function test_admission_and_billing_account_refer_to_same_patient(): void
    {
        [$patient, $admission, $key] = $this->makeAdmission('LINK');
        $account = IpBillingAccount::create([
            'admission_id' => $admission->id,
            'patient_id' => $patient->id,
            'account_no' => $key,
            'opened_at' => now(),
            'status' => 'open',
            'net_amount' => '0.00',
            'paid_amount' => '0.00',
            'balance_amount' => '0.00',
        ]);
        $this->assertSame($patient->id, $admission->patient->id);
        $this->assertSame($admission->id, $account->admission->id);
        $this->assertSame($patient->id, $account->patient->id);
        $this->assertSame('0.00', $account->balance_amount);
    }

    public function test_two_admissions_have_distinct_billing_accounts(): void
    {
        [$firstPatient, $firstAdmission, $firstKey] = $this->makeAdmission('FIRST');
        [$secondPatient, $secondAdmission, $secondKey] = $this->makeAdmission('SECOND');
        foreach ([[$firstPatient, $firstAdmission, $firstKey], [$secondPatient, $secondAdmission, $secondKey]] as [$patient, $admission, $key]) {
            IpBillingAccount::create([
                'admission_id' => $admission->id,
                'patient_id' => $patient->id,
                'account_no' => $key,
                'opened_at' => now(),
                'status' => 'open',
                'net_amount' => '0.00',
                'paid_amount' => '0.00',
                'balance_amount' => '0.00',
            ]);
        }
        $this->assertSame(1, IpBillingAccount::where('admission_id', $firstAdmission->id)->count());
        $this->assertSame(1, IpBillingAccount::where('admission_id', $secondAdmission->id)->count());
        $this->assertSame(0, IpBillingAccount::where('admission_id', $firstAdmission->id)->where('patient_id', $secondPatient->id)->count());
    }
}
