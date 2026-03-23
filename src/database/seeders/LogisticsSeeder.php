<?php

namespace Database\Seeders;

use App\DeliveryStatus;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Store;
use App\Models\User;
use App\OrderPaymentMethod;
use App\OrderPaymentStatus;
use App\OrderStatus;
use App\ShipmentProvider;
use Illuminate\Database\Seeder;
use Lunar\Base\ValueObjects\Cart\TaxBreakdown;
use Lunar\Models\Channel;
use Lunar\Models\Product;
use Lunar\Models\ProductVariant;

class LogisticsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customer = User::query()->where('email', 'customer@negosyohub.test')->firstOrFail();
        $channel = Channel::query()->whereDefault(true)->firstOrFail();

        $this->seedOrderWithShipment(
            storeSlug: 'technest',
            customer: $customer,
            channel: $channel,
            reference: 'DEMO-COD-TECHNEST',
            paymentMethod: OrderPaymentMethod::CashOnDelivery,
            paymentStatus: OrderPaymentStatus::Unpaid,
            orderStatus: OrderStatus::Shipped,
            deliveryStatus: DeliveryStatus::InTransit,
            shippingFee: 14_900,
        );

        $this->seedOrderWithShipment(
            storeSlug: 'freshbasket',
            customer: $customer,
            channel: $channel,
            reference: 'DEMO-PAYPAL-FRESHBASKET',
            paymentMethod: OrderPaymentMethod::PayPal,
            paymentStatus: OrderPaymentStatus::Paid,
            orderStatus: OrderStatus::Delivered,
            deliveryStatus: DeliveryStatus::Delivered,
            shippingFee: 9_900,
        );
    }

    private function seedOrderWithShipment(
        string $storeSlug,
        User $customer,
        Channel $channel,
        string $reference,
        OrderPaymentMethod $paymentMethod,
        OrderPaymentStatus $paymentStatus,
        OrderStatus $orderStatus,
        DeliveryStatus $deliveryStatus,
        int $shippingFee,
    ): void {
        $store = Store::query()->where('slug', $storeSlug)->first();

        if (! $store) {
            $this->command?->warn("LogisticsSeeder: store [{$storeSlug}] not found — skipping.");

            return;
        }

        $products = Product::query()
            ->whereJsonContains('attribute_data->store_id->value', $store->id)
            ->with(['variants.prices'])
            ->take(2)
            ->get();

        if ($products->isEmpty()) {
            $this->command?->warn("LogisticsSeeder: store [{$storeSlug}] has no products — skipping.");

            return;
        }

        $linePayloads = $products->map(function (Product $product, int $index): array {
            /** @var ProductVariant|null $variant */
            $variant = $product->variants->first();
            $price = (int) ($variant?->prices->first()?->price?->value ?? 0);
            $quantity = $index === 0 ? 1 : 2;
            $subTotal = $price * $quantity;
            $taxTotal = (int) round($subTotal * 0.12);

            return [
                'variant' => $variant,
                'description' => $product->translateAttribute('name'),
                'identifier' => $variant?->sku ?? "demo-{$product->id}",
                'unit_price' => $price,
                'quantity' => $quantity,
                'sub_total' => $subTotal,
                'tax_total' => $taxTotal,
                'total' => $subTotal + $taxTotal,
            ];
        })->filter(fn (array $line): bool => (bool) $line['variant'])->values();

        if ($linePayloads->isEmpty()) {
            return;
        }

        $subTotal = (int) $linePayloads->sum('sub_total');
        $taxTotal = (int) $linePayloads->sum('tax_total');
        $total = $subTotal + $taxTotal + $shippingFee;
        $commissionAmount = (int) round($total * 0.12);
        $storeEarning = $total - $commissionAmount;

        $order = Order::query()->updateOrCreate(
            ['reference' => $reference],
            [
                'channel_id' => $channel->id,
                'new_customer' => false,
                'user_id' => $customer->id,
                'store_id' => $store->id,
                'status' => $orderStatus->value,
                'sub_total' => $subTotal,
                'discount_total' => 0,
                'shipping_total' => $shippingFee,
                'tax_breakdown' => new TaxBreakdown,
                'tax_total' => $taxTotal,
                'total' => $total,
                'notes' => 'Demo seeded order for delivery tracking.',
                'currency_code' => 'PHP',
                'compare_currency_code' => 'PHP',
                'exchange_rate' => 1,
                'meta' => [],
                'commission_amount' => $commissionAmount,
                'store_earning' => $storeEarning,
                'platform_earning' => $commissionAmount,
                'payment_method' => $paymentMethod->value,
                'payment_status' => $paymentStatus->value,
                'paid_at' => $paymentStatus === OrderPaymentStatus::Paid ? now()->subDays(3) : null,
            ]
        );

        $order->lines()->delete();
        $order->addresses()->delete();
        $order->shipments()->delete();

        foreach ($linePayloads as $line) {
            /** @var ProductVariant $variant */
            $variant = $line['variant'];

            $order->lines()->create([
                'purchasable_type' => $variant->getMorphClass(),
                'purchasable_id' => $variant->id,
                'type' => 'physical',
                'description' => $line['description'],
                'option' => null,
                'identifier' => $line['identifier'],
                'unit_price' => $line['unit_price'],
                'unit_quantity' => 1,
                'quantity' => $line['quantity'],
                'sub_total' => $line['sub_total'],
                'discount_total' => 0,
                'tax_breakdown' => new TaxBreakdown,
                'tax_total' => $line['tax_total'],
                'total' => $line['total'],
                'notes' => null,
                'meta' => null,
            ]);
        }

        $order->lines()->create([
            'purchasable_type' => ProductVariant::morphName(),
            'purchasable_id' => $linePayloads->first()['variant']->id,
            'type' => 'shipping',
            'description' => 'Standard delivery',
            'option' => 'Nationwide courier',
            'identifier' => "{$reference}-shipping",
            'unit_price' => $shippingFee,
            'unit_quantity' => 1,
            'quantity' => 1,
            'sub_total' => $shippingFee,
            'discount_total' => 0,
            'tax_breakdown' => new TaxBreakdown,
            'tax_total' => 0,
            'total' => $shippingFee,
            'notes' => null,
            'meta' => null,
        ]);

        $order->addresses()->create([
            'country_id' => null,
            'title' => 'Ms.',
            'first_name' => 'Demo',
            'last_name' => 'Customer',
            'company_name' => null,
            'line_one' => '101 Scout Torillo Street',
            'line_two' => 'Unit 5C',
            'line_three' => null,
            'city' => 'Quezon City',
            'state' => 'Metro Manila',
            'postcode' => '1103',
            'delivery_instructions' => 'Please call upon arrival.',
            'contact_email' => $customer->email,
            'contact_phone' => $customer->phone ?? '09171234567',
            'type' => 'shipping',
            'shipping_option' => 'standard',
            'meta' => null,
        ]);

        Shipment::query()->create([
            'order_id' => $order->id,
            'provider' => ShipmentProvider::Manual->value,
            'external_reference' => "{$reference}-SHIP",
            'delivery_status' => $deliveryStatus->value,
            'driver_name' => $deliveryStatus === DeliveryStatus::Delivered ? 'Ramon Dela Cruz' : 'Paolo Santos',
            'driver_contact' => '09175551234',
            'vehicle_type' => 'Motorcycle',
            'tracking_url' => 'https://tracking.negosyohub.test/'.$reference,
            'pickup_address' => ($store->address['line1'] ?? $store->address['city'] ?? $store->name).', '.($store->address['city'] ?? 'Metro Manila'),
            'dropoff_address' => '101 Scout Torillo Street, Quezon City',
            'booking_payload' => ['source' => 'demo-seeder', 'store_slug' => $store->slug],
            'provider_response' => ['status' => $deliveryStatus->value, 'mode' => 'manual'],
            'booked_at' => now()->subDays(3),
            'picked_up_at' => in_array($deliveryStatus, [DeliveryStatus::PickedUp, DeliveryStatus::InTransit, DeliveryStatus::Delivered], true) ? now()->subDays(2) : null,
            'delivered_at' => $deliveryStatus === DeliveryStatus::Delivered ? now()->subDay() : null,
            'failed_at' => null,
            'cancelled_at' => null,
            'last_synced_at' => now()->subHour(),
        ]);
    }
}
