<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Shop\Exceptions\Renderer as ApiRenderer;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: ['api/*']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        ApiRenderer::register($exceptions);
    })
    ->withProviders([
        // Concord boots Vanilo (cart, order, checkout, payment, address).
        Konekt\Concord\ConcordServiceProvider::class,
        Shop\Providers\AppServiceProvider::class,
    ])
    ->create();
