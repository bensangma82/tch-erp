<?php

return [
    'local_ai_enabled' => (bool) env('ERP_ASSISTANT_LOCAL_AI_ENABLED', false),
    'local_ai_url' => env('ERP_ASSISTANT_LOCAL_AI_URL', 'http://127.0.0.1:11434'),
    'local_ai_model' => env('ERP_ASSISTANT_LOCAL_AI_MODEL', 'qwen2.5:1.5b'),
    'timeout' => 12,
    'samples_per_queue' => 3,
];
