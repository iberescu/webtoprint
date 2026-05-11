<?php

return [
    'foundation' => [
        'models' => [
            'order' => \Vanilo\Order\Models\Order::class,
            'order_item' => \Vanilo\Order\Models\OrderItem::class,
            'cart' => \Vanilo\Cart\Models\Cart::class,
            'cart_item' => \Vanilo\Cart\Models\CartItem::class,
        ],
    ],

    'user' => [
        'model' => \Shop\Models\Customer::class,
        'table' => 'customers',
    ],

    'cart' => [
        'pricing' => ['mode' => 'frozen'],
        'preserve_for_user' => true,
    ],

    'order' => [
        'number_generator' => null,
    ],
];
