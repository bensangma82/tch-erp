<?php

namespace Tests\Feature;

use App\Models\GeneralLedgerAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\PharmacySale;
use App\Services\Tally\TallyPharmacySalesXmlBuilder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class TallyPharmacyStaffBenefitTest extends TestCase
{
    use DatabaseTransactions;

    public function test_staff_medical_benefit_generates_balanced_tally_sales_xml(): void
    {
        config(['tally.company' => 'TCH Test Company']);

        $patient = new \App\Models\Patient();
        $patient->forceFill([
            'uhid' => 'TEST-' . Str::uuid(),
            'first_name' => 'Test Patient',
            'sex' => 'Male',
        ])->save();

        $sale = PharmacySale::create([
            'patient_id' => $patient->id,
            'sale_no' => 'TEST-PHS-' . Str::uuid(),
            'sale_at' => now(),
            'subtotal' => '168.00',
            'discount' => '0.00',
            'total_amount' => '168.00',
            'taxable_amount' => '160.00',
            'cgst_amount' => '4.00',
            'sgst_amount' => '4.00',
            'igst_amount' => '0.00',
            'paid_amount' => '168.00',
            'balance_amount' => '0.00',
            'payment_mode' => 'staff_medical_benefit',
            'status' => 'completed',
        ]);

        $journal = JournalEntry::create([
            'journal_no' => 'TEST-JRN-' . Str::uuid(),
            'journal_date' => now()->toDateString(),
            'source_type' => 'pharmacy_sale',
            'source_id' => $sale->id,
            'source_event' => 'completed',
            'reference_no' => $sale->sale_no,
            'status' => 'draft',
        ]);

        $entries = [
            ['6113', '168.00', '0.00'],
            ['4306', '0.00', '160.00'],
            ['2311', '0.00', '4.00'],
            ['2312', '0.00', '4.00'],
            ['5101', '80.00', '0.00'],
            ['1300', '0.00', '80.00'],
        ];

        foreach ($entries as $index => [$code, $debit, $credit]) {
            $account = GeneralLedgerAccount::firstOrCreate(
                ['account_code' => $code],
                [
                    'account_name' => 'Test GL ' . $code,
                    'account_type' => 'expense',
                    'normal_balance' => 'debit',
                    'tally_ledger_name' => $code === '6113' ? 'Staff Medical Benefit Expense' : 'Test GL ' . $code,
                    'is_active' => true,
                    'allow_posting' => true,
                ]
            );

            $account->tally_ledger_name = $code === '6113'
                ? 'Staff Medical Benefit Expense'
                : 'Test GL ' . $code;
            $account->save();

            JournalEntryLine::create([
                'journal_entry_id' => $journal->id,
                'line_no' => $index + 1,
                'general_ledger_account_id' => $account->id,
                'debit' => $debit,
                'credit' => $credit,
            ]);
        }

        $user = \App\Models\User::factory()->create(['role' => 'admin']);

        $journal->update([
            'status' => 'posted',
            'posted_by' => $user->id,
            'posted_at' => now(),
        ]);

        $xml = app(TallyPharmacySalesXmlBuilder::class)->build($journal->fresh());

        $this->assertStringContainsString(
            'Staff Medical Benefit',
            $xml
        );

        $this->assertStringContainsString(
            '<VOUCHERTYPENAME>Sales</VOUCHERTYPENAME>',
            $xml
        );

        $this->assertStringContainsString('-168.00', $xml);
        $this->assertStringContainsString('160.00', $xml);
        $this->assertStringContainsString('-80.00', $xml);
        $this->assertStringContainsString('80.00', $xml);
    }
}
