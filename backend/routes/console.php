<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment('"Print is forever." — Anon.');
})->purpose('Display an inspiring quote.');
