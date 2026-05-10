<?php

namespace Modules\Integrations\Infrastructure\BankTransfer;

use Modules\Ecommerce\Domain\Models\Order;
use Modules\Integrations\Domain\Contracts\PaymentGateway;

/**
 * "Bank transfer" gateway: returns the company bank info and marks payment
 * as `pending` until manually reconciled. Order goes into production once
 * an admin marks the payment as paid.
 */
class BankTransferGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'bank_transfer';
    }

    public function createCheckoutSession(Order $order): array
    {
        return [
            'gateway' => 'bank_transfer',
            'instructions' => 'Please transfer the order total to the company bank account.',
            'reference' => $order->number,
            'order_id' => $order->id,
            'amount' => $order->gross_total,
            'currency' => $order->currency,
        ];
    }

    public function handleWebhook(array $headers, string $body): ?array
    {
        return null; // bank transfer is reconciled manually by admin
    }
}
