<?php

return [
    'print' => [
        'base_url' => env('PRINT_API_URL', 'http://wtp-backend:8000/api/v1'),
        'token' => env('INTERNAL_BRIDGE_TOKEN', 'dev-internal-token-CHANGE-ME'),
    ],
];
