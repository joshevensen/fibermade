<?php

namespace Database\Seeders;

use App\Enums\DiscountType;
use App\Models\Account;
use App\Models\Discount;
use Illuminate\Database\Seeder;

class DiscountSeeder extends Seeder
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

        $discounts = [
            [
                'name' => 'Free Shipping Over $75',
                'type' => DiscountType::OrderThresholdFreeShipping,
                'code' => 'FREESHIP75',
                'parameters' => ['threshold' => 75],
                'is_active' => true,
            ],
            [
                'name' => 'Buy 5 Get 1 Free',
                'type' => DiscountType::QuantityPerSkein,
                'code' => 'BUY5GET1',
                'parameters' => [
                    'min_quantity' => 5,
                    'discount_per_skein' => 0,
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Holiday Sale 20% Off',
                'type' => DiscountType::TimeBoxed,
                'code' => 'HOLIDAY20',
                'parameters' => [
                    'percentage' => 20,
                    'starts_at' => now()->subDays(2)->toIso8601String(),
                    'ends_at' => now()->addDays(28)->toIso8601String(),
                ],
                'starts_at' => now()->subDays(2),
                'ends_at' => now()->addDays(28),
                'is_active' => true,
            ],
        ];

        foreach ($discounts as $discountData) {
            Discount::create([
                'account_id' => $account->id,
                'name' => $discountData['name'],
                'type' => $discountData['type'],
                'code' => $discountData['code'],
                'parameters' => $discountData['parameters'],
                'starts_at' => $discountData['starts_at'] ?? null,
                'ends_at' => $discountData['ends_at'] ?? null,
                'is_active' => $discountData['is_active'],
            ]);
        }
    }
}
