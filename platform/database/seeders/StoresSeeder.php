<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\BaseStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\StoreVendorStatus;
use App\Enums\UserRole;
use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Creator;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $creator = Creator::where('name', 'Bad Frog Yarn Co.')->first();

        if (! $creator || ! $creator->account) {
            $this->command->warn('Bad Frog Yarn Co. account not found. Run FoundationSeeder and BadFrogSeeder first.');

            return;
        }

        $account = $creator->account;
        $user = User::where('account_id', $account->id)->first();

        $stores = $this->seedStores($creator);
        $this->seedWholesaleOrders($account, $user, $stores);
        $this->seedOrderItems($account);
    }

    /**
     * Seed 3 stores: Yarnivore (Han Smith) plus 2 random. Attach to creator as vendors.
     *
     * @return array<int, Store>
     */
    protected function seedStores(Creator $creator): array
    {
        $stores = [];

        $yarnivoreAccount = Account::create([
            'status' => BaseStatus::Active,
            'type' => AccountType::Store,
            'subscription_status' => null,
        ]);

        $yarnivore = Store::factory()->create([
            'account_id' => $yarnivoreAccount->id,
            'name' => 'Yarnivore',
            'email' => 'han@yarnivore.com',
            'owner_name' => 'Han Smith',
            'address_line1' => fake()->streetAddress(),
            'address_line2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'state_region' => fake()->stateAbbr(),
            'postal_code' => fake()->postcode(),
            'country_code' => 'US',
        ]);

        User::factory()->create([
            'account_id' => $yarnivoreAccount->id,
            'name' => 'Han Smith',
            'email' => 'han@yarnivore.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Owner,
        ]);

        $creator->stores()->attach($yarnivore->id, [
            'discount_rate' => 0.40,
            'minimum_order_quantity' => 1,
            'minimum_order_value' => 0,
            'payment_terms' => 'Due on Receipt',
            'lead_time_days' => 20,
            'allows_preorders' => true,
            'status' => StoreVendorStatus::Active->value,
            'notes' => null,
        ]);

        $stores[] = $yarnivore;

        for ($i = 0; $i < 2; $i++) {
            $storeAccount = Account::create([
                'status' => BaseStatus::Active,
                'type' => AccountType::Store,
                'subscription_status' => null,
            ]);

            $store = Store::factory()->create([
                'account_id' => $storeAccount->id,
            ]);

            $creator->stores()->attach($store->id, [
                'discount_rate' => fake()->randomFloat(2, 0.10, 0.30),
                'minimum_order_quantity' => fake()->numberBetween(1, 20),
                'minimum_order_value' => fake()->randomFloat(2, 100, 500),
                'payment_terms' => fake()->randomElement(['Net 30', 'Net 60', 'Due on Receipt']),
                'lead_time_days' => fake()->numberBetween(14, 60),
                'allows_preorders' => fake()->boolean(30),
                'status' => StoreVendorStatus::Active->value,
                'notes' => fake()->optional()->sentence(),
            ]);

            $stores[] = $store;
        }

        return $stores;
    }

    /**
     * Seed wholesale orders for the given stores.
     *
     * @param  array<int, Store>  $stores
     */
    protected function seedWholesaleOrders(Account $account, ?User $user, array $stores): void
    {
        $statusDistribution = [
            [OrderStatus::Delivered, 4],
            [OrderStatus::Open, 2],
            [OrderStatus::Draft, 1],
        ];

        foreach ($statusDistribution as [$status, $count]) {
            for ($i = 0; $i < $count; $i++) {
                $store = fake()->randomElement($stores);
                $orderDate = fake()->dateTimeBetween('-2 months', 'now');

                $order = Order::create([
                    'account_id' => $account->id,
                    'type' => OrderType::Wholesale,
                    'status' => $status,
                    'order_date' => $orderDate,
                    'orderable_id' => $store->id,
                    'orderable_type' => Store::class,
                    'created_by' => $user?->id,
                ]);

                if ($status === OrderStatus::Delivered) {
                    $order->update([
                        'delivered_at' => $order->order_date?->setTime(14, 0) ?? $order->created_at,
                    ]);
                }
            }
        }
    }

    /**
     * Seed order items for wholesale orders. Recalculates order totals.
     */
    protected function seedOrderItems(Account $account): void
    {
        $orders = Order::where('account_id', $account->id)
            ->where('type', OrderType::Wholesale)
            ->get();

        $bases = Base::where('account_id', $account->id)->get();
        $colorways = Colorway::where('account_id', $account->id)->get();

        if ($orders->isEmpty() || $bases->isEmpty() || $colorways->isEmpty()) {
            return;
        }

        foreach ($orders as $order) {
            $itemCount = fake()->numberBetween(2, 5);

            for ($i = 0; $i < $itemCount; $i++) {
                $base = $bases->random();
                $colorway = $colorways->random();
                $quantity = fake()->numberBetween(5, 20);
                $unitPrice = ($base->retail_price ?? fake()->randomFloat(2, 20, 35)) * 0.7;
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

            $order->refresh();
            $order->load('orderItems');
            $subtotal = $order->orderItems->sum('line_total');
            $shipping = $order->shipping_amount ?? 0;
            $discount = $order->discount_amount ?? 0;
            $tax = $order->tax_amount ?? 0;
            $total = $subtotal + $shipping - $discount + $tax;

            $order->update([
                'subtotal_amount' => $subtotal,
                'total_amount' => $total,
            ]);
        }
    }
}
