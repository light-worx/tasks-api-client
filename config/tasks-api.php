<?php

return [
    'base_url' => env('TASKS_API_URL', 'https://api.example.com'),

    'client_id' => env('TASKS_API_CLIENT_ID'),

    // MUST NOT be hardcoded. Always load from environment variables.
    'client_secret' => env('TASKS_API_CLIENT_SECRET'),

    'cache_token_minutes' => 55,
];