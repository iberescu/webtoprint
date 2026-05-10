<?php

namespace Modules\Integrations\Infrastructure\ManualInvoice;

use Modules\Ecommerce\Domain\Models\Order;
use Modules\Integrations\Domain\Contracts\PaymentGateway;

/**
 * "Manual invoice" — used for B2B accounts. The order moves to production
 * before payment; the invoice goes out separately on terms.
 */
class ManualInvoiceGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'manual_invoice';
    }

    public function createCheckoutSession(Order $order): array
    {
        return [
            'gateway' => 'manual_invoice',
            'instructions' => 'An invoice will be issued separately.',
            'reference' => $order->number,
            'order_id' => $order->id,
            'amount' => $order->gross_total,
            'currency' => $order->currency,
        ];
    }

    public function handleWebhook(array $headers, string $body): ?array
    {
        return null;
    }
}
