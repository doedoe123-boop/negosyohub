<?php

namespace App\Services;

use App\CouponScope;
use App\Models\Coupon;
use App\Models\Order;
use App\OrderPaymentMethod;
use App\OrderPaymentStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Lunar\Facades\ShippingManifest;
use Lunar\Models\Cart;
use Lunar\Models\OrderLine;

class MultiStoreCheckoutService
{
    public function __construct(
        private MarketplaceCartService $marketplaceCartService,
        private OrderService $orderService,
        private CommissionService $commissionService
    ) {}

    /**
     * @return array{
     *     checkout_group_id: string,
     *     orders: Collection<int, Order>,
     *     order_ids: list<int>,
     *     first_order_id: int,
     *     applied_coupon: array<string, mixed>|null
     * }
     */
    public function placeCashOnDelivery(Cart $cart): array
    {
        $orders = $this->createOrdersFromCart(
            $cart,
            paymentMethod: OrderPaymentMethod::CashOnDelivery->value,
            paymentStatus: OrderPaymentStatus::Unpaid->value,
            paidAt: null,
            paymentIntentId: null,
        );

        return $this->formatCheckoutResult($orders);
    }

    /**
     * @return array{
     *     checkout_group_id: string,
     *     orders: Collection<int, Order>,
     *     order_ids: list<int>,
     *     first_order_id: int,
     *     applied_coupon: array<string, mixed>|null
     * }
     */
    public function completePayPal(Cart $cart, string $paypalOrderId, string $captureReference): array
    {
        $orders = $this->createOrdersFromCart(
            $cart,
            paymentMethod: OrderPaymentMethod::PayPal->value,
            paymentStatus: OrderPaymentStatus::Paid->value,
            paidAt: now(),
            paymentIntentId: $paypalOrderId,
        );

        $orders->each(function (Order $order) use ($captureReference): void {
            $this->orderService->recordPaymentCapture(
                $order,
                driver: OrderPaymentMethod::PayPal->value,
                reference: $captureReference,
                status: 'COMPLETED',
            );
        });

        return $this->formatCheckoutResult($orders);
    }

    /**
     * @return Collection<int, Order>
     */
    private function createOrdersFromCart(
        Cart $sourceCart,
        string $paymentMethod,
        string $paymentStatus,
        mixed $paidAt,
        ?string $paymentIntentId,
    ): Collection {
        $sourceCart = $sourceCart->calculate();
        $this->orderService->validateCart($sourceCart);

        $storeGroups = $this->marketplaceCartService->groupCartByStore($sourceCart);

        if ($storeGroups->count() < 2) {
            throw ValidationException::withMessages([
                'cart' => ['Multi-store checkout requires items from at least two stores.'],
            ]);
        }

        $appliedCoupon = $this->validatedAppliedCoupon($sourceCart);
        $checkoutGroupId = (string) Str::uuid();
        $orders = collect();

        DB::transaction(function () use (
            $storeGroups,
            $sourceCart,
            $checkoutGroupId,
            $paymentMethod,
            $paymentStatus,
            $paidAt,
            $paymentIntentId,
            $orders
        ): void {
            foreach ($storeGroups as $group) {
                $store = $group['store'];
                $this->orderService->validateStore($store);

                $splitCart = $this->buildStoreSpecificCart(
                    $sourceCart,
                    $group['lines'],
                    $checkoutGroupId,
                    $store->id,
                );

                $order = $this->orderService->createFromCart($splitCart, $store, [
                    'payment_method' => $paymentMethod,
                    'payment_status' => $paymentStatus,
                    'paid_at' => $paidAt,
                    'payment_intent_id' => $paymentIntentId,
                    'meta' => array_merge((array) ($splitCart->meta ?? []), [
                        'checkout_group_id' => $checkoutGroupId,
                        'store_id' => $store->id,
                        'store_name' => $store->name,
                    ]),
                ]);

                $orders->push($order->load('lines'));
            }

            $this->alignOrderTotalsWithMarketplaceCart($orders, $sourceCart, $appliedCoupon, $checkoutGroupId);
        });

        return $orders->map(fn (Order $order): Order => $order->refresh()->load('store', 'latestShipment'));
    }

    /**
     * @param  Collection<int, mixed>  $lines
     */
    private function buildStoreSpecificCart(Cart $sourceCart, Collection $lines, string $checkoutGroupId, int $storeId): Cart
    {
        $splitCart = Cart::query()->create([
            'user_id' => $sourceCart->user_id,
            'customer_id' => $sourceCart->customer_id,
            'currency_id' => $sourceCart->currency_id,
            'channel_id' => $sourceCart->channel_id,
            'meta' => [
                'checkout_group_id' => $checkoutGroupId,
                'source_cart_id' => $sourceCart->id,
                'store_id' => $storeId,
            ],
        ]);

        foreach ($sourceCart->addresses as $address) {
            $splitCart->addresses()->create($address->only([
                'country_id',
                'title',
                'first_name',
                'last_name',
                'company_name',
                'line_one',
                'line_two',
                'line_three',
                'city',
                'state',
                'postcode',
                'delivery_instructions',
                'contact_email',
                'contact_phone',
                'type',
                'shipping_option',
                'meta',
            ]));
        }

        $splitCart->addLines($lines->map(fn ($line): array => [
            'purchasable' => $line->purchasable,
            'quantity' => $line->quantity,
            'meta' => array_merge((array) ($line->meta ?? []), [
                'store_id' => $storeId,
            ]),
        ])->all());

        $preferredShippingIdentifier = $sourceCart->shippingAddress?->shipping_option;
        $shippingOptions = ShippingManifest::getOptions($splitCart->calculate());
        $shippingOption = $shippingOptions->first(
            fn ($option): bool => $option->getIdentifier() === $preferredShippingIdentifier
        ) ?? $shippingOptions->first();

        if ($shippingOption) {
            $splitCart->setShippingOption($shippingOption);
        }

        return $splitCart->refresh()->calculate();
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @param  array<string, mixed>|null  $appliedCoupon
     */
    private function alignOrderTotalsWithMarketplaceCart(
        Collection $orders,
        Cart $sourceCart,
        ?array $appliedCoupon,
        string $checkoutGroupId
    ): void {
        $marketplaceShippingTotal = (int) ($sourceCart->shippingTotal?->value ?? 0);
        $marketplaceDiscountTotal = (int) ($appliedCoupon['discount_amount'] ?? 0);
        $baseTotals = $orders->map(fn (Order $order): int => $this->baseProductTotal($order));

        $shippingAllocations = $this->allocateAmount($marketplaceShippingTotal, $baseTotals);
        $discountAllocations = $this->allocateAmount($marketplaceDiscountTotal, $baseTotals);

        foreach ($orders as $index => $order) {
            $baseProductTotal = $baseTotals[$index];
            $allocatedShipping = $shippingAllocations[$index];
            $allocatedDiscount = $discountAllocations[$index];
            $shippingLine = $order->lines->firstWhere('type', 'shipping');

            if ($shippingLine instanceof OrderLine) {
                $shippingLine->update([
                    'unit_price' => $allocatedShipping,
                    'sub_total' => $allocatedShipping,
                    'discount_total' => 0,
                    'tax_total' => 0,
                    'total' => $allocatedShipping,
                ]);
            }

            $orderMeta = array_merge((array) ($order->meta ?? []), [
                'checkout_group_id' => $checkoutGroupId,
                'checkout_order_count' => $orders->count(),
            ]);

            if ($appliedCoupon) {
                $orderMeta['applied_coupon'] = array_merge($appliedCoupon, [
                    'discount_amount' => $allocatedDiscount,
                    'discount_formatted' => '₱'.number_format($allocatedDiscount / 100, 2),
                ]);
            }

            $order->update([
                'shipping_total' => $allocatedShipping,
                'discount_total' => $allocatedDiscount,
                'total' => max(0, $baseProductTotal + $allocatedShipping - $allocatedDiscount),
                'meta' => $orderMeta,
            ]);

            $this->commissionService->applyToOrder($order->refresh());
        }

        if ($appliedCoupon && ! empty($appliedCoupon['coupon_id'])) {
            Coupon::query()
                ->whereKey($appliedCoupon['coupon_id'])
                ->increment('times_used');
        }
    }

    private function validatedAppliedCoupon(Cart $cart): ?array
    {
        $appliedCoupon = (array) ($cart->meta['applied_coupon'] ?? []);

        if (! $appliedCoupon) {
            return null;
        }

        $coupon = Coupon::query()->find((int) ($appliedCoupon['coupon_id'] ?? 0));

        if (! $coupon || ! $coupon->isUsable()) {
            throw ValidationException::withMessages([
                'code' => ['The applied coupon is no longer valid. Please remove it and try again.'],
            ]);
        }

        if ($coupon->scope !== CouponScope::Global) {
            throw ValidationException::withMessages([
                'code' => ['Only marketplace-wide coupons can be used when checking out from multiple stores.'],
            ]);
        }

        return $appliedCoupon;
    }

    private function baseProductTotal(Order $order): int
    {
        return $order->lines
            ->where('type', '!=', 'shipping')
            ->sum(fn ($line): int => (int) $line->total->value);
    }

    /**
     * @param  Collection<int, int>  $weights
     * @return list<int>
     */
    private function allocateAmount(int $total, Collection $weights): array
    {
        if ($weights->sum() <= 0 || $total <= 0) {
            return $weights->map(fn (): int => 0)->values()->all();
        }

        $allocated = [];
        $runningTotal = 0;
        $sumOfWeights = $weights->sum();
        $lastIndex = $weights->keys()->last();

        foreach ($weights as $index => $weight) {
            if ($index === $lastIndex) {
                $allocated[$index] = $total - $runningTotal;

                continue;
            }

            $share = (int) floor(($total * $weight) / $sumOfWeights);
            $allocated[$index] = $share;
            $runningTotal += $share;
        }

        ksort($allocated);

        return array_values($allocated);
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array{
     *     checkout_group_id: string,
     *     orders: Collection<int, Order>,
     *     order_ids: list<int>,
     *     first_order_id: int,
     *     applied_coupon: array<string, mixed>|null
     * }
     */
    private function formatCheckoutResult(Collection $orders): array
    {
        /** @var Order $firstOrder */
        $firstOrder = $orders->first();

        return [
            'checkout_group_id' => (string) ($firstOrder->meta['checkout_group_id'] ?? ''),
            'orders' => $orders->values(),
            'order_ids' => $orders->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            'first_order_id' => (int) $firstOrder->id,
            'applied_coupon' => $firstOrder->meta['applied_coupon'] ?? null,
        ];
    }
}
