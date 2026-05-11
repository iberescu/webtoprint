<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => [
    'service' => 'Web-to-Print Shop',
    'health' => url('/up'),
    'api' => url('/api/v1'),
]);
