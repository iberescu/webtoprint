<?php

return [
    'driver' => env('SESSION_DRIVER', 'array'),
    'lifetime' => 120,
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => storage_path('framework/sessions'),
    'cookie' => 'wtp_shop_session',
    'path' => '/',
    'http_only' => true,
    'same_site' => 'lax',
];
