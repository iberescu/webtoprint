<?php

namespace Modules\InternalBridge\Providers;

use App\Modules\ModuleServiceProvider;

/**
 * Internal-only HTTP API used by sibling services (the shop) to talk to
 * the print backend.
 *
 *  - GET  /api/v1/internal/products/{id}              — product + snapshot
 *  - GET  /api/v1/internal/designs/{id}/preflight     — preflight + pdf id
 *  - POST /api/v1/internal/production-jobs/batch      — accept a batch of
 *                                                       ProductionJobInput-
 *                                                       shaped jobs.
 *
 * Auth: a static bearer token from config('internal_bridge.token'). It's
 * simple, predictable, and good enough for a backend-to-backend trust
 * boundary on a private network.
 */
class InternalBridgeServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'InternalBridge';
    }

    protected function path(): string
    {
        return dirname(__DIR__);
    }
}
