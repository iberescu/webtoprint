<?php

namespace Shop\Http\Controllers;

use Shop\Actions\PlaceOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Vanilo\Cart\Models\Cart as VaniloCart;

class CheckoutController extends Controller
{
    public function __construct(private readonly PlaceOrder $placeOrder)
    {
    }

    public function placeOrder(Request $request, VaniloCart $cart): JsonResponse
    {
        $cart->load('items.product');

        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.', 'errors' => []], 422);
        }

        $data = $request->validate([
            'shipping_address' => 'nullable|array',
            'billing_address' => 'nullable|array',
            'payment_method' => 'nullable|in:stripe,paypal,bank_transfer,manual_invoice',
        ]);

        $order = $this->placeOrder->execute(
            cart: $cart,
            shipping: $data['shipping_address'] ?? null,
            billing: $data['billing_address'] ?? null,
            extra: array_filter(['payment_method' => $data['payment_method'] ?? null]),
        );

        return response()->json([
            'id' => $order->id,
            'number' => $order->getNumber(),
            'status' => $order->getStatus()->value(),
            'items' => $order->getItems()->map(fn ($i) => [
                'id' => $i->id,
                'product_id' => $i->product_id,
                'product_type' => $i->product_type,
                'quantity' => $i->quantity,
                'price' => $i->price,
                'configuration' => $i->configuration ?? null,
            ])->all(),
        ], 201);
    }
}
