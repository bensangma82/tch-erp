<?php

return [
    // Disabled by default. Never enable on the hospital Ubuntu server.
    'allow_dummy_execution' => env('TCH_ALLOW_DUMMY_CANCELLATION_EXECUTION', false),
    // Never enable on production. Does not physically refund money.
    'allow_dummy_refund' => env('TCH_ALLOW_DUMMY_INVESTIGATION_REFUND', false),
    'enable_verified_refunds' => env('TCH_ENABLE_VERIFIED_INVESTIGATION_REFUNDS', false),
];
