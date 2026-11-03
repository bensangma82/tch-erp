<?php

return [
    // Disabled by default. Do not enable until export eligibility for these journals
    // is explicitly excluded and verified in the Tally export path.
    'enabled' => env('MHIS_LEGACY_GL_POSTING_ENABLED', false),
];
