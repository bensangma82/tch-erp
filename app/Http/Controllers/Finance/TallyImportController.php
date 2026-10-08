<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use App\Models\FinanceHead;
use App\Models\FinanceVoucher;
use App\Models\TallyImportedVoucher;
use App\Models\TallyLedgerMapping;
use App\Services\Finance\FinanceVoucherNumberService;
use App\Services\Tally\TallyImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class TallyImportController extends Controller
{
    public function sync(
        TallyImportService $importService
    ): RedirectResponse {
        // Prevent overlapping synchronization requests.
        $lock = Cache::lock(
            'tch:tally:voucher-import',
            600
        );

        if (! $lock->get()) {
            return redirect()
                ->route('finance.tally.index')
                ->with(
                    'error',
                    'A Tally synchronization is already running.'
                );
        }

        try {
            $summary = $importService->sync();

            Log::info('Tally voucher synchronization completed', [
                'user_id' => auth()->id(),
                'summary' => $summary,
            ]);

            return redirect()
                ->route('finance.tally.index')
                ->with(
                    'success',
                    'Tally synchronization completed. '
                    . 'Received: ' . $summary['received']
                    . ', Imported: ' . $summary['imported']
                    . ', Updated: ' . $summary['updated']
                    . ', Skipped: ' . $summary['skipped']
                );
        } catch (Throwable $exception) {
            Log::error('Tally voucher synchronization failed', [
                'user_id' => auth()->id(),
                'exception' => $exception,
            ]);

            return redirect()
                ->route('finance.tally.index')
                ->with(
                    'error',
                    'Tally synchronization failed. '
                    . 'Please check the Tally connection and Laravel logs.'
                );
        } finally {
            $lock->release();
        }
    }

    /**
     * Review an unlinked imported Tally voucher.
     */
    public function review(
        TallyImportedVoucher $tallyVoucher
    ): View {
        abort_if(
            $tallyVoucher->finance_voucher_id !== null,
            409,
            'This Tally voucher is already linked to an ERP voucher.'
        );

        $tallyVoucher->load([
            'entries' => fn ($query) => $query->orderBy('line_no'),
        ]);

        $type = $this->supportedType($tallyVoucher);

        $accounts = FinanceAccount::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $heads = FinanceHead::query()
            ->where('is_active', true)
            ->whereDoesntHave('children', function ($query) {
                $query->where('is_active', true);
            })
            ->when(
                $type === 'receipt',
                fn ($query) => $query->where('head_type', 'income')
            )
            ->when(
                $type === 'payment',
                fn ($query) => $query->where('head_type', 'expense')
            )
            ->orderBy('name')
            ->get();

        $ledgerNames = $tallyVoucher->entries
            ->pluck('ledger_name')
            ->filter()
            ->unique()
            ->values();

        $mappings = TallyLedgerMapping::query()
            ->with(['financeHead', 'financeAccount'])
            ->where('is_active', true)
            ->whereIn('tally_ledger_name', $ledgerNames)
            ->get();

        $amount = round(
            $tallyVoucher->entries->sum(
                fn ($entry) => abs((float) $entry->amount)
            ) / 2,
            2
        );

        return view(
            'finance.tally.import-review',
            compact(
                'tallyVoucher',
                'type',
                'accounts',
                'heads',
                'mappings',
                'amount'
            )
        );
    }

    /**
     * Create an ERP Finance voucher as Draft.
     *
     * Never automatically posts to Finance.
     */
    public function createDraft(
        Request $request,
        TallyImportedVoucher $tallyVoucher,
        FinanceVoucherNumberService $voucherNumberService
    ): RedirectResponse {
        $validated = $request->validate([
            'voucher_type' => [
                'required',
                Rule::in(['receipt', 'payment', 'transfer']),
            ],
            'finance_head_id' => [
                'nullable',
                'integer',
                'exists:finance_heads,id',
            ],
            'finance_account_id' => [
                'required',
                'integer',
                'exists:finance_accounts,id',
            ],
            'destination_account_id' => [
                'nullable',
                'integer',
                'exists:finance_accounts,id',
            ],
            'payment_mode' => [
                'nullable',
                'string',
                'max:50',
            ],
            'reference_no' => [
                'nullable',
                'string',
                'max:100',
            ],
            'party_name' => [
                'nullable',
                'string',
                'max:200',
            ],
            'narration' => [
                'nullable',
                'string',
            ],
            'confirm_review' => [
                'accepted',
            ],
        ]);

        $userId = $request->user()?->id;

        abort_if($userId === null, 403);

        $voucher = DB::transaction(function () use (
            $tallyVoucher,
            $validated,
            $userId,
            $voucherNumberService
        ) {
            $source = TallyImportedVoucher::query()
                ->whereKey($tallyVoucher->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($source->finance_voucher_id !== null) {
                throw ValidationException::withMessages([
                    'voucher' => 'This Tally voucher is already linked to an ERP voucher.',
                ]);
            }

            if (empty($source->guid) || empty($source->tally_company)) {
                throw ValidationException::withMessages([
                    'voucher' => 'A verified Tally company and GUID are required.',
                ]);
            }

            $type = $this->supportedType($source);

            if ($type === null || $type !== $validated['voucher_type']) {
                throw ValidationException::withMessages([
                    'voucher_type' => 'Unsupported or mismatched Tally voucher type.',
                ]);
            }

            $source->load('entries');

            $entries = $source->entries;

            if ($entries->count() < 2) {
                throw ValidationException::withMessages([
                    'voucher' => 'At least two Tally ledger entries are required.',
                ]);
            }

            $debits = 0.0;
            $credits = 0.0;

            foreach ($entries as $entry) {
                $amount = (float) $entry->amount;

                if (
                    ! is_finite($amount)
                    || abs($amount) < 0.001
                    || blank($entry->ledger_name)
                ) {
                    throw ValidationException::withMessages([
                        'voucher' => 'The imported voucher contains an invalid ledger entry.',
                    ]);
                }

                if ($amount < 0) {
                    $debits += abs($amount);
                } else {
                    $credits += $amount;
                }
            }

            if (
                abs($debits - $credits) > 0.01
                || $debits <= 0
            ) {
                throw ValidationException::withMessages([
                    'voucher' => 'The imported Tally voucher is not balanced.',
                ]);
            }

            $amount = round($debits, 2);

            /*
             * Initial conversion supports only simple
             * two-ledger vouchers.
             *
             * Complex split postings need separate handling.
             */
            if ($entries->count() !== 2) {
                throw ValidationException::withMessages([
                    'voucher' => 'Split-ledger vouchers require manual accounting review.',
                ]);
            }

            $account = FinanceAccount::query()
                ->whereKey($validated['finance_account_id'])
                ->where('is_active', true)
                ->first();

            if (! $account) {
                throw ValidationException::withMessages([
                    'finance_account_id' => 'Select an active finance account.',
                ]);
            }

            $headId = null;
            $destinationAccountId = null;

            if (in_array($type, ['receipt', 'payment'], true)) {
                $headId = $validated['finance_head_id'] ?? null;

                $head = FinanceHead::query()
                    ->whereKey($headId)
                    ->where('is_active', true)
                    ->first();

                if (
                    ! $head
                    || $head->children()
                        ->where('is_active', true)
                        ->exists()
                    || $head->head_type !== (
                        $type === 'receipt' ? 'income' : 'expense'
                    )
                ) {
                    throw ValidationException::withMessages([
                        'finance_head_id' => 'Select a valid active income or expense sub-head.',
                    ]);
                }
            } else {
                $destinationAccountId =
                    $validated['destination_account_id'] ?? null;

                $destination = FinanceAccount::query()
                    ->whereKey($destinationAccountId)
                    ->where('is_active', true)
                    ->first();

                if (
                    ! $destination
                    || (int) $destination->id === (int) $account->id
                ) {
                    throw ValidationException::withMessages([
                        'destination_account_id' => 'Select a different active destination account.',
                    ]);
                }
            }

            /*
             * Validate ledger mappings AND debit/credit direction.
             *
             * Tally import convention:
             * Negative = Debit
             * Positive = Credit
             *
             * Receipt:  Cash Dr / Income Cr
             * Payment:  Expense Dr / Cash Cr
             * Transfer: Destination Dr / Source Cr
             */

            $debitEntry = $entries->first(
                fn ($entry) => (float) $entry->amount < 0
            );

            $creditEntry = $entries->first(
                fn ($entry) => (float) $entry->amount > 0
            );

            if (! $debitEntry || ! $creditEntry) {
                throw ValidationException::withMessages([
                    'voucher' => 'Expected one debit and one credit ledger entry.',
                ]);
            }

            $sourceAccountLedger = match ($type) {
                'receipt' => $debitEntry->ledger_name,
                'payment', 'transfer' => $creditEntry->ledger_name,
            };

            $accountMapped = TallyLedgerMapping::query()
                ->where('is_active', true)
                ->where('tally_ledger_name', $sourceAccountLedger)
                ->where('finance_account_id', $account->id)
                ->exists();

            if (! $accountMapped) {
                throw ValidationException::withMessages([
                    'finance_account_id' =>
                        'Selected account does not match the required debit/credit Tally ledger.',
                ]);
            }

            if ($type === 'transfer') {
                $destinationMapped = TallyLedgerMapping::query()
                    ->where('is_active', true)
                    ->where('tally_ledger_name', $debitEntry->ledger_name)
                    ->where('finance_account_id', $destinationAccountId)
                    ->exists();

                if (! $destinationMapped) {
                    throw ValidationException::withMessages([
                        'destination_account_id' =>
                            'Destination account must match the debited Tally ledger.',
                    ]);
                }
            } else {
                $headLedger = $type === 'receipt'
                    ? $creditEntry->ledger_name
                    : $debitEntry->ledger_name;

                $headMapped = TallyLedgerMapping::query()
                    ->where('is_active', true)
                    ->where('tally_ledger_name', $headLedger)
                    ->where('finance_head_id', $headId)
                    ->exists();

                if (! $headMapped) {
                    throw ValidationException::withMessages([
                        'finance_head_id' =>
                            'Selected finance head does not match the required debit/credit Tally ledger.',
                    ]);
                }
            }


            $date = $source->voucher_date?->format('Y-m-d');

            if (! $date) {
                throw ValidationException::withMessages([
                    'voucher' => 'The imported Tally voucher has no valid date.',
                ]);
            }

            /*
             * Prevent creating an ERP draft when a potentially
             * equivalent ERP Finance voucher already exists.
             *
             * This is deliberately conservative:
             * same date + type + amount requires manual review.
             */

            DB::select(
                'SELECT pg_advisory_xact_lock(hashtext(?))',
                [
                    'tally-draft-check-'
                    . $date . '-'
                    . $type . '-'
                    . number_format($amount, 2, '.', ''),
                ]
            );

            $existingVoucher = FinanceVoucher::query()
                ->whereDate('voucher_date', $date)
                ->where('voucher_type', $type)
                ->where(
                    'amount',
                    number_format($amount, 2, '.', '')
                )
                ->whereIn('status', [
                    'draft',
                    'posted',
                ])
                ->first();

            if ($existingVoucher) {
                throw ValidationException::withMessages([
                    'voucher' =>
                        'A potentially matching ERP voucher already exists: '
                        . $existingVoucher->voucher_no
                        . '. Review and reconcile it before creating another voucher.',
                ]);
            }

            /*
             * Check whether the Tally remote ID is already
             * associated with an ERP export.
             */
            if (filled($source->remote_id)) {
               $existingExport = \App\Models\TallyExport::query()
   ->where('remote_id', $source->remote_id)
    ->first();
                if ($existingExport) {
                    throw ValidationException::withMessages([
                        'voucher' =>
                            'This Tally remote ID is already associated with '
                            . 'an ERP export. Reconcile the existing transaction instead.',
                    ]);
                }
            }

            $voucher = FinanceVoucher::create([
                'voucher_no' => $voucherNumberService->generate(
                    $type,
                    $date
                ),
                'voucher_type' => $type,
                'voucher_date' => $date,
                'finance_head_id' => $headId,
                'finance_account_id' => $account->id,
                'destination_account_id' => $destinationAccountId,
                'amount' => $amount,
                'payment_mode' => $validated['payment_mode'] ?? null,
                'reference_no' => $validated['reference_no']
                    ?? $source->voucher_number,
                'party_name' => $validated['party_name']
                    ?? $source->party_ledger,
                'narration' => $validated['narration']
                    ?? $source->narration,
                'status' => 'draft',
                'created_by' => $userId,
            ]);

            $source->finance_voucher_id = $voucher->id;
            $source->reconciliation_status = 'pending';
            $source->match_method = 'tally_import_draft';
            $source->reconciliation_notes =
                'ERP draft created from imported Tally voucher; posting and reconciliation pending.';
            $source->save();

            return $voucher;
        });

        Log::info('ERP draft created from Tally import', [
            'tally_voucher_id' => $tallyVoucher->id,
            'finance_voucher_id' => $voucher->id,
            'user_id' => $userId,
        ]);

        return redirect()
            ->route('finance.vouchers.show', $voucher)
            ->with(
                'success',
                'ERP Finance voucher created as Draft. Review and post it separately.'
            );
    }

    /**
     * Only simple voucher types are supported initially.
     */
    private function supportedType(
        TallyImportedVoucher $voucher
    ): ?string {
        return match (strtolower(trim((string) $voucher->voucher_type))) {
            'receipt' => 'receipt',
            'payment' => 'payment',
            'contra' => 'transfer',
            default => null,
        };
    }
}