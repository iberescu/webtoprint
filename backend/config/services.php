<?php

return [
    'meilisearch' => [
        'host' => env('MEILI_HOST'),
        'key' => env('MEILI_KEY'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'mode' => env('PAYPAL_MODE', 'sandbox'),
    ],

    'callas' => [
        'bin' => env('CALLAS_BIN'),
        'license_key' => env('CALLAS_LICENSE_KEY'),
    ],

    'pdf' => [
        'ghostscript_bin' => env('GHOSTSCRIPT_BIN', 'gs'),
        'qpdf_bin' => env('QPDF_BIN', 'qpdf'),
    ],
];
