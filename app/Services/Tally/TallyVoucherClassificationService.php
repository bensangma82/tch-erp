<?php

namespace App\Services\Tally;

use App\Models\JournalEntry;
use RuntimeException;

class TallyVoucherClassificationService
{
    private const RULES = [
        'ip_billing_account:finalized' => 'Sales',
        'ip_billing_mhis_claim:approved' => 'Journal',
        'ip_billing_payment:received' => 'Receipt',
        'ip_billing_advance:received' => 'Receipt',
        'ip_billing_refund:paid' => 'Payment',
        'hospital_expense_payment:supplier_payment' => 'Payment',
        'invoice:issued' => 'Sales',
        'payment:received' => 'Receipt',
        'ip_billing_account:advance_applied' => 'Journal',
        'ip_billing_mhis_receipt:received' => 'Receipt',
        'pharmacy_grn:received' => 'Purchase',
        'pharmacy_sale:completed' => 'Sales',
        'pharmacy_sale:cogs' => 'Journal',
        'pharmacy_supplier_payment:paid' => 'Payment',
    ];

    public function classify(JournalEntry $journal): string
    {
        if ($journal->status !== 'posted') {
            throw new RuntimeException(
                'Only posted journals can be classified for export.'
            );
        }

        $key = $journal->source_type.':'.$journal->source_event;

        if (! array_key_exists($key, self::RULES)) {
            throw new RuntimeException(
                'Unsupported Tally accounting event: '.$key
            );
        }

        return self::RULES[$key];
    }
}
