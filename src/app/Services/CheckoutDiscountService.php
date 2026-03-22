<?php

namespace App\Services;

use App\CouponScope;
use App\CouponType;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Validation\ValidationException;
use Lunar\Models\Cart;

class CheckoutDiscountService
{
    /**
     * @return array{
     *     coupon_id: int,
     *     code: string,
     *     description: ?string,
     *     type: string,
     *     type_label: string,
     *     discount_amount: int,
     *     discount_formatted: string,
     *     helper_text: string
     * }
     */
    public function validateAndCalculate(string $code, Cart $cart, Store $store): array
    {
        $coupon = Coupon::query()
            ->active()
            ->whereRaw('LOWER(code) = ?', [mb_strtolower(trim($code))])
            ->first();

        if (! $coupon) {
            throw ValidationException::withMessages([
                'code' => ['This coupon code is invalid or has expired.'],
            ]);
        }

        return $this->calculateCouponData($coupon, $cart, $store);
    }

    /**
     * @return array<string, mixed>
     */
    public function calculateAppliedCoupon(Cart $cart, Store $store): ?array
    {
        $appliedCoupon = (array) ($cart->meta['applied_coupon'] ?? []);
        $couponId = (int) ($appliedCoupon['coupon_id'] ?? 0);

        if (! $couponId) {
            return null;
        }

        $coupon = Coupon::query()->find($couponId);

        if (! $coupon || ! $coupon->isUsable()) {
            return null;
        }

        return $this->calculateCouponData($coupon, $cart, $store);
    }

    /**
     * @return array{discount_amount: int, total_after_discount: int, applied_coupon: ?array<string, mixed>}
     */
    public function summarizeCart(Cart $cart, Store $store): array
    {
        $cart = $cart->calculate();
        $appliedCoupon = $this->calculateAppliedCoupon($cart, $store);
        $discountAmount = (int) ($appliedCoupon['discount_amount'] ?? 0);
        $totalAfterDiscount = max(0, ($cart->total?->value ?? 0) - $discountAmount);

        return [
            'discount_amount' => $discountAmount,
            'total_after_discount' => $totalAfterDiscount,
            'applied_coupon' => $appliedCoupon,
        ];
    }

    public function applyToCart(Cart $cart, Store $store, string $code): Cart
    {
        $couponData = $this->validateAndCalculate($code, $cart, $store);

        $cart->update([
            'meta' => array_merge((array) ($cart->meta ?? []), [
                'applied_coupon' => $couponData,
            ]),
        ]);

        return $cart->refresh();
    }

    public function removeFromCart(Cart $cart): Cart
    {
        $meta = (array) ($cart->meta ?? []);
        unset($meta['applied_coupon']);

        $cart->update(['meta' => $meta]);

        return $cart->refresh();
    }

    public function applyToOrder(Order $order, Cart $cart, Store $store): Order
    {
        $summary = $this->summarizeCart($cart, $store);
        $appliedCoupon = $summary['applied_coupon'];

        if (! $appliedCoupon) {
            return $order;
        }

        $orderMeta = array_merge((array) ($order->meta ?? []), [
            'applied_coupon' => $appliedCoupon,
        ]);

        $order->update([
            'discount_total' => $summary['discount_amount'],
            'total' => $summary['total_after_discount'],
            'meta' => $orderMeta,
        ]);

        Coupon::query()
            ->whereKey($appliedCoupon['coupon_id'])
            ->increment('times_used');

        return $order->refresh();
    }

    private function calculateCouponData(Coupon $coupon, Cart $cart, Store $store): array
    {
        $cart = $cart->calculate();
        $subTotal = (int) ($cart->subTotal?->value ?? 0);
        $shippingTotal = (int) ($cart->shippingTotal?->value ?? 0);

        if ($coupon->min_order_cents && $subTotal < $coupon->min_order_cents) {
            throw ValidationException::withMessages([
                'code' => ['This coupon requires a higher order subtotal.'],
            ]);
        }

        if ($coupon->scope === CouponScope::Store && (int) $coupon->store_id !== (int) $store->id) {
            throw ValidationException::withMessages([
                'code' => ['This coupon is only valid for a different store.'],
            ]);
        }

        if ($coupon->scope === CouponScope::Sector && $coupon->sector !== $store->sector) {
            throw ValidationException::withMessages([
                'code' => ['This coupon is not valid for this sector.'],
            ]);
        }

        $discountAmount = match ($coupon->type) {
            CouponType::Percentage => (int) round($subTotal * ($coupon->value / 100)),
            CouponType::FixedAmount => min($coupon->value, $subTotal),
            CouponType::FreeShipping => $shippingTotal,
        };

        if ($coupon->max_discount_cents) {
            $discountAmount = min($discountAmount, $coupon->max_discount_cents);
        }

        if ($discountAmount <= 0) {
            throw ValidationException::withMessages([
                'code' => ['This coupon does not apply to your current checkout.'],
            ]);
        }

        return [
            'coupon_id' => $coupon->id,
            'code' => $coupon->code,
            'description' => $coupon->description,
            'type' => $coupon->type->value,
            'type_label' => $coupon->type->label(),
            'discount_amount' => $discountAmount,
            'discount_formatted' => '₱'.number_format($discountAmount / 100, 2),
            'helper_text' => $coupon->type === CouponType::FreeShipping
                ? 'Free shipping applied.'
                : 'Discount applied at checkout.',
        ];
    }
}
