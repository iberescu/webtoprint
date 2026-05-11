<?php

namespace Modules\InternalBridge\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyInternalToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('internalbridge.token');
        $provided = $request->bearerToken() ?? $request->header('X-Internal-Token');

        if (!$expected || !$provided || !hash_equals($expected, $provided)) {
            return response()->json(['message' => 'internal: unauthorized'], 401);
        }

        return $next($request);
    }
}
