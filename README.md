# TCH ERP — Pharmacy advance / approved MHIS finalisation correction

## Scope
- Final bill journal posts first.
- Validated posted advances are then applied to GL 1200 by Dr 2100 / Cr 1200.
- Only after that, approved MHIS recognition executes.
- All stages remain inside the existing DB transaction; exceptions roll back the bill and new accounting journals.
- MHIS Free advances remain prohibited. Other complex adjustments remain blocked.
- Source validation accepts only ordinary advances, `pharmacy_sale` and `pharmacy_admission`; tied to the same patient/admission.
- A receipt must have exactly one posted journal with exact cash/UPI/card and GL2100 entries; duplicates/mismatches blocked.
- Existing application journals are validated for exact amount and GL2100/1200 before idempotent return.

## Important validation status
- PHP syntax checked on the three modified files.
- Full Laravel PHPUnit/feature tests **not run**: the uploaded source archive contained only these three PHP files, not a working Laravel project or database.
- No hospital server files or records were changed by patch preparation.

## On MacBook (development branch, not Ubuntu)
1. From your ERP root, verify a clean Git working tree and current branch.
2. Extract this zip at ERP root, e.g. `unzip -o ~/Downloads/tch_mhis_pharmacy_finalization_patch.zip -d .`
3. Run `php -l` on the three files or `./vendor/bin/sail exec laravel.test php -l ...` as applicable.
4. Run focused application tests in the MacBook Laravel environment, including MHIS advance reconciliation and billing finalisation.
5. Browser acceptance: approved MHIS + legitimate pharmacy advance; verify three journals and account balance. Also check an MHIS Free patient advance remains rejected, duplicate advance application does not occur, wrong-source and mismatched receipt journals are rejected.
6. Commit/push development branch only when tests and browser acceptance pass. Do not deploy to Ubuntu until reviewed and approved; verify fresh PostgreSQL backup first.

## Expected for billing account #29 (only if a real authorized financial transaction)
- Existing receipt (unchanged): JRN-IPADV-14 — Dr GL1100 ₹427.80, Cr GL2100 ₹427.80.
- On finalisation: JRN-IPAPPLY-29 — Dr GL2100 ₹427.80, Cr GL1200 ₹427.80.
- MHIS approved claim: Dr GL1210 ₹1,000.00, Cr GL1200 ₹1,000.00.
- Exact final bill/revenue journal depends on billing service and applicable revenue GL mapping.
