<?php

return [
    'url' => env(
        'TALLY_URL',
        'http://host.docker.internal:9000'
    ),

    'timeout' => (int) env(
        'TALLY_TIMEOUT',
        10
    ),

    'company' => env(
        'TALLY_COMPANY',
        'Tura Christian Hospital ERP -Test'
    ),
    'journal_export_enabled' => env(
    'TALLY_JOURNAL_EXPORT_ENABLED',
    false
),
];
