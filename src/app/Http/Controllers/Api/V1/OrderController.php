<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlaceOrderRequest;
use App\Models\Order;
use App\Models\Store;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Lunar\Facades\CartSession;

/**
 * Thin HTTP handler for customer order operations.
 *
 * Role-scoped querying, validation, and commission logic
 * all live in OrderService.
 *
 * @see /skills/order-processing.md
 */
class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    /**
     * List orders visible to the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->orderService->listForUser($request->user())
        );
    }

    /**
     * Show a single order with its summary.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        $order->load(['store', 'lines', 'addresses']);

        return response()->json([
            'order' => $order,
            'summary' => $this->orderService->summarize($order),
        ]);
    }

    /**
     * Place a new order from the current Lunar cart session.
     */
    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $store = Store::query()->findOrFail($request->validated('store_id'));

        $order = $this->orderService->createFromCart(
            CartSession::current(),
            $store
        );

        return response()->json([
            'message' => 'Order placed successfully.',
            'order' => $order,
            'summary' => $this->orderService->summarize($order),
        ], 201);
    }
}
