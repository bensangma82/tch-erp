<?php

namespace App\Services\Accounting;

use App\Models\IpBillingMhisClaim;
use App\Models\IpBillingAccount;
use App\Models\MhisPackageVarianceApproval;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MhisPackageVarianceApprovalService
{
    private function requirePermission(string $permission): int
    {
        if (! auth()->check() || ! auth()->user()->hasPermission($permission)) {
            throw new RuntimeException('Finance permission required: '.$permission);
        }
        return (int) auth()->id();
    }

    public static function paise($value): int
    {
        $s = (string) $value;
        $negative = str_starts_with($s, '-');
        $s = $negative ? substr($s, 1) : $s;
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $s)) {
            throw new RuntimeException('Invalid MHIS package variance amount.');
        }
        [$whole, $decimal] = array_pad(explode('.', $s, 2), 2, '');
        $amount = ((int) $whole * 100) + (int) str_pad($decimal, 2, '0');
        return $negative ? -$amount : $amount;
    }

    private static function money(int $paise): string
    {
        return ($paise < 0 ? '-' : '').number_format(abs($paise) / 100, 2, '.', '');
    }

    private static function snapshot(IpBillingMhisClaim $claim, IpBillingAccount $account): array
    {
        if ((int) $claim->ip_billing_account_id !== (int) $account->id
            || ! in_array($account->status, ['open', 'finalized'], true)
            || ($account->status === 'finalized' && ! $account->final_bill_no)
            || $account->mhis_category !== 'free'
            || $claim->status !== 'approved' || ! $claim->approval_date) {
            throw new RuntimeException('Open or finalized MHIS Free bill and formally approved claim required.');
        }
        $bill = self::paise($account->net_amount);
        $approved = self::paise($claim->approved_amount);
        $variance = $approved - $bill;
        if ($approved <= 0 || $bill <= 0 || $variance === 0) {
            throw new RuntimeException('A non-zero MHIS Free package variance is required.');
        }
        return [
            'bill_amount' => self::money($bill),
            'approved_amount' => self::money($approved),
            'variance_amount' => self::money($variance),
            'treatment' => $variance > 0 ? 'additional_revenue' : 'contractual_allowance',
        ];
    }

    /** Prepares a claim-specific, immutable approval snapshot once submitted. */
    public function prepare(IpBillingMhisClaim $claim, array $inputs): MhisPackageVarianceApproval
    {
        $maker = $this->requirePermission('finance.mhis-adjustment.post');
        return DB::transaction(function () use ($claim, $inputs, $maker) {
            $account = $claim->billingAccount()->lockForUpdate()->firstOrFail();
            $claim = IpBillingMhisClaim::query()->whereKey($claim->id)->lockForUpdate()->firstOrFail();
            $snapshot = self::snapshot($claim, $account);
            $existing = MhisPackageVarianceApproval::query()
                ->where('ip_billing_mhis_claim_id', $claim->id)->lockForUpdate()->first();
            if ($existing) {
                throw new RuntimeException('Variance approval already exists; revisions require Finance review.');
            }
            $reference = trim((string) ($inputs['supporting_reference'] ?? ''));
            $reason = trim((string) ($inputs['reason'] ?? ''));
            $evidence = trim((string) ($inputs['supporting_evidence'] ?? ''));
            $nonrecoverable = (bool) ($inputs['contractual_nonrecoverable'] ?? false);
            if ($reference === '' || $reason === '' || $evidence === '') {
                throw new RuntimeException('Variance reference, reason and supporting evidence are required.');
            }
            if ($snapshot['treatment'] === 'contractual_allowance' && ! $nonrecoverable) {
                throw new RuntimeException('Finance must confirm the shortfall is contractually non-recoverable.');
            }
            return MhisPackageVarianceApproval::create(array_merge($snapshot, [
                'ip_billing_mhis_claim_id' => $claim->id,
                'ip_billing_account_id' => $account->id,
                'supporting_reference' => $reference,
                'reason' => $reason,
                'supporting_evidence' => $evidence,
                'contractual_nonrecoverable' => $nonrecoverable,
                'status' => 'submitted',
                'created_by' => $maker,
                'submitted_at' => now(),
            ]));
        });
    }

    public function approve(MhisPackageVarianceApproval $approval): MhisPackageVarianceApproval
    {
        $checker = $this->requirePermission('finance.mhis-adjustment.approve');
        return DB::transaction(function () use ($approval, $checker) {
            $source = IpBillingMhisClaim::findOrFail($approval->ip_billing_mhis_claim_id);
            $account = $source->billingAccount()->lockForUpdate()->firstOrFail();
            $claim = IpBillingMhisClaim::whereKey($source->id)->lockForUpdate()->firstOrFail();
            $a = MhisPackageVarianceApproval::whereKey($approval->id)->lockForUpdate()->firstOrFail();
            if ($a->status !== 'submitted' || (int) $a->created_by === $checker) {
                throw new RuntimeException('Only a different Finance checker may approve a submitted variance.');
            }
            $snapshot = self::snapshot($claim, $account);
            foreach ($snapshot as $key => $expected) {
                if ((string) $a->$key !== $expected) {
                    throw new RuntimeException('Variance snapshot changed; authorisation rejected.');
                }
            }
            if ((int) $a->ip_billing_account_id !== (int) $account->id
                || ($a->treatment === 'contractual_allowance' && ! $a->contractual_nonrecoverable)) {
                throw new RuntimeException('Invalid package variance authorisation.');
            }
            $a->approved_by = $checker;
            $a->approved_at = now();
            $a->status = 'approved';
            $a->save();
            return $a;
        });
    }

    /** Called within the recognition transaction after billing account and claim locks. */
    public function assertApproved(IpBillingMhisClaim $claim, IpBillingAccount $account, int $variancePaise): void
    {
        if ($variancePaise === 0) return;
        $a = MhisPackageVarianceApproval::query()
            ->where('ip_billing_mhis_claim_id', $claim->id)->lockForUpdate()->first();
        if (! $a || $a->status !== 'approved' || ! $a->approved_at || ! $a->approved_by
            || (int) $a->created_by === (int) $a->approved_by
            || (int) $a->ip_billing_account_id !== (int) $account->id) {
            throw new RuntimeException('Approved maker-checker Finance authorisation is required for MHIS package variance.');
        }
        $snapshot = self::snapshot($claim, $account);
        if (self::paise($a->variance_amount) !== $variancePaise) {
            throw new RuntimeException('Authorised variance differs from reconciled patient receivable.');
        }
        foreach ($snapshot as $key => $value) {
            if ((string) $a->$key !== $value) {
                throw new RuntimeException('Approved MHIS package variance snapshot no longer matches the claim.');
            }
        }
        if ($a->treatment === 'contractual_allowance' && ! $a->contractual_nonrecoverable) {
            throw new RuntimeException('Contractual allowance has not been confirmed non-recoverable.');
        }
        if (trim((string) $a->supporting_reference) === ''
            || trim((string) $a->supporting_evidence) === ''
            || trim((string) $a->reason) === '') {
            throw new RuntimeException('MHIS variance authorisation evidence is incomplete.');
        }
    }
}
