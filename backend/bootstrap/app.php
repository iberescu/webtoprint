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
        // Trust the aggregator nginx + ngrok-agent proxy chain so that
        // X-Forwarded-Proto: https is honoured. Without this Laravel
        // generates http:// asset URLs while the page is on https://,
        // which the browser blocks as mixed content — Filament's
        // sidebar/tables JS then never loads, surfacing as
        // "Cannot read properties of undefined (reading 'isOpen')".
        $middleware->trustProxies(at: '*');

        $middleware->statefulApi();
        // The /api/v1 routes are pure JSON APIs (Sanctum tokens, no session
        // CSRF needed). Without this, statefulApi() pulls in session +
        // VerifyCsrfToken and unauthenticated POSTs from the storefront /
        // designer return 419 "CSRF token mismatch".
        $middleware->validateCsrfTokens(except: ['api/*']);

        // Stop the Authenticate middleware from redirecting unauthenticated
        // /api/v1/* clients to a non-existent `login` route (which would
        // surface as a 500 "Route [login] not defined" instead of a clean
        // 401). Returning null makes it throw AuthenticationException, which
        // our Renderer maps to the spec §14 401 envelope.
        $middleware->redirectGuestsTo(
            fn (Illuminate\Http\Request $request) => $request->is('api/*') ? null : route('filament.admin.auth.login'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        ApiRenderer::register($exceptions);
    })
    ->withProviders([
        App\Providers\ModulesServiceProvider::class,
    ])
    ->create();
