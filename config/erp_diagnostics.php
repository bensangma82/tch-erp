<?php

return [
    'log_directory' => storage_path('logs'),
    'state_directory' => storage_path('app/erp-diagnostics'),
    'lookback_days' => 7,
    'max_files' => 6,
    'max_scan_bytes' => 4 * 1024 * 1024,
    'max_file_bytes' => 1024 * 1024,
    'max_incidents' => 100,
];
