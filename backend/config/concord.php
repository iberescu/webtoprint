<?php

/**
 * Concord modules — none on the print backend after the print/shop split.
 *
 * Ecommerce (Vanilo) now lives in backend-shop, which has its own concord.php
 * registering Cart, Order, Checkout, Payment, Address modules. Print talks to
 * shop via the InternalBridge module (/api/v1/internal/*) and never imports
 * Vanilo classes directly.
 */
return [
    'modules' => [],
    'register_route_models' => false,
];
