<?php

namespace Modules\Integrations\Domain\Contracts;

use Modules\Ecommerce\Domain\Models\Order;

/**
 * One implementation per payment provider.
 * The order/payment lifecycle is managed by Ecommerce; the gateway only
 * deals with provider-specific session creation and webhook normalisation.
 */
interface PaymentGateway
{
    /** Provider key used in DB rows: 'stripe' | 'paypal' | 'bank_transfer' | 'manual_invoice' */
    public function key(): string;

    /** Create a checkout session and return the URL the storefront should redirect to. */
    public function createCheckoutSession(Order $order): array;

    /** Verify and parse an inbound webhook payload. Returns normalised event data or null. */
    public function handleWebhook(array $headers, string $body): ?array;
}
