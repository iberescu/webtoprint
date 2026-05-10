<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Vanilo\Order\Models\Order;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => Order::query()
                ->orderByDesc('created_at')
                ->paginate((int) $request->query('per_page', 30)),
        ]);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json($order->load('items'));
    }

    public function mine(Request $request): JsonResponse
    {
        return response()->json([
            'data' => Order::query()
                ->where('user_id', $request->user()->id)
                ->orderByDesc('created_at')
                ->with('items')
                ->paginate(20),
        ]);
    }
}
