<?php

namespace Shop\Http\Controllers;

use Shop\Services\PrintApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Vanilo\Cart\Contracts\CartItem as VaniloCartItem;
use Vanilo\Cart\Models\Cart as VaniloCart;

class CartController extends Controller
{
    public function __construct(private readonly PrintApi $print)
    {
    }

    public function create(Request $request): JsonResponse
    {
        $cart = VaniloCart::query()->create([
            'user_id' => optional($request->user())->id,
        ]);
        return response()->json($this->toArray($cart), 201);
    }

    public function show(VaniloCart $cart): JsonResponse
    {
        $cart->load('items.product');
        return response()->json($this->toArray($cart));
    }

    public function addItem(Request $request, VaniloCart $cart): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|uuid',
            'design_id' => 'nullable|uuid',
            'configuration_json' => 'required|array',
            'price_json' => 'required|array',
            'quantity' => 'required|integer|min:1',
        ]);

        // Validate the product exists on print AND cache locally so the
        // morph relation has a row to point at.
        $product = $this->print->resolveProduct($data['product_id']);

        $gross = (float) ($data['price_json']['gross_price'] ?? $data['price_json']['gross'] ?? 0);
        $unit = $gross / max(1, (int) $data['quantity']);
        $product->priceOverride($unit);

        $item = $cart->addItem($product, $data['quantity'], [
            'attributes' => [
                'configuration' => [
                    'design_id' => $data['design_id'] ?? null,
                    'configuration' => $data['configuration_json'],
                    'price' => $data['price_json'],
                ],
            ],
        ]);

        $item->load('product');

        return response()->json($this->itemToArray($item), 201);
    }

    public function removeItem(VaniloCart $cart, int $item): JsonResponse
    {
        $cart->items()->where('id', $item)->delete();
        return response()->json(null, 204);
    }

    private function toArray(VaniloCart $cart): array
    {
        return [
            'id' => $cart->id,
            'user_id' => $cart->user_id,
            'items' => $cart->items->map(fn ($i) => $this->itemToArray($i))->all(),
            'totals' => [
                'gross' => $cart->itemsTotal(),
                'currency' => config('vanilo.foundation.currency.code', 'EUR'),
            ],
        ];
    }

    private function itemToArray(VaniloCartItem $item): array
    {
        $product = $item->product;
        $name = $product && method_exists($product, 'getName')
            ? $product->getName()
            : ($product?->name ?? 'Item');

        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'product_type' => $item->product_type,
            'name' => $name,
            'quantity' => $item->quantity,
            'unit_price' => $item->price,
            'total' => $item->total(),
            'configuration' => $item->configuration ?? [],
        ];
    }
}
