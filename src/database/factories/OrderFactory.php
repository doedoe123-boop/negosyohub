<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\OrderPaymentMethod;
use App\OrderPaymentStatus;
use App\OrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Lunar\Base\ValueObjects\Cart\TaxBreakdown;
use Lunar\Models\Channel;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $total = fake()->numberBetween(50000, 500000); // in cents
        $taxTotal = intval($total * 0.12);
        $commissionRate = 15;
        $commissionAmount = intval($total * ($commissionRate / 100));
        $storeEarning = $total - $commissionAmount;

        return [
            'channel_id' => Channel::factory(),
            'new_customer' => fake()->boolean,
            'user_id' => User::factory(),
            'store_id' => Store::factory(),
            'status' => OrderStatus::Pending->value,
            'reference' => fake()->unique()->regexify('[A-Z]{8}'),
            'sub_total' => $total - $taxTotal,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_breakdown' => new TaxBreakdown,
            'tax_total' => $taxTotal,
            'total' => $total,
            'notes' => null,
            'currency_code' => 'PHP',
            'compare_currency_code' => 'PHP',
            'exchange_rate' => 1,
            'meta' => [],
            'commission_amount' => $commissionAmount,
            'store_earning' => $storeEarning,
            'platform_earning' => $commissionAmount,
            'payment_method' => OrderPaymentMethod::PayPal->value,
            'payment_status' => OrderPaymentStatus::Paid->value,
            'paid_at' => now(),
        ];
    }

    /**
     * Mark the order as delivered.
     */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Delivered->value,
        ]);
    }

    /**
     * Mark the order as cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Cancelled->value,
        ]);
    }

    /**
     * Mark the order as confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Confirmed->value,
        ]);
    }

    public function cashOnDelivery(): static
    {
        return $this->state(fn (): array => [
            'payment_method' => OrderPaymentMethod::CashOnDelivery->value,
            'payment_status' => OrderPaymentStatus::Unpaid->value,
            'paid_at' => null,
        ]);
    }
}
