<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\PIM\Domain\Models\Product;
use Vanilo\Cart\Contracts\CartItem as VaniloCartItem;
use Vanilo\Cart\Models\Cart as VaniloCart;

/**
 * Headless cart controller — backed by Vanilo. The storefront identifies a
 * cart by its integer id (returned at create time); we don't need a separate
 * cart token.
 */
class CartController extends Controller
{
    public function create(Request $request): JsonResponse
    {
        $cart = VaniloCart::query()->create([
            'user_id' => optional($request->user())->id,
        ]);
        return response()->json($this->toArray($cart), 201);
    }

    // Laravel resolves {cart} (typed VaniloCart) via implicit model binding.
    // Previously we declared int $cartId, which produced a 500 type mismatch.
    public function show(VaniloCart $cart): JsonResponse
    {
        $cart->load('items.product');
        return response()->json($this->toArray($cart));
    }

    public function addItem(Request $request, VaniloCart $cart): JsonResponse
    {
        $data = $request->validate([
            'product_id' => 'required|uuid|exists:products,id',
            'design_id' => 'nullable|uuid|exists:designs,id',
            'configuration_json' => 'required|array',
            'price_json' => 'required|array',
            'quantity' => 'required|integer|min:1',
        ]);

        /** @var Product $product */
        $product = Product::query()->findOrFail($data['product_id']);

        // The price endpoint returns `gross_price`; previous code read `gross`
        // and silently fell through to 0.
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

        // Vanilo's cart_items table has no `name` column, so the row's name
        // attribute will always be null. Eager-load the morph relation so
        // itemToArray can derive the name from the product.
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
        // Vanilo's cart_items has no name column. Derive it from the morphed
        // product (the model implements Buyable::getName).
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
