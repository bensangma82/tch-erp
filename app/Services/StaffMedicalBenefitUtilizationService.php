<?php

namespace App\Services;

use App\Models\EmployeeDependent;
use App\Models\Patient;
use App\Models\StaffMedicalBenefitAccount;
use App\Models\StaffMedicalBenefitTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StaffMedicalBenefitUtilizationService
{
    /**
     * Record Staff Medical Benefit utilization.
     *
     * The benefit account is locked before the available balance
     * is calculated so concurrent billing cannot overspend the
     * employee or dependent-family entitlement.
     */
    public function utilize(
        StaffMedicalBenefitAccount $benefitAccount,
        Patient $patient,
        string $beneficiaryType,
        float $requestedAmount,
        string $sourceType,
        ?int $sourceId = null,
        ?string $sourceReference = null,
        $transactionDate = null,
        ?EmployeeDependent $dependent = null,
        ?float $grossBillAmount = null,
        ?float $mhisApprovedAmount = null,
        ?float $residualBeforeBenefit = null,
        ?string $remarks = null,
        ?int $userId = null
    ): StaffMedicalBenefitTransaction {
        $transactionDate = Carbon::parse(
            $transactionDate ?? now()
        )->startOfDay();

        $beneficiaryType = strtolower(
            trim($beneficiaryType)
        );

        $sourceType = strtolower(
            trim($sourceType)
        );

        if (! in_array(
            $beneficiaryType,
            ['employee', 'dependent'],
            true
        )) {
            throw new RuntimeException(
                'Invalid Staff Medical Benefit beneficiary type.'
            );
        }

        if ($requestedAmount <= 0) {
            throw new RuntimeException(
                'Staff Medical Benefit amount must be greater than zero.'
            );
        }

        if ($sourceType === '') {
            throw new RuntimeException(
                'Staff Medical Benefit source type is required.'
            );
        }

        if (
            $grossBillAmount !== null &&
            $grossBillAmount < 0
        ) {
            throw new RuntimeException(
                'Gross bill amount cannot be negative.'
            );
        }

        if (
            $mhisApprovedAmount !== null &&
            $mhisApprovedAmount < 0
        ) {
            throw new RuntimeException(
                'MHIS approved amount cannot be negative.'
            );
        }

        if (
            $residualBeforeBenefit !== null &&
            $residualBeforeBenefit < 0
        ) {
            throw new RuntimeException(
                'Residual before benefit cannot be negative.'
            );
        }

        return DB::transaction(function () use (
            $benefitAccount,
            $patient,
            $beneficiaryType,
            $requestedAmount,
            $sourceType,
            $sourceId,
            $sourceReference,
            $transactionDate,
            $dependent,
            $grossBillAmount,
            $mhisApprovedAmount,
            $residualBeforeBenefit,
            $remarks,
            $userId
        ) {
            /*
             * Lock the annual account before calculating utilization.
             */
            $account = StaffMedicalBenefitAccount::query()
                ->whereKey($benefitAccount->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($account->status !== 'active') {
                throw new RuntimeException(
                    'Staff Medical Benefit account is not active.'
                );
            }

            if (! $account->isEligibleOn($transactionDate)) {
                throw new RuntimeException(
                    'Staff Medical Benefit account is not eligible '
                    . 'on the transaction date.'
                );
            }

            /*
             * Prevent utilization outside this financial year.
             */
            if (
                $transactionDate->lt(
                    $account->financial_year_start->copy()->startOfDay()
                ) ||
                $transactionDate->gt(
                    $account->financial_year_end->copy()->endOfDay()
                )
            ) {
                throw new RuntimeException(
                    'Transaction date falls outside the benefit '
                    . 'financial year.'
                );
            }

            /*
             * Validate beneficiary identity.
             */
            if ($beneficiaryType === 'employee') {
                if (! $account->employee_id) {
                    throw new RuntimeException(
                        'Benefit account has no employee.'
                    );
                }

                $account->loadMissing('employee');

                if (! $account->employee->patient_id) {
                    throw new RuntimeException(
                        'Employee is not linked to a Patient/UHID.'
                    );
                }

                if (
                    (int) $account->employee->patient_id !==
                    (int) $patient->id
                ) {
                    throw new RuntimeException(
                        'Selected patient does not match the '
                        . 'employee Patient/UHID.'
                    );
                }

                if ($dependent !== null) {
                    throw new RuntimeException(
                        'Dependent must not be supplied for an '
                        . 'employee benefit transaction.'
                    );
                }
            }

            if ($beneficiaryType === 'dependent') {
                if (! $dependent) {
                    throw new RuntimeException(
                        'Dependent record is required for a '
                        . 'dependent benefit transaction.'
                    );
                }

                /*
                 * Lock the dependent eligibility record as well.
                 */
                $lockedDependent = EmployeeDependent::query()
                    ->whereKey($dependent->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    (int) $lockedDependent->employee_id !==
                    (int) $account->employee_id
                ) {
                    throw new RuntimeException(
                        'Dependent does not belong to this employee.'
                    );
                }

                if (
                    ! $lockedDependent->patient_id ||
                    (int) $lockedDependent->patient_id !==
                    (int) $patient->id
                ) {
                    throw new RuntimeException(
                        'Selected patient does not match the '
                        . 'registered dependent Patient/UHID.'
                    );
                }

                if (
                    ! $lockedDependent->isEligibleOn(
                        $transactionDate
                    )
                ) {
                    throw new RuntimeException(
                        'Dependent is not eligible on the '
                        . 'transaction date.'
                    );
                }

                $dependent = $lockedDependent;
            }

            /*
             * Calculate utilization directly from the locked account's
             * ledger rather than trusting a previously displayed balance.
             */
            $utilized = StaffMedicalBenefitTransaction::query()
                ->where(
                    'benefit_account_id',
                    $account->id
                )
                ->where(
                    'beneficiary_type',
                    $beneficiaryType
                )
                ->where(
                    'status',
                    'active'
                )
                ->selectRaw(
                    "
                    COALESCE(
                        SUM(
                            CASE
                                WHEN transaction_type = 'utilization'
                                    THEN amount
                                WHEN transaction_type = 'reversal'
                                    THEN -amount
                                ELSE 0
                            END
                        ),
                        0
                    ) AS utilized
                    "
                )
                ->value('utilized');

            $utilized = max(
                0,
                (float) $utilized
            );

            $entitlement = $beneficiaryType === 'employee'
                ? (float) $account->employee_entitlement
                : (float) $account->dependent_family_entitlement;

            $availableBalance = max(
                0,
                $entitlement - $utilized
            );

            if ($availableBalance <= 0) {
                throw new RuntimeException(
                    'No Staff Medical Benefit balance is available.'
                );
            }

            /*
             * Never consume more than the residual bill amount when
             * the billing module supplies that value.
             */
            $maximumAllowed = $availableBalance;

            if ($residualBeforeBenefit !== null) {
                $maximumAllowed = min(
                    $maximumAllowed,
                    (float) $residualBeforeBenefit
                );
            }

            $amountToUtilize = min(
                (float) $requestedAmount,
                $maximumAllowed
            );

            $amountToUtilize = round(
                $amountToUtilize,
                2
            );

            if ($amountToUtilize <= 0) {
                throw new RuntimeException(
                    'No amount is available for Staff Medical '
                    . 'Benefit utilization.'
                );
            }

            /*
             * Protect against accidental duplicate posting from the
             * same billing source.
             *
             * Manual sources are excluded because source_id may be null.
             */
            if (
                $sourceId !== null &&
                $sourceType !== 'manual'
            ) {
                $duplicate = StaffMedicalBenefitTransaction::query()
                    ->where(
                        'benefit_account_id',
                        $account->id
                    )
                    ->where(
                        'source_type',
                        $sourceType
                    )
                    ->where(
                        'source_id',
                        $sourceId
                    )
                    ->where(
                        'transaction_type',
                        'utilization'
                    )
                    ->where(
                        'status',
                        'active'
                    )
                    ->exists();

                if ($duplicate) {
                    throw new RuntimeException(
                        'Staff Medical Benefit has already been '
                        . 'utilized for this billing source.'
                    );
                }
            }

            return StaffMedicalBenefitTransaction::create([
                'benefit_account_id' =>
                    $account->id,

                'beneficiary_type' =>
                    $beneficiaryType,

                'dependent_id' =>
                    $beneficiaryType === 'dependent'
                        ? $dependent->id
                        : null,

                'patient_id' =>
                    $patient->id,

                'transaction_type' =>
                    'utilization',

                'transaction_date' =>
                    $transactionDate->toDateString(),

                'amount' =>
                    $amountToUtilize,

                'source_type' =>
                    $sourceType,

                'source_id' =>
                    $sourceId,

                'source_reference' =>
                    $sourceReference,

                'gross_bill_amount' =>
                    $grossBillAmount,

                'mhis_approved_amount' =>
                    $mhisApprovedAmount,

                'residual_before_benefit' =>
                    $residualBeforeBenefit,

                'reverses_transaction_id' =>
                    null,

                'status' =>
                    'active',

                'reason' =>
                    null,

                'remarks' =>
                    $remarks,

                'created_by' =>
                    $userId,

                'updated_by' =>
                    $userId,
            ]);
        });
    }


                /**
     * Reverse a Staff Medical Benefit utilization.
     *
     * A reversal is recorded as a separate ledger transaction.
     * The original utilization is never deleted or modified.
     */
    public function reverse(
        StaffMedicalBenefitTransaction $utilization,
        ?float $amount = null,
        $transactionDate = null,
        ?string $reason = null,
        ?string $remarks = null,
        ?int $userId = null
    ): StaffMedicalBenefitTransaction {
        $transactionDate = Carbon::parse(
            $transactionDate ?? now()
        )->startOfDay();

        return DB::transaction(function () use (
            $utilization,
            $amount,
            $transactionDate,
            $reason,
            $remarks,
            $userId
        ) {
            /*
             * Lock the original utilization first.
             */
            $original = StaffMedicalBenefitTransaction::query()
                ->whereKey($utilization->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($original->transaction_type !== 'utilization') {
                throw new RuntimeException(
                    'Only a utilization transaction can be reversed.'
                );
            }

            if ($original->status !== 'active') {
                throw new RuntimeException(
                    'Only an active utilization transaction can be reversed.'
                );
            }

            /*
             * Lock the annual benefit account as well.
             */
            $account = StaffMedicalBenefitAccount::query()
                ->whereKey($original->benefit_account_id)
                ->lockForUpdate()
                ->firstOrFail();

            /*
             * Calculate how much of this utilization has already
             * been reversed.
             */
            $alreadyReversed = StaffMedicalBenefitTransaction::query()
                ->where(
                    'reverses_transaction_id',
                    $original->id
                )
                ->where(
                    'transaction_type',
                    'reversal'
                )
                ->where(
                    'status',
                    'active'
                )
                ->sum('amount');

            $alreadyReversed = round(
                (float) $alreadyReversed,
                2
            );

            $originalAmount = round(
                (float) $original->amount,
                2
            );

            $remainingReversible = round(
                $originalAmount - $alreadyReversed,
                2
            );

            if ($remainingReversible <= 0) {
                throw new RuntimeException(
                    'This Staff Medical Benefit utilization has '
                    . 'already been fully reversed.'
                );
            }

            /*
             * If no amount is supplied, reverse the entire remaining
             * amount. Partial reversals are also supported.
             */
            $amountToReverse = $amount === null
                ? $remainingReversible
                : round((float) $amount, 2);

            if ($amountToReverse <= 0) {
                throw new RuntimeException(
                    'Reversal amount must be greater than zero.'
                );
            }

            if ($amountToReverse > $remainingReversible) {
                throw new RuntimeException(
                    'Reversal amount exceeds the remaining '
                    . 'reversible amount.'
                );
            }

            /*
             * A reversal cannot pre-date the original utilization.
             */
            if (
                $transactionDate->lt(
                    $original->transaction_date
                        ->copy()
                        ->startOfDay()
                )
            ) {
                throw new RuntimeException(
                    'Reversal date cannot be earlier than the '
                    . 'original utilization date.'
                );
            }

            /*
             * Keep the reversal inside the same financial-year
             * benefit account.
             */
            if (
                $transactionDate->lt(
                    $account->financial_year_start
                        ->copy()
                        ->startOfDay()
                ) ||
                $transactionDate->gt(
                    $account->financial_year_end
                        ->copy()
                        ->endOfDay()
                )
            ) {
                throw new RuntimeException(
                    'Reversal date falls outside the benefit '
                    . 'financial year.'
                );
            }

            return StaffMedicalBenefitTransaction::create([
                'benefit_account_id' =>
                    $original->benefit_account_id,

                'beneficiary_type' =>
                    $original->beneficiary_type,

                'dependent_id' =>
                    $original->dependent_id,

                'patient_id' =>
                    $original->patient_id,

                'transaction_type' =>
                    'reversal',

                'transaction_date' =>
                    $transactionDate->toDateString(),

                'amount' =>
                    $amountToReverse,

                'source_type' =>
                    $original->source_type,

                'source_id' =>
                    $original->source_id,

                'source_reference' =>
                    $original->source_reference,

                'gross_bill_amount' =>
                    $original->gross_bill_amount,

                'mhis_approved_amount' =>
                    $original->mhis_approved_amount,

                'residual_before_benefit' =>
                    $original->residual_before_benefit,

                'reverses_transaction_id' =>
                    $original->id,

                'status' =>
                    'active',

                'reason' =>
                    $reason,

                'remarks' =>
                    $remarks,

                'created_by' =>
                    $userId,

                'updated_by' =>
                    $userId,
            ]);
        });
    }
          

}
