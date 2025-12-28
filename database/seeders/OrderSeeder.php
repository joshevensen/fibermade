<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Account;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
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

        $user = User::where('account_id', $account->id)->first();

        $orders = [
            [
                'type' => OrderType::Wholesale,
                'status' => OrderStatus::Open,
                'order_date' => now()->subDays(5),
                'subtotal_amount' => 450.00,
                'shipping_amount' => 25.00,
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'total_amount' => 475.00,
                'notes' => 'Yarnivore wholesale order',
            ],
            [
                'type' => OrderType::Retail,
                'status' => OrderStatus::Closed,
                'order_date' => now()->subDays(10),
                'shopify_order_id' => '1234567890',
                'subtotal_amount' => 125.50,
                'shipping_amount' => 8.50,
                'discount_amount' => 25.10,
                'tax_amount' => 8.68,
                'total_amount' => 117.58,
                'notes' => null,
            ],
            [
                'type' => OrderType::Show,
                'status' => OrderStatus::Draft,
                'order_date' => now()->addDays(30),
                'subtotal_amount' => 300.00,
                'shipping_amount' => 0.00,
                'discount_amount' => 0.00,
                'tax_amount' => 0.00,
                'total_amount' => 300.00,
                'notes' => 'Local craft fair inventory',
            ],
            [
                'type' => OrderType::Retail,
                'status' => OrderStatus::Open,
                'order_date' => now()->subDays(2),
                'shopify_order_id' => '0987654321',
                'subtotal_amount' => 89.00,
                'shipping_amount' => 0.00,
                'discount_amount' => 0.00,
                'tax_amount' => 6.23,
                'total_amount' => 95.23,
                'notes' => null,
            ],
            [
                'type' => OrderType::Wholesale,
                'status' => OrderStatus::Closed,
                'order_date' => now()->subDays(20),
                'subtotal_amount' => 680.00,
                'shipping_amount' => 35.00,
                'discount_amount' => 68.00,
                'tax_amount' => 0.00,
                'total_amount' => 647.00,
                'notes' => 'Large wholesale order - payment received',
            ],
        ];

        foreach ($orders as $orderData) {
            Order::create([
                'type' => $orderData['type'],
                'status' => $orderData['status'],
                'account_id' => $account->id,
                'shopify_order_id' => $orderData['shopify_order_id'] ?? null,
                'order_date' => $orderData['order_date'],
                'subtotal_amount' => $orderData['subtotal_amount'],
                'shipping_amount' => $orderData['shipping_amount'],
                'discount_amount' => $orderData['discount_amount'],
                'tax_amount' => $orderData['tax_amount'],
                'total_amount' => $orderData['total_amount'],
                'notes' => $orderData['notes'],
                'created_by' => $user?->id,
            ]);
        }
    }
}
