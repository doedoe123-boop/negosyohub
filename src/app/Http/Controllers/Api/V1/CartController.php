<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AddCartLineRequest;
use App\Http\Requests\Api\V1\SetCartAddressRequest;
use App\Http\Requests\Api\V1\SetShippingOptionRequest;
use App\Http\Requests\Api\V1\UpdateCartLineRequest;
use App\Services\CheckoutDiscountService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Lunar\DataTypes\ShippingOption;
use Lunar\Facades\CartSession;
use Lunar\Models\Cart;
use Lunar\Models\Country;
use Lunar\Models\ProductVariant;

/**
 * Manages the customer's active Lunar cart via CartSession.
 *
 * All operations proxy to Lunar's Cart model methods so that
 * CartSession state and Lunar pipelines (tax, discounts, shipping)
 * are always respected.
 */
class CartController extends Controller
{
    public function __construct(
        private OrderService $orderService,
        private CheckoutDiscountService $checkoutDiscountService
    ) {}

    /**
     * Purchasable-type slug → Eloquent model class.
     *
     * @var array<string, class-string>
     */
    private const PURCHASABLE_TYPES = [
        'product-variant' => ProductVariant::class,
    ];

    /**
     * GET /api/v1/cart
     * Return the current calculated cart.
     */
    public function show(): JsonResponse
    {
        $cart = CartSession::current(calculate: true);

        return response()->json($cart ? $this->cartResource($cart) : null);
    }

    /**
     * POST /api/v1/cart/lines
     * Add a purchasable item to the cart.
     */
    public function addLine(AddCartLineRequest $request): JsonResponse
    {
        $data = $request->validated();

        $modelClass = self::PURCHASABLE_TYPES[$data['purchasable_type']];
        $purchasable = $modelClass::findOrFail($data['purchasable_id']);

        $cart = CartSession::manager();
        $cart = $cart->add($purchasable, $data['quantity'], $data['meta'] ?? []);

        // Persist store_id at cart level so OrderController can find it later.
        if (! empty($data['meta']['store_id'])) {
            $cart->update([
                'meta' => array_merge((array) ($cart->meta ?? []), [
                    'store_id' => (int) $data['meta']['store_id'],
                ]),
            ]);
        }

        return response()->json($this->cartResource($cart->calculate()));
    }

    /**
     * PATCH /api/v1/cart/lines/{lineId}
     * Update the quantity of an existing cart line.
     */
    public function updateLine(UpdateCartLineRequest $request, int $lineId): JsonResponse
    {
        $data = $request->validated();

        $cart = CartSession::manager();
        $cart = $cart->updateLine($lineId, $data['quantity']);

        return response()->json($this->cartResource($cart->calculate()));
    }

    /**
     * DELETE /api/v1/cart/lines/{lineId}
     * Remove a single line from the cart.
     */
    public function removeLine(int $lineId): JsonResponse
    {
        $cart = CartSession::manager();
        $cart = $cart->remove($lineId);

        return response()->json($this->cartResource($cart->calculate()));
    }

    /**
     * DELETE /api/v1/cart
     * Clear every line and forget the cart from session.
     */
    public function clear(): JsonResponse
    {
        // Use current() so we never trigger cart creation.
        // If no cart exists, there is nothing to clear.
        $cart = CartSession::current();

        if ($cart) {
            CartSession::manager()->clear();
        }

        return response()->json(null);
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100'],
        ]);

        $cart = CartSession::current(calculate: true);

        if (! $cart || $cart->lines->isEmpty()) {
            throw ValidationException::withMessages([
                'code' => ['Your cart is empty.'],
            ]);
        }

        $store = $this->orderService->resolveStoreFromCart($cart);
        $cart = $this->checkoutDiscountService->applyToCart($cart, $store, $validated['code']);

        return response()->json($this->cartResource($cart->calculate()));
    }

    public function removeCoupon(): JsonResponse
    {
        $cart = CartSession::current(calculate: true);

        if (! $cart) {
            return response()->json(null);
        }

        $cart = $this->checkoutDiscountService->removeFromCart($cart);

        return response()->json($this->cartResource($cart->calculate()));
    }

    /**
     * GET /api/v1/cart/shipping-options
     * Return available shipping options for the current cart.
     */
    public function shippingOptions(): JsonResponse
    {
        /** @var Collection<ShippingOption> $options */
        $options = CartSession::getShippingOptions();

        return response()->json(
            $options->map(fn (ShippingOption $option): array => [
                'id' => $option->getIdentifier(),
                'name' => $option->getName(),
                'description' => $option->getDescription(),
                'price' => [
                    'amount' => $option->getPrice()->value,
                    'formatted' => '₱'.number_format($option->getPrice()->decimal, 2),
                ],
            ])->values()
        );
    }

    /**
     * POST /api/v1/cart/shipping-option
     * Set the chosen shipping option on the cart.
     */
    public function setShippingOption(SetShippingOptionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $options = CartSession::getShippingOptions();

        $option = $options->first(
            fn (ShippingOption $o): bool => $o->getIdentifier() === $data['shipping_rate_id']
        );

        if (! $option) {
            return response()->json(['message' => 'Shipping option not found.'], 422);
        }

        $cart = CartSession::manager();
        $cart->setShippingOption($option);

        return response()->json($this->cartResource($cart->calculate()));
    }

    /**
     * POST /api/v1/cart/address
     * Set or replace the shipping address on the cart.
     */
    public function setAddress(SetCartAddressRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Resolve ISO2 country code to Lunar's country_id.
        $country = Country::where('iso2', $data['country'])->first();
        unset($data['country']);
        $data['country_id'] = $country?->id;

        $cart = CartSession::manager();
        $cart->setShippingAddress($data);
        $cart->setBillingAddress($data);

        return response()->json(['message' => 'Address saved.']);
    }

    /**
     * Serialise a calculated Cart into the shape the Vue SPA expects.
     *
     * @return array<string, mixed>
     */
    private function cartResource(Cart $cart): array
    {
        $store = null;
        $discountSummary = [
            'discount_amount' => 0,
            'total_after_discount' => $cart->total?->value ?? 0,
            'applied_coupon' => null,
        ];

        try {
            $store = $this->orderService->resolveStoreFromCart($cart);
            $discountSummary = $this->checkoutDiscountService->summarizeCart($cart, $store);
        } catch (\Throwable) {
        }

        // Eager-load product media on each purchasable without re-loading
        // the lines collection (which would wipe calculated price values).
        $cart->lines->each(fn ($line) => $line->purchasable?->load('product.media'));

        return [
            'id' => $cart->id,
            'meta' => $cart->meta,

            'lines' => $cart->lines->sortBy('id')->map(fn ($line): array => [
                'id' => $line->id,
                'quantity' => $line->quantity,
                'purchasable' => [
                    'id' => $line->purchasable?->id,
                    'name' => $line->purchasable?->product?->translateAttribute('name'),
                    'thumbnail' => $line->purchasable?->product?->getFirstMediaUrl('images') ?: null,
                ],
                'unit_price' => [
                    'formatted' => '₱'.number_format($line->unitPrice?->decimal ?? 0, 2),
                ],
                'sub_total' => [
                    'formatted' => '₱'.number_format($line->subTotal?->decimal ?? 0, 2),
                ],
            ])->values(),

            'sub_total' => ['formatted' => '₱'.number_format($cart->subTotal?->decimal ?? 0, 2)],
            'shipping_total' => ['formatted' => '₱'.number_format($cart->shippingTotal?->decimal ?? 0, 2)],
            'tax_total' => ['formatted' => '₱'.number_format($cart->taxTotal?->decimal ?? 0, 2)],
            'discount_total' => [
                'value' => $discountSummary['discount_amount'],
                'formatted' => '₱'.number_format($discountSummary['discount_amount'] / 100, 2),
            ],
            'total' => [
                'value' => $discountSummary['total_after_discount'],
                'formatted' => '₱'.number_format(($discountSummary['total_after_discount'] ?? 0) / 100, 2),
            ],
            'original_total' => [
                'value' => $cart->total?->value ?? 0,
                'formatted' => '₱'.number_format($cart->total?->decimal ?? 0, 2),
            ],
            'applied_coupon' => $discountSummary['applied_coupon'],
        ];
    }
}
