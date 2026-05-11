<?php

namespace Shop\Actions;

use Shop\Events\OrderPlaced;
use Shop\Services\PrintApi;
use Illuminate\Support\Facades\DB;
use Vanilo\Cart\Contracts\Cart as VaniloCart;
use Vanilo\Order\Contracts\Order as VaniloOrder;
use Vanilo\Order\Factories\OrderFactory;

/**
 * Wraps Vanilo's cart→order conversion. Stays here on the shop side
 * (no print-domain dependencies); print is informed via the OrderPlaced
 * event handled by HandOffOrderToPrint.
 */
class PlaceOrder
{
    public function __construct(
        private readonly OrderFactory $orderFactory,
        private readonly PrintApi $print,
    ) {
    }

    public function execute(VaniloCart $cart, ?array $shipping = null, ?array $billing = null, array $extra = []): VaniloOrder
    {
        return DB::transaction(function () use ($cart, $shipping, $billing, $extra) {
            $this->guardPreflight($cart);

            $items = $cart->getItems()->map(fn ($item) => [
                'product_type' => $item->product_type,
                'product_id' => $item->product_id,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'configuration' => $item->configuration,
            ])->all();

            $order = $this->orderFactory->createFromDataArray(
                array_filter([
                    'number' => $this->nextOrderNumber(),
                    'user_id' => $cart->user_id,
                    'shippingAddress' => $this->toVaniloAddress($shipping),
                    'billpayer' => $this->toVaniloBillpayer($billing),
                    ...$extra,
                ], fn ($v) => $v !== null),
                $items,
            );

            OrderPlaced::dispatch($order);

            return $order;
        });
    }

    private function guardPreflight(VaniloCart $cart): void
    {
        foreach ($cart->getItems() as $item) {
            $designId = data_get($item->configuration, 'design_id');
            if (!$designId) continue;

            $info = $this->print->designPreflight($designId);
            if ($info && ($info['preflight_status'] ?? null) === 'failed') {
                throw new \DomainException(
                    "Item refused: artwork {$designId} failed preflight."
                );
            }
        }
    }

    private function nextOrderNumber(): string
    {
        $year = now()->format('Y');
        $orderClass = config('vanilo.foundation.models.order', \Vanilo\Order\Models\Order::class);
        $count = $orderClass::query()->whereYear('created_at', $year)->count() + 1;
        return sprintf('%s-%06d', $year, $count);
    }

    private function toVaniloAddress(?array $a): ?array
    {
        if (!$a) return null;
        return array_filter([
            'name' => $a['name'] ?? null,
            'address' => $a['line1'] ?? $a['address'] ?? null,
            'city' => $a['city'] ?? null,
            'postalcode' => $a['postalcode'] ?? $a['postal_code'] ?? null,
            'country_id' => $a['country_code'] ?? $a['country_id'] ?? null,
        ], fn ($v) => $v !== null);
    }

    private function toVaniloBillpayer(?array $b): ?array
    {
        if (!$b) return null;
        $parts = preg_split('/\s+/', trim((string) ($b['name'] ?? '')), 2) ?: [];
        return array_filter([
            'firstname' => $parts[0] ?? null,
            'lastname' => $parts[1] ?? '-',
            'email' => $b['email'] ?? null,
            'address' => $this->toVaniloAddress($b),
        ], fn ($v) => $v !== null);
    }
}
