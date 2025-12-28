<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;

class OrderItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $account = Account::where('name', 'Bad Frog Yarn Co.')->first();

        if (! $account) {
            return;
        }

        $orders = Order::where('account_id', $account->id)->get();
        $bases = Base::where('account_id', $account->id)->get();
        $colorways = Colorway::where('account_id', $account->id)->get();

        if ($orders->isEmpty() || $bases->isEmpty() || $colorways->isEmpty()) {
            return;
        }

        foreach ($orders as $order) {
            // Each order gets 2-5 order items
            $itemCount = fake()->numberBetween(2, 5);

            for ($i = 0; $i < $itemCount; $i++) {
                $base = $bases->random();
                $colorway = $colorways->random();
                $quantity = fake()->numberBetween(1, 8);
                $unitPrice = $base->retail_price ?? fake()->randomFloat(2, 20, 35);
                $lineTotal = $quantity * $unitPrice;

                OrderItem::create([
                    'order_id' => $order->id,
                    'colorway_id' => $colorway->id,
                    'base_id' => $base->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);
            }
        }
    }
}
