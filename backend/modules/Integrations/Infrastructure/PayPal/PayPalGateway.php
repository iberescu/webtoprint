<?php

namespace Modules\Integrations\Infrastructure\PayPal;

use Modules\Ecommerce\Domain\Models\Order;
use Modules\Integrations\Domain\Contracts\PaymentGateway;

class PayPalGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'paypal';
    }

    public function createCheckoutSession(Order $order): array
    {
        return [
            'gateway' => 'paypal',
            'redirect_url' => config('app.url') . "/storefront/payment/paypal?order={$order->number}",
            'order_id' => $order->id,
            'amount' => $order->gross_total,
            'currency' => $order->currency,
        ];
    }

    public function handleWebhook(array $headers, string $body): ?array
    {
        $payload = json_decode($body, true) ?: [];
        return [
            'gateway' => 'paypal',
            'event' => $payload['event_type'] ?? 'unknown',
            'data' => $payload,
        ];
    }
}
