<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Models\Store;
use App\OrderPaymentMethod;
use App\StoreStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Lunar\Facades\CartSession;

/**
 * Validates order placement requests.
 *
 * Ensures the store exists, is approved, and the customer is authorised.
 *
 * @see /skills/order-processing.md
 * @see /agent/order-agent.md
 */
class PlaceOrderRequest extends FormRequest
{
    /**
     * Inject store_id from the active cart's meta when not provided in the
     * request body. This allows the SPA to place an order without re-sending
     * the store_id that was already persisted when adding items to the cart.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('store_id')) {
            // Use current() rather than manager() so we never trigger cart
            // creation when no session cart exists (e.g. during tests).
            $cart = CartSession::current();

            if ($cart && ! empty($cart->meta['store_id'])) {
                $this->merge(['store_id' => (int) $cart->meta['store_id']]);
            }
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Order::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'payment_method' => ['required', 'string', Rule::in(array_map(
                static fn (OrderPaymentMethod $method): string => $method->value,
                OrderPaymentMethod::cases()
            ))],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * Adds an after-validation hook to verify the store is approved.
     * This runs in the FormRequest layer so it fires before the controller,
     * keeping store-eligibility checks independent of cart state.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $store = Store::query()->find($this->validated('store_id'));

                if ($store && $store->status !== StoreStatus::Approved) {
                    $validator->errors()->add(
                        'store_id',
                        'This store is not currently accepting orders.'
                    );
                }

                if (
                    $store
                    && $this->validated('payment_method') === OrderPaymentMethod::CashOnDelivery->value
                    && $store->sector !== 'ecommerce'
                ) {
                    $validator->errors()->add(
                        'payment_method',
                        'Cash on Delivery is only available for e-commerce orders.'
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'store_id.required' => 'A store must be selected for this order.',
            'store_id.exists' => 'The selected store does not exist.',
            'store_id.integer' => 'The store identifier must be valid.',
            'payment_method.required' => 'Please select a payment method.',
            'payment_method.in' => 'The selected payment method is not supported.',
        ];
    }
}
