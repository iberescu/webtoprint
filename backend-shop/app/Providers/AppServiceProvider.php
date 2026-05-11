<?php

namespace Shop\Providers;

use Shop\Events\OrderPlaced;
use Shop\Listeners\HandOffOrderToPrint;
use Shop\Services\PrintApi;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PrintApi::class, fn () => PrintApi::fromConfig());
    }

    public function boot(): void
    {
        Event::listen(OrderPlaced::class, HandOffOrderToPrint::class);
    }
}
