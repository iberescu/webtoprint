<?php

namespace Modules\Ecommerce\Application\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Designer\Domain\Models\Design;
use Modules\Ecommerce\Events\OrderPlaced;
use Vanilo\Cart\Contracts\Cart as VaniloCart;
use Vanilo\Order\Contracts\Order as VaniloOrder;
use Vanilo\Order\Factories\OrderFactory;

/**
 * Wraps Vanilo's cart→order conversion. We keep our own preflight gate plus
 * the OrderPlaced event (Distribution listens on it), so the rest of the
 * platform doesn't know it's now Vanilo underneath.
 */
class PlaceOrder
{
    public function __construct(private readonly OrderFactory $orderFactory)
    {
    }

    /**
     * @param array<string,mixed>|null $shipping
     * @param array<string,mixed>|null $billing
     * @param array<string,mixed>      $extra (e.g. payment_method, language)
     */
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
                    'shippingAddress' => $shipping,
                    'billpayer' => $billing,
                    ...$extra,
                ], fn ($v) => $v !== null),
                $items,
            );

            OrderPlaced::dispatch($order);

            return $order;
        });
    }

    /**
     * Spec §17: refuse to place orders whose attached design failed preflight.
     * The cart_item carries `configuration['design_id']` set by the storefront.
     */
    private function guardPreflight(VaniloCart $cart): void
    {
        foreach ($cart->getItems() as $item) {
            $designId = data_get($item->configuration, 'design_id');
            if (! $designId) continue;

            $design = Design::query()->find($designId);
            if ($design && $design->preflight_status === 'failed') {
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
}
