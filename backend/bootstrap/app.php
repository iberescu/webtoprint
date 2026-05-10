<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Exceptions\Renderer as ApiRenderer;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        // The /api/v1 routes are pure JSON APIs (Sanctum tokens, no session
        // CSRF needed). Without this, statefulApi() pulls in session +
        // VerifyCsrfToken and unauthenticated POSTs from the storefront /
        // designer return 419 "CSRF token mismatch".
        $middleware->validateCsrfTokens(except: ['api/*']);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        ApiRenderer::register($exceptions);
    })
    ->withProviders([
        App\Providers\ModulesServiceProvider::class,
    ])
    ->create();
