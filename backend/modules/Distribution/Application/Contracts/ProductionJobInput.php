<?php

namespace Modules\Distribution\Application\Contracts;

/**
 * Neutral DTO used by any caller (Vanilo adapter, Shopify webhook handler,
 * manual admin action, Magento connector …) to request a production job
 * from the Distribution module.
 *
 * Distribution knows nothing about ecommerce models — it only consumes
 * primitives + JSON + a reference to an artwork File (the only shared
 * platform-level concept).
 */
final class ProductionJobInput
{
    public function __construct(
        /** Caller's stable reference for the parent order (e.g. Vanilo order number, Shopify order id). */
        public readonly string $externalOrderRef,
        /** Caller's stable reference for the order line. */
        public readonly string $externalOrderItemRef,
        /** Source system tag — e.g. "vanilo", "shopify", "magento", "manual". */
        public readonly string $source,
        /** Human-readable product name for the jobsheet. */
        public readonly string $productName,
        /** Frozen product snapshot (PIM produces this; structure is opaque to Distribution). */
        public readonly array $productSnapshot,
        /** Resolved configuration: option_code => value_code. */
        public readonly array $configuration,
        /** Order quantity. */
        public readonly int $quantity,
        /** Optional FileStorage file id pointing at the print-ready artwork PDF. */
        public readonly ?string $artworkFileId,
        /** Customer snapshot — name/email/phone. Plain JSON; no model dep. */
        public readonly array $customerSnapshot = [],
        /** Shipping address snapshot. */
        public readonly array $shippingAddress = [],
        /** Billing address snapshot. */
        public readonly array $billingAddress = [],
        /** Free-form metadata (priority, internal SKUs, …). */
        public readonly array $metadata = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'external_order_ref' => $this->externalOrderRef,
            'external_order_item_ref' => $this->externalOrderItemRef,
            'source' => $this->source,
            'product_name' => $this->productName,
            'product_snapshot' => $this->productSnapshot,
            'configuration' => $this->configuration,
            'quantity' => $this->quantity,
            'artwork_file_id' => $this->artworkFileId,
            'customer' => $this->customerSnapshot,
            'shipping_address' => $this->shippingAddress,
            'billing_address' => $this->billingAddress,
            'metadata' => $this->metadata,
        ];
    }
}
