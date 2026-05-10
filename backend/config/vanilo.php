<?php

return [
    'foundation' => [
        'models' => [
            // Vanilo will resolve its order/cart/cart_item models here so we can
            // override them per-customer if needed.
            'order' => \Vanilo\Order\Models\Order::class,
            'order_item' => \Vanilo\Order\Models\OrderItem::class,
            'cart' => \Vanilo\Cart\Models\Cart::class,
            'cart_item' => \Vanilo\Cart\Models\CartItem::class,
        ],
    ],

    'user' => [
        'model' => \Modules\Ecommerce\Domain\Models\Customer::class,
        'table' => 'customers',
    ],

    'cart' => [
        'pricing' => [
            // We compute prices in our Pricing engine; Vanilo just stores them.
            'mode' => 'frozen',
        ],
        'preserve_for_user' => true,
    ],

    'order' => [
        'number_generator' => null, // we keep our own number sequence in PlaceOrder
    ],
];
