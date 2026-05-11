<?php

return [
    'modules' => [
        Konekt\Address\Providers\ModuleServiceProvider::class,
        Vanilo\Cart\Providers\ModuleServiceProvider::class,
        Vanilo\Order\Providers\ModuleServiceProvider::class,
        Vanilo\Checkout\Providers\ModuleServiceProvider::class,
        Vanilo\Payment\Providers\ModuleServiceProvider::class,
    ],
    'register_route_models' => true,
];
