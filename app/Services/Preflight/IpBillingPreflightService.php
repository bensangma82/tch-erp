<?php

namespace App\Services\Preflight;

use App\Models\GeneralLedgerAccount;
use App\Models\IpBillingAccount;

final class IpBillingPreflightService
{
    public function checkFinalization(
        IpBillingAccount $account
    ): PreflightResult {
        $result = new PreflightResult();

        $account->loadMissing(['charges.service']);
        if ($account->status === 'finalized') {
            $result->add(new PreflightIssue(
                'ALREADY_FINALIZED',
                'blocking',
                'This IPD bill is already finalized.',
                'Open the existing Final IP Bill. Do not finalize it again.',
                'Billing account #'.$account->id
            ));

            return $result;
        }

        if ($account->status !== 'open') {
            $result->add(new PreflightIssue(
                'INVALID_BILL_STATUS',
                'blocking',
                'This IPD billing account is not open for finalization.',
                'Review the billing account status before proceeding.',
                'Billing account #'.$account->id
            ));

            return $result;
        }


        $revenueHeads = GeneralLedgerAccount::query()
            ->where('account_type', 'income')
            ->where('is_active', true)
            ->where('allow_posting', true)
            ->whereNotNull('finance_head_id')
            ->whereNotIn('account_code', ['4101', '4102'])
            ->pluck('finance_head_id')
            ->all();

        if (! GeneralLedgerAccount::query()
            ->where('account_code', '1200')
            ->where('is_active', true)
            ->where('allow_posting', true)
            ->exists()) {
            $result->add(new PreflightIssue(
                'MISSING_PATIENT_RECEIVABLE_GL',
                'blocking',
                'Patient Receivables GL 1200 is unavailable.',
                'Ask Finance to activate and verify GL 1200.',
                'GL 1200'
            ));
        }

        $existingJournal = \App\Models\JournalEntry::query()
            ->where('source_type', 'ip_billing_account')
            ->where('source_id', (string) $account->id)
            ->where('source_event', 'finalized')
            ->first();

        if ($existingJournal) {
            $result->add(new PreflightIssue(
                'FINAL_BILL_JOURNAL_EXISTS',
                'blocking',
                'A final-bill accounting journal already exists.',
                'Review journal '.$existingJournal->journal_no
                    .' ('.$existingJournal->status.'). '
                    .'Do not finalize or post a duplicate.',
                'Billing account #'.$account->id
            ));
        }

        $totalPaise = 0;

        foreach ($account->charges->where('status', 'active') as $charge) {
            $amount = (int) round((float) $charge->amount * 100);

            if ($amount === 0) {
                continue;
            }

            $totalPaise += $amount;

            if ($charge->service_id) {
                $service = $charge->service;

                if (! $service || ! $service->finance_head_id) {
                    $result->add(new PreflightIssue(
                        'MISSING_FINANCE_HEAD',
                        'blocking',
                        'Missing finance-head mapping for '.$charge->description,
                        'Assign the correct revenue finance head in Service Master.',
                        'IPD charge #'.$charge->id
                    ));
                    continue;
                }

                if (! in_array($service->finance_head_id, $revenueHeads)) {
                    $result->add(new PreflightIssue(
                        'MISSING_REVENUE_GL',
                        'blocking',
                        'No eligible revenue GL for '.$service->name,
                        'Configure an active, posting-enabled revenue GL for the finance head.',
                        'Service #'.$service->id
                    ));
                }

                continue;
            }

            if ($charge->charge_type === 'bed') {
                $code = '4301';
            } elseif (in_array($charge->charge_type, [
                'pharmacy', 'pharmacy_return',
            ], true)) {
                $code = '4306';
            } else {
                $result->add(new PreflightIssue(
                    'UNMAPPED_CHARGE_TYPE',
                    'blocking',
                    'Unsupported IPD charge type: '.$charge->charge_type,
                    'Review the charge source and configure its revenue mapping.',
                    'IPD charge #'.$charge->id
                ));
                continue;
            }

            if (in_array($charge->charge_type, [
                'pharmacy', 'pharmacy_return',
            ], true)) {
                $model = match ($charge->source_type) {
                    'pharmacy_sale_item' => \App\Models\PharmacySaleItem::class,
                    'pharmacy_return_item' => \App\Models\PharmacyReturnItem::class,
                    default => null,
                };

                $source = $model && $charge->source_id
                    ? $model::find($charge->source_id)
                    : null;

                if (! $source) {
                    $result->add(new PreflightIssue(
                        'INVALID_PHARMACY_SOURCE',
                        'blocking',
                        'Pharmacy source is missing or unsupported.',
                        'Review the linked dispensing or return transaction.',
                        'IPD charge #'.$charge->id
                    ));
                } else {
                    $gstPaise = (int) round(
                        ((float) $source->cgst_amount
                        + (float) $source->sgst_amount
                        + (float) $source->igst_amount) * 100
                    );

                    $taxablePaise = abs($amount) - $gstPaise;
                    $sourceTaxablePaise = (int) round(
                        (float) $source->taxable_amount * 100
                    );

                    if ($sourceTaxablePaise === 0 && $gstPaise === 0) {
                        $taxablePaise = abs($amount);
                    }

                    if (
                        $taxablePaise < 0
                        || (
                            $sourceTaxablePaise > 0
                            && abs($taxablePaise - $sourceTaxablePaise) > 1
                        )
                    ) {
                        $result->add(new PreflightIssue(
                            'PHARMACY_GST_MISMATCH',
                            'blocking',
                            'Pharmacy taxable value and GST do not reconcile.',
                            'Review the original pharmacy sale or return tax amounts.',
                            'IPD charge #'.$charge->id
                        ));
                    }
                }
            }

            if (! GeneralLedgerAccount::query()
                ->where('account_code', $code)
                ->where('is_active', true)
                ->where('allow_posting', true)
                ->exists()) {
                $result->add(new PreflightIssue(
                    'MISSING_REVENUE_GL',
                    'blocking',
                    'Revenue GL '.$code.' is unavailable.',
                    'Activate and verify the required revenue GL account.',
                    'IPD charge #'.$charge->id
                ));
            }
        }

        $billPaise = (int) round((float) $account->net_amount * 100);

        if ($totalPaise !== $billPaise) {
            $result->add(new PreflightIssue(
                'CHARGE_TOTAL_MISMATCH',
                'blocking',
                'Active charges do not reconcile with the net final bill.',
                'Review charge amounts, cancellations and billing recalculation.',
                'Billing account #'.$account->id
            ));
        }

        return $result;
    }
}
