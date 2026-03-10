<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Coupon validation endpoint for the customer storefront.
 *
 * Customers can validate a coupon code to see if it's usable
 * before applying it at checkout.
 */
class CouponController extends Controller
{
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $coupon = Coupon::query()
            ->where('code', strtoupper(trim($request->input('code'))))
            ->first();

        if (! $coupon || ! $coupon->isUsable()) {
            throw ValidationException::withMessages([
                'code' => ['This coupon code is invalid or has expired.'],
            ]);
        }

        return response()->json([
            'id' => $coupon->id,
            'code' => $coupon->code,
            'description' => $coupon->description,
            'type' => $coupon->type->value,
            'type_label' => $coupon->type->label(),
            'scope' => $coupon->scope->value,
            'value' => $coupon->value,
            'min_order_cents' => $coupon->min_order_cents,
            'max_discount_cents' => $coupon->max_discount_cents,
            'starts_at' => $coupon->starts_at,
            'ends_at' => $coupon->ends_at,
        ]);
    }
}
