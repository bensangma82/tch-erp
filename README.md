# TCH ERP Pharmacy Accrual Accounting Patch (review build)

This patch is designed to be copied into the existing TCH ERP Laravel project. It contains:

- `PharmacyAccountingService`: journal posting for GRNs, supplier payments, OPD Pharmacy sales, Pharmacy COGS, supplier returns, and patient returns.
- Controller hooks that invoke accounting inside the same database transaction as each stock/financial transaction.
- IPD final-bill support for Pharmacy sales and Pharmacy-return charge tax treatment, avoiding a second Pharmacy revenue journal at dispensing.
- A migration that creates dedicated input/output GST ledgers and a purchase-price-variance ledger if their account codes are unused.
- `config/pharmacy_accounting.php` and GRN costing aligned with the configured input-GST treatment.
- Feature tests for GRN journal balance, input GST policy, and idempotent posting.

## Important accounting controls

1. `PHARMACY_INPUT_GST_RECOVERABLE` defaults to `false`. In that mode, purchase GST is included in inventory acquisition cost. Set it to `true` only after the accountant confirms the relevant input-tax-credit eligibility and the opening inventory valuation basis is reconciled.
2. The new GST GL accounts have blank Tally ledger mappings by design. Map them to the accountant's actual existing TallyPrime ledgers before exporting any journals.
3. Existing inventory batches may have been valued under the prior taxable-only cost formula. Before enabling production accounting, reconcile/revalue opening inventory consistently with the selected GST policy. This patch does not silently rewrite historical batch valuations.
4. The new migration deliberately does not delete GL accounts on rollback because they may be used by posted journals or Tally mappings.
5. This is a review patch, not a production deployment. Run the full Laravel test suite, inspect the diff, and complete accounting/tax approval before committing or deploying.

## Apply on the MacBook

From the repository root, copy the files in this patch into the same relative paths, allowing the existing modified costing files to remain in place. Then run:

```bash
git diff --check
./vendor/bin/sail artisan migrate --pretend
./vendor/bin/sail test --filter=PharmacyAccountingIntegrityTest
```

Review the migration output before applying it. Do not run migrations on Ubuntu as part of this review step.
