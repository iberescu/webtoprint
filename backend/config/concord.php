<?php

/**
 * Vanilo modules used by the platform's Ecommerce adapter.
 *
 * If you swap Vanilo for Shopify/Magento, drop this file (or comment all out)
 * and disable the EcommerceServiceProvider — the print-domain modules don't
 * depend on any of these.
 */
return [
    'modules' => [
        // Only the modules we actually use. Foundation, Properties, MasterProduct
        // ship Vanilo's own Product / MasterProduct schema which would clash
        // with PIM's products table.
        Konekt\Address\Providers\ModuleServiceProvider::class,
        Vanilo\Cart\Providers\ModuleServiceProvider::class,
        Vanilo\Order\Providers\ModuleServiceProvider::class,
        Vanilo\Checkout\Providers\ModuleServiceProvider::class,
        Vanilo\Payment\Providers\ModuleServiceProvider::class,
    ],
    'register_route_models' => true,
];
