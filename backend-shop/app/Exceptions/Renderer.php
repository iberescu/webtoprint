<?php

namespace Shop\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Centralised error renderer producing the spec §14 error envelope.
 */
class Renderer
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return self::render($e);
        });
    }

    private static function render(Throwable $e)
    {
        if ($e instanceof ValidationException) {
            $errors = [];
            foreach ($e->errors() as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $errors[] = [
                        'field' => $field,
                        'code' => 'validation_error',
                        'message' => $message,
                    ];
                }
            }
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $errors,
            ], 422);
        }

        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'message' => 'Resource not found.',
                'errors' => [],
            ], 404);
        }

        if ($e instanceof AuthenticationException) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'errors' => [],
            ], 401);
        }

        if ($e instanceof AuthorizationException) {
            return response()->json([
                'message' => 'Forbidden.',
                'errors' => [],
            ], 403);
        }

        if ($e instanceof HttpExceptionInterface) {
            return response()->json([
                'message' => $e->getMessage() ?: 'HTTP error.',
                'errors' => [],
            ], $e->getStatusCode());
        }

        $debug = config('app.debug');
        return response()->json([
            'message' => $debug ? $e->getMessage() : 'Server error.',
            'errors' => [],
        ], 500);
    }
}
