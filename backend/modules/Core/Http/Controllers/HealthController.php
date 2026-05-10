<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function show(): JsonResponse
    {
        $checks = [
            'database' => $this->ping(fn () => DB::select('select 1')),
            'redis' => $this->ping(fn () => Redis::connection()->ping()),
        ];

        $ok = ! in_array(false, $checks, true);

        return response()->json([
            'status' => $ok ? 'ok' : 'degraded',
            'name' => config('app.name'),
            'version' => 'v1',
            'checks' => $checks,
        ], $ok ? 200 : 503);
    }

    private function ping(callable $probe): bool
    {
        try {
            $probe();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
