<?php

namespace App\Services\Diagnostics;

final class DiagnosticCatalog
{
    /** Only these two local maintenance operations are executable. No command text comes from a request or a log. */
    public const COMMANDS = ['views_clear' => 'view:clear', 'routes_clear' => 'route:clear'];

    public function classify(string $text): array
    {
        $s = strtolower($text);
        $header = strtolower(strtok($text, "\r\n") ?: '');
        $rules = [
            ['payroll_empty', str_contains($header, 'a payroll run with no employees cannot be approved.'), 'Payroll approval blocked by an empty run', 'Payroll', 'review', 'Open the payroll run and check that eligible employees have active salary structures covering the payroll period. Recalculate and review the entries before approving.', null, 'admin.hr.payroll.runs.index'],
            ['immutable_journal', str_contains($s,'posted or reversed journal entries cannot be modified'), 'Posted journal protected', 'Accounting', 'review', 'Use the approved reversal or correction workflow. Posted journals must remain unchanged.', null, 'finance.journals.index'],
            ['mhis_variance', str_contains($s,'maker-checker finance authorisation') || str_contains($s,'mhis package variance'), 'MHIS variance needs Finance review', 'MHIS', 'review', 'Open the variance review, verify the recorded amounts and obtain independent Finance approval.', null, 'finance.mhis-package-variance.index'],
            ['cancelled_pair', str_contains($s,'cancelled receipt pair'), 'Cancelled receipt pair excluded', 'Tally', 'review', 'Review the cancellation pair in journal exports. An excluded pair must not be forced into a new batch.', null, 'finance.tally.journals.index'],
            ['refund_review', str_contains($s,'finance reconciliation required') || str_contains($s,'tally-linked accounting') || str_contains($s,'independent verifier required'), 'Refund or accounting review required', 'Refunds / accounting', 'review', 'Open the refund desk or reconciliation screen and follow its specific review message. No payment, journal or export is changed here.', null, 'billing.investigation-refunds.verification'],
            ['duplicate_export', str_contains($s,'already has a tally export') || str_contains($s,'unresolved transmission') || str_contains($s,'outcome uncertain'), 'Tally transmission needs reconciliation', 'Tally', 'review', 'Reconcile the existing export before considering recovery. This page does not resend vouchers or reset export status.', null, 'finance.tally.reconciliation'],
            ['tally_connection', (str_contains($s,'tally') || str_contains($s,':9000')) && (str_contains($s,'curl error 7') || str_contains($s,'could not connect') || str_contains($s,'unable to connect')), 'Tally connection unavailable', 'Tally', 'error', 'Start TallyPrime and its HTTP service, check the configured network connection, then run the live connection check. Reconcile uncertain exports separately.', null, 'finance.tally.index'],
            ['tally_timeout', (str_contains($s,'tally') || str_contains($s,':9000')) && (str_contains($s,'curl error 28') || str_contains($s,'timed out')), 'Tally request timed out', 'Tally', 'review', 'Check connectivity. A timeout can leave an uncertain transmission outcome; use reconciliation before any retry.', null, 'finance.tally.reconciliation'],
            ['database_schema', str_contains($s,'42p01') || str_contains($s,'42703') || str_contains($s,'undefined table') || str_contains($s,'undefined column'), 'Database schema is missing or out of date', 'Database', 'error', 'Review pending migrations and confirm that this deployment uses the correct database. Apply reviewed migrations through the deployment process; this page does not run them.', null, null],
            ['database_connection', str_contains($s,'sqlstate[080') || str_contains($s,'sqlstate[28p01]') || str_contains($s,'connection refused') || str_contains($s,'password authentication failed'), 'Database or service connection failed', 'Infrastructure', 'error', 'Check the database service, network and deployment configuration. Credentials are never displayed or edited here.', null, null],
            ['database_conflict', str_contains($s,'40p01') || str_contains($s,'40001') || str_contains($s,'deadlock detected'), 'Concurrent transaction conflict', 'Database', 'review', 'Check whether the original operation completed before retrying through its normal screen. Do not repeat a payment or posting blindly.', null, null],
            ['duplicate_record', str_contains($s,'23505') || str_contains($s,'duplicate key value'), 'Duplicate record prevented', 'Data integrity', 'review', 'Find the existing record in the originating module. Do not remove unique constraints or recreate financial transactions.', null, null],
            ['storage_access', str_contains($s,'permission denied') || str_contains($s,'failed to open stream') || str_contains($s,'no space left'), 'Storage access or disk space problem', 'Infrastructure', 'error', 'Review runtime directory permissions and free disk space with the server administrator. No automatic chmod or file deletion is performed.', null, null],
            ['view_runtime', str_contains($s,'storage/framework/views') || str_contains($s,'viewexception') || str_contains($s,'view [') && str_contains($s,'not found'), 'View rendering error', 'Interface', 'error', 'Clear compiled views, then reopen the affected screen. If the error recurs, its source template needs a code correction; clearing cache alone cannot fix invalid source.', 'views_clear', null],
            ['route_runtime', str_contains($s,'routenotfoundexception') || (str_contains($s,'route [') && str_contains($s,'not defined')), 'Route unavailable', 'Navigation', 'error', 'Clear stale route cache, then reopen the affected screen. A genuinely missing route needs a reviewed code deployment.', 'routes_clear', null],
        ];
        foreach ($rules as [$code,$matches,$title,$module,$severity,$solution,$action,$route]) {
            if ($matches) return compact('code','title','module','severity','solution','action','route');
        }
        return ['code'=>'unclassified','title'=>'Error needs technical review','module'=>'ERP','severity'=>'error',
            'solution'=>'Use the incident identifier, time and source location to investigate the original server log. No reliable automatic repair is known for this error.', 'action'=>null,'route'=>null];
    }

    public function workflow(array $incident): ?array
    {
        $route = $incident['route'] ?? match ($incident['file'] ?? null) {
            'app/Http/Controllers/Hr/PayrollRunController.php' => 'admin.hr.payroll.runs.index',
            'app/Http/Controllers/Hr/EmployeeSalaryStructureController.php' => 'admin.hr.payroll.salary-structures.index',
            'app/Http/Controllers/Finance/TallyExportController.php' => 'finance.tally.exports.index',
            'app/Http/Controllers/Finance/TallyJournalExportController.php' => 'finance.tally.journals.index',
            'app/Http/Controllers/Finance/FinanceVoucherController.php' => 'finance.vouchers.index',
            'app/Http/Controllers/Finance/FinanceMasterController.php' => 'finance.master.index',
            default => null,
        };

        if ($route === null) {
            return null;
        }

        $label = match ($route) {
            'admin.hr.payroll.runs.index' => 'Open Payroll Runs',
            'admin.hr.payroll.salary-structures.index' => 'Open Salary Structures',
            'finance.tally.exports.index' => 'Open Tally Voucher Export',
            'finance.tally.journals.index' => 'Open Tally Journal Export',
            'finance.tally.reconciliation' => 'Open Tally Reconciliation',
            'finance.tally.index' => 'Open Tally Integration',
            'finance.vouchers.index' => 'Open Finance Vouchers',
            'finance.master.index' => 'Open Finance Master',
            'finance.journals.index' => 'Open GL Journals',
            'finance.mhis-package-variance.index' => 'Open MHIS Variance Review',
            'billing.investigation-refunds.verification' => 'Open Refund Verification',
            default => 'Open review screen',
        };

        return compact('route', 'label');
    }
}
