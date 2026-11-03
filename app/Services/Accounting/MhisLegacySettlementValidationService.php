<?php

namespace App\Services\Accounting;

use App\Models\MhisLegacySettlement;
use App\Models\MhisOpeningReceivable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MhisLegacySettlementValidationService
{
    public function paise(string|int|float $amount): int
    {
        $value = (string) $amount;

        if (! preg_match('/^\d{1,16}(?:\.\d{1,2})?$/', $value)) {
            throw ValidationException::withMessages([
                'amount' => 'Invalid monetary amount.',
            ]);
        }

        [$rupees, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $rupees * 100)
            + (int) str_pad($fraction, 2, '0');
    }

    public function validateForVerification(
        MhisLegacySettlement $settlement,
        ?int $verifierId = null
    ): void {
        DB::transaction(function () use ($settlement, $verifierId) {
            $opening = MhisOpeningReceivable::query()
                ->whereKey($settlement->mhis_opening_receivable_id)
                ->lockForUpdate()
                ->firstOrFail();

            $settlement = MhisLegacySettlement::query()
                ->whereKey($settlement->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($opening->status !== 'approved') {
                throw ValidationException::withMessages([
                    'opening' => 'Opening MHIS receivable must be approved.',
                ]);
            }

            if ($settlement->status !== 'draft') {
                throw ValidationException::withMessages([
                    'settlement' => 'Only draft settlements can be verified.',
                ]);
            }

            $net = $this->paise($settlement->net_amount);
            $tds = $this->paise($settlement->tds_amount);
            $deduction = $this->paise($settlement->approved_deduction);
            $gross = $this->paise($settlement->gross_settled_amount);

            if ($gross <= 0 || $gross !== $net + $tds + $deduction) {
                throw ValidationException::withMessages([
                    'settlement' => 'Gross settlement does not equal net, TDS and deductions.',
                ]);
            }

            if (
                $settlement->settlement_date->lt($opening->opening_date)
            ) {
                throw ValidationException::withMessages([
                    'settlement_date' => 'Settlement date cannot precede the opening date.',
                ]);
            }

            $duplicate = MhisLegacySettlement::query()
                ->where('mhis_opening_receivable_id', $opening->id)
                ->where('id', '!=', $settlement->id)
                ->whereIn('status', ['verified', 'posted'])
                ->where('settlement_reference', $settlement->settlement_reference)
                ->where('historical_claim_reference', $settlement->historical_claim_reference)
                ->where('gross_settled_amount', $settlement->gross_settled_amount)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'settlement_reference' => 'Possible duplicate historical MHIS settlement.',
                ]);
            }

            $used = 0;

            MhisLegacySettlement::query()
                ->where('mhis_opening_receivable_id', $opening->id)
                ->where('id', '!=', $settlement->id)
                ->whereIn('status', ['verified', 'posted'])
                ->select('gross_settled_amount')
                ->orderBy('id')
                ->chunk(100, function ($rows) use (&$used) {
                    foreach ($rows as $row) {
                        $used += $this->paise($row->gross_settled_amount);
                    }
                });

            $available = $this->paise($opening->opening_amount) - $used;

            if ($gross > $available) {
                throw ValidationException::withMessages([
                    'settlement' => 'Settlement exceeds the remaining opening MHIS receivable balance.',
                ]);
            }

            // Verification must occur within the same locked transaction.
            if ($verifierId !== null) {
                if (! \App\Models\User::whereKey($verifierId)->exists()) {
                    throw ValidationException::withMessages([
                        'verifier' => 'Invalid Finance verifier.',
                    ]);
                }

                if (
                    $settlement->created_by !== null
                    && (int) $settlement->created_by === $verifierId
                ) {
                    throw ValidationException::withMessages([
                        'verifier' => 'The settlement creator cannot verify their own entry.',
                    ]);
                }

                $settlement->status = 'verified';
                $settlement->verified_by = $verifierId;
                $settlement->verified_at = now();
                $settlement->save();
            }
        });
    }
}
