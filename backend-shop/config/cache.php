<?php

return [
    'default' => env('CACHE_STORE', 'array'),
    'stores' => [
        'array' => ['driver' => 'array'],
        'file' => ['driver' => 'file', 'path' => storage_path('framework/cache/data')],
    ],
];
