<?php

namespace Modules\Integrations\Providers;

use App\Modules\ModuleServiceProvider;
use Modules\Integrations\Domain\Contracts\PaymentGateway;
use Modules\Integrations\Domain\Contracts\PreflightDriver;
use Modules\Integrations\Domain\Services\GatewayManager;
use Modules\Integrations\Domain\Services\PreflightManager;

class IntegrationsServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Integrations';
    }

    protected function path(): string
    {
        return dirname(__DIR__);
    }

    protected function registerBindings(): void
    {
        $this->app->singleton(GatewayManager::class, function ($app) {
            $manager = new GatewayManager($app);
            $manager->register('stripe', \Modules\Integrations\Infrastructure\Stripe\StripeGateway::class);
            $manager->register('paypal', \Modules\Integrations\Infrastructure\PayPal\PayPalGateway::class);
            $manager->register('bank_transfer', \Modules\Integrations\Infrastructure\BankTransfer\BankTransferGateway::class);
            $manager->register('manual_invoice', \Modules\Integrations\Infrastructure\ManualInvoice\ManualInvoiceGateway::class);
            return $manager;
        });

        $this->app->singleton(PreflightManager::class, function ($app) {
            $manager = new PreflightManager($app);
            $manager->register('callas', \Modules\Integrations\Infrastructure\Callas\CallasPreflightDriver::class, isDefault: true);
            return $manager;
        });
    }
}
