<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Customer;
use App\Models\Show;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(OrderType::cases());
        $status = fake()->randomElement(OrderStatus::cases());
        $orderDate = fake()->dateTimeBetween('-3 months', 'now');

        $subtotal = fake()->randomFloat(2, 25, 500);
        $shipping = fake()->optional(0.7)->randomFloat(2, 5, 25);
        $discount = fake()->optional(0.3)->randomFloat(2, 5, 50);
        $tax = fake()->optional(0.5)->randomFloat(2, 2, 50);
        $total = $subtotal + ($shipping ?? 0) - ($discount ?? 0) + ($tax ?? 0);

        return [
            'type' => $type,
            'status' => $status,
            'shopify_order_id' => $type === OrderType::Retail ? fake()->optional(0.7)->numerify('##########') : null,
            'order_date' => $orderDate,
            'subtotal_amount' => $subtotal,
            'shipping_amount' => $shipping,
            'discount_amount' => $discount,
            'tax_amount' => $tax,
            'total_amount' => $total,
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function ($order) {
            if ($order->orderable_id === null && $order->orderable_type === null) {
                $type = $order->type ?? fake()->randomElement(OrderType::cases());

                if ($type === OrderType::Wholesale) {
                    $store = Store::factory()->create();
                    $order->orderable_id = $store->id;
                    $order->orderable_type = Store::class;
                } elseif ($type === OrderType::Retail) {
                    $customer = Customer::factory()->create(['account_id' => $order->account_id]);
                    $order->orderable_id = $customer->id;
                    $order->orderable_type = Customer::class;
                } elseif ($type === OrderType::Show) {
                    $show = Show::factory()->create(['account_id' => $order->account_id]);
                    $order->orderable_id = $show->id;
                    $order->orderable_type = Show::class;
                }
            }
        });
    }
}
