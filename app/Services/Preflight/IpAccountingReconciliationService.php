<?php

namespace App\Services\Preflight;

use App\Models\IpBillingAccount;
use App\Models\JournalEntry;

final class IpAccountingReconciliationService
{
    public function inspect(IpBillingAccount $account): array
    {
        $account->load(['advances', 'payments']);

        $items = [];

        foreach ([
            'advances' => 'ip_billing_advance',
            'payments' => 'ip_billing_payment',
        ] as $relation => $sourceType) {
            foreach ($account->{$relation} as $receipt) {
                $journal = JournalEntry::query()
                    ->where('source_type', $sourceType)
                    ->where('source_id', (string) $receipt->id)
                    ->where('source_event', 'received')
                    ->first();

                $debitCodes = [
                    'cash' => '1100',
                    'upi' => '1110',
                    'card' => '1120',
                ];

                $paymentMode = strtolower(
                    trim((string) $receipt->payment_mode)
                );

                $debitCode = $debitCodes[$paymentMode] ?? null;

                $otherReferenceJournals = JournalEntry::query()
                    ->where('reference_no', $receipt->receipt_no)
                    ->where('status', 'posted')
                    ->where(function ($query) use ($sourceType, $receipt) {
                        $query->where('source_type', '!=', $sourceType)
                            ->orWhere('source_id', '!=', (string) $receipt->id);
                    })
                    ->pluck('journal_no')
                    ->all();

                $items[] = [
                    'possible_duplicate_journals' => $otherReferenceJournals,
                    'requires_finance_review' => ! empty($otherReferenceJournals),
                    'proposed_debit_gl' => $debitCode,
                    'proposed_credit_gl' => $relation === 'advances'
                        ? '2100'
                        : '1200',
                    'posting_preview_only' => true,
                    'type' => $relation,
                    'id' => $receipt->id,
                    'receipt_no' => $receipt->receipt_no,
                    'amount' => $receipt->amount,
                    'receipt_status' => $receipt->status,
                    'journal_no' => $journal?->journal_no,
                    'journal_status' => $journal?->status ?? 'missing',
                ];
            }
        }

        $application = JournalEntry::query()
            ->where('source_type', 'ip_billing_account')
            ->where('source_id', (string) $account->id)
            ->where('source_event', 'advance_applied')
            ->first();

        return [
            'items' => $items,
            'advance_application' => [
                'journal_no' => $application?->journal_no,
                'status' => $application?->status ?? 'missing',
            ],
        ];
    }
}
