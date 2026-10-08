<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Services\Tally\TallyImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
}
