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
            throw new MhisPackageVarianceReviewException('Finance permission required: '.$permission);
        }
        return (int) auth()->id();
    }

    public static function paise($value): int
    {
        $s = (string) $value;
        $negative = str_starts_with($s, '-');
        $s = $negative ? substr($s, 1) : $s;
        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $s)) {
            throw new MhisPackageVarianceReviewException('Invalid MHIS package variance amount.');
        }
        [$whole, $decimal] = array_pad(explode('.', $s, 2), 2, '');
        $amount = ((int) $whole * 100) + (int) str_pad($decimal, 2, '0');
        return $negative ? -$amount : $amount;
    }

    public static function recordedReference(IpBillingMhisClaim $claim): string
    {
        foreach (['authorization_no', 'claim_no', 'mhis_registration_id'] as $field) {
            $value = trim((string) $claim->$field);
            if ($value !== '' && ! in_array($value, ['Actual MHIS approval/package reference', 'Actual approval document/reference'], true)) return $value;
        }
        return '';
    }

    private static function money(int $paise): string
    {
        return ($paise < 0 ? '-' : '').number_format(abs($paise) / 100, 2, '.', '');
    }

    public static function snapshot(IpBillingMhisClaim $claim, IpBillingAccount $account): array
    {
        if ((int) $claim->ip_billing_account_id !== (int) $account->id
            || ! in_array($account->status, ['open', 'finalized'], true)
            || ($account->status === 'finalized' && ! $account->final_bill_no)
            || $account->mhis_category !== 'free'
            || $claim->status !== 'approved' || ! $claim->approval_date) {
            throw new MhisPackageVarianceReviewException('Open or finalized MHIS Free bill and formally approved claim required.');
        }
        $bill = self::paise($account->net_amount);
        $approved = self::paise($claim->approved_amount);
        $variance = $approved - $bill;
        if ($approved <= 0 || $bill <= 0 || $variance === 0) {
            throw new MhisPackageVarianceReviewException('A non-zero MHIS Free package variance is required.');
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
            if ($existing && ($existing->status !== 'returned' || (int) $existing->created_by !== $maker)) {
                throw new MhisPackageVarianceReviewException('A review already exists. Only the original submitter may revise a returned review.');
            }
            if ($inputs['simple_review'] ?? false) {
                if (trim((string) ($inputs['supporting_reference'] ?? '')) === '') {
                    $inputs['supporting_reference'] = self::recordedReference($claim);
                }
                if (trim((string) $inputs['supporting_reference']) === '') {
                    throw new MhisPackageVarianceReviewException('No approval, claim or registration reference is recorded. Add the real identifier to the claim or supply a traceable document reference.');
                }
                if (strlen((string) $inputs['supporting_reference']) > 255) {
                    throw new MhisPackageVarianceReviewException('Supporting reference must fit within 255 characters.');
                }
                if (self::paise($inputs['expected_bill'] ?? '') !== self::paise($snapshot['bill_amount'])
                    || self::paise($inputs['expected_approved'] ?? '') !== self::paise($snapshot['approved_amount'])) {
                    throw new MhisPackageVarianceReviewException('The bill or approval changed. Refresh the review before submitting.');
                }
                $reasons = ['recorded_approval' => 'Recorded MHIS approval differs from the hospital bill', 'package_tariff' => 'Difference from contractual MHIS package tariff',
                    'approved_package' => 'Difference from the formally approved MHIS package', 'other' => 'Other documented package difference'];
                $choice = trim((string) ($inputs['reason_choice'] ?? '')) ?: 'recorded_approval';
                if (! isset($reasons[$choice])) throw new MhisPackageVarianceReviewException('Select a reason for review.');
                if ($choice === 'other' && trim((string) ($inputs['notes'] ?? '')) === '') throw new MhisPackageVarianceReviewException('Explain the Other reason in notes.');
                $inputs['reason'] = $reasons[$choice].'. Hospital bill ₹'.$snapshot['bill_amount']
                    .'; approved MHIS ₹'.$snapshot['approved_amount'].'; difference ₹'.self::money(abs(self::paise($snapshot['variance_amount'])))
                    .'. '.trim((string) ($inputs['notes'] ?? ''));
                $inputs['supporting_evidence'] = 'ERP snapshot: account '.$account->account_no.'; claim '.$claim->id
                    .'; recorded approval date '.$claim->approval_date->format('Y-m-d')
                    .'; package '.($claim->package_code ?: 'not recorded')
                    .'; recorded or supplied reference: '.($inputs['supporting_reference'] ?? '')
                    .'; captured by '.auth()->user()->name
                    .'; source: ERP claim record; no live MHIS portal lookup performed; recorded at '.now()->toIso8601String().'. '
                    .trim((string) ($inputs['notes'] ?? ''));
            }
            $reference = trim((string) ($inputs['supporting_reference'] ?? ''));
            $reason = trim((string) ($inputs['reason'] ?? ''));
            $evidence = trim((string) ($inputs['supporting_evidence'] ?? ''));
            $nonrecoverable = (bool) ($inputs['contractual_nonrecoverable'] ?? false);
            if ($reference === '' || $reason === '' || $evidence === '') {
                throw new MhisPackageVarianceReviewException('Variance reference, reason and supporting evidence are required.');
            }
            if ($snapshot['treatment'] === 'contractual_allowance' && ! $nonrecoverable) {
                throw new MhisPackageVarianceReviewException('Finance must confirm the shortfall is contractually non-recoverable.');
            }
            $data = array_merge($snapshot, [
                'ip_billing_mhis_claim_id' => $claim->id,
                'ip_billing_account_id' => $account->id,
                'supporting_reference' => $reference,
                'reason' => $reason,
                'supporting_evidence' => $evidence,
                'contractual_nonrecoverable' => $nonrecoverable,
                'status' => 'submitted',
                'created_by' => $maker,
                'submitted_at' => now(),
                'approved_by' => null,
                'approved_at' => null,
            ]);
            if ($existing) {
                $this->event($existing, 'before_revision', $maker, 'Preserved returned snapshot before revision.');
                $existing->update($data);
                $approval = $existing->fresh();
            } else {
                $approval = MhisPackageVarianceApproval::create($data);
            }
            $this->event($approval, $existing ? 'resubmitted' : 'submitted', $maker);
            return $approval;
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
                throw new MhisPackageVarianceReviewException('Only a different Finance checker may approve a submitted variance.');
            }
            $snapshot = self::snapshot($claim, $account);
            foreach ($snapshot as $key => $expected) {
                if ((string) $a->$key !== $expected) {
                    throw new MhisPackageVarianceReviewException('Variance snapshot changed; authorisation rejected.');
                }
            }
            if ((int) $a->ip_billing_account_id !== (int) $account->id
                || ($a->treatment === 'contractual_allowance' && ! $a->contractual_nonrecoverable)) {
                throw new MhisPackageVarianceReviewException('Invalid package variance authorisation.');
            }
            $a->approved_by = $checker;
            $a->approved_at = now();
            $a->status = 'approved';
            $a->save();
            $this->event($a, 'approved', $checker);
            return $a;
        });
    }

    private function event(MhisPackageVarianceApproval $approval, string $action, int $actor, ?string $notes = null): void
    {
        DB::table('mhis_variance_review_events')->insert([
            'approval_id' => $approval->id, 'actor_id' => $actor, 'action' => $action,
            'snapshot' => json_encode($approval->getAttributes(), JSON_THROW_ON_ERROR),
            'notes' => $notes, 'created_at' => now(),
        ]);
    }

    public function returnForCorrection(MhisPackageVarianceApproval $approval, string $notes): void
    {
        $checker = $this->requirePermission('finance.mhis-adjustment.approve');
        $notes = trim($notes);
        if ($notes === '') throw new MhisPackageVarianceReviewException('Explain what needs correction.');
        DB::transaction(function () use ($approval, $checker, $notes) {
            $source = IpBillingMhisClaim::findOrFail($approval->ip_billing_mhis_claim_id);
            $source->billingAccount()->lockForUpdate()->firstOrFail();
            IpBillingMhisClaim::whereKey($source->id)->lockForUpdate()->firstOrFail();
            $a = MhisPackageVarianceApproval::whereKey($approval->id)->lockForUpdate()->firstOrFail();
            if ($a->status !== 'submitted' || (int) $a->created_by === $checker) {
                throw new MhisPackageVarianceReviewException('Only a different Finance checker may return a submitted review.');
            }
            $a->update(['status' => 'returned', 'approved_by' => null, 'approved_at' => null]);
            $this->event($a, 'returned', $checker, $notes);
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
            throw new MhisPackageVarianceReviewException('Approved maker-checker Finance authorisation is required for MHIS package variance.');
        }
        $snapshot = self::snapshot($claim, $account);
        if (self::paise($a->variance_amount) !== $variancePaise) {
            throw new MhisPackageVarianceReviewException('Authorised variance differs from reconciled patient receivable.');
        }
        foreach ($snapshot as $key => $value) {
            if ((string) $a->$key !== $value) {
                throw new MhisPackageVarianceReviewException('Approved MHIS package variance snapshot no longer matches the claim.');
            }
        }
        if ($a->treatment === 'contractual_allowance' && ! $a->contractual_nonrecoverable) {
            throw new MhisPackageVarianceReviewException('Contractual allowance has not been confirmed non-recoverable.');
        }
        if (trim((string) $a->supporting_reference) === ''
            || trim((string) $a->supporting_evidence) === ''
            || trim((string) $a->reason) === '') {
            throw new MhisPackageVarianceReviewException('MHIS variance authorisation evidence is incomplete.');
        }
    }
}
