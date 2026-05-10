<?php

namespace Modules\Integrations\Infrastructure\Stripe;

use Modules\Ecommerce\Domain\Models\Order;
use Modules\Integrations\Domain\Contracts\PaymentGateway;

/**
 * Stripe gateway scaffolding. Real implementation wires the Stripe SDK
 * in createCheckoutSession() and validates webhooks against the secret.
 */
class StripeGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'stripe';
    }

    public function createCheckoutSession(Order $order): array
    {
        // TODO: wire stripe-php — return real checkout URL.
        return [
            'gateway' => 'stripe',
            'redirect_url' => config('app.url') . "/storefront/payment/stripe?order={$order->number}",
            'order_id' => $order->id,
            'amount' => $order->gross_total,
            'currency' => $order->currency,
        ];
    }

    public function handleWebhook(array $headers, string $body): ?array
    {
        // TODO: verify signature with config('services.stripe.webhook_secret').
        $payload = json_decode($body, true) ?: [];
        return [
            'gateway' => 'stripe',
            'event' => $payload['type'] ?? 'unknown',
            'data' => $payload,
        ];
    }
}
