<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Show;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrdersSeeder extends Seeder
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

        $this->seedCustomers($account);
        $this->seedStores($account);
        $this->seedShows($account);
        $this->seedOrders($account);
        $this->seedOrderItems($account);
    }

    /**
     * Seed customers.
     */
    protected function seedCustomers(Account $account): void
    {
        Customer::factory()
            ->count(10)
            ->create([
                'account_id' => $account->id,
            ]);
    }

    /**
     * Seed stores.
     */
    protected function seedStores(Account $account): void
    {
        Store::factory()->count(8)->create([
            'account_id' => $account->id,
        ]);
    }

    /**
     * Seed shows.
     */
    protected function seedShows(Account $account): void
    {
        $shows = [
            [
                'name' => 'Spring Fiber Festival',
                'start_at' => now()->addMonths(2)->startOfDay()->setTime(9, 0),
                'end_at' => now()->addMonths(2)->addDays(2)->endOfDay()->setTime(17, 0),
                'location_name' => 'Convention Center',
                'location_address' => '123 Main Street',
                'location_city' => 'Portland',
                'location_state' => 'OR',
                'location_zip' => '97201',
                'description' => 'Annual spring fiber festival featuring local yarn dyers, fiber artists, and workshops.',
                'website' => 'https://springfiberfestival.example.com',
            ],
            [
                'name' => 'Trunk Show at Local Yarn Store',
                'start_at' => now()->addWeeks(3)->setTime(10, 0),
                'end_at' => now()->addWeeks(3)->setTime(18, 0),
                'location_name' => 'Knit & Purl Yarn Shop',
                'location_address' => '456 Oak Avenue',
                'location_city' => 'Seattle',
                'location_state' => 'WA',
                'location_zip' => '98101',
                'description' => 'Exclusive trunk show featuring our latest colorways and bases.',
                'website' => null,
            ],
            [
                'name' => 'Downtown Yarn Market',
                'start_at' => now()->addMonths(4)->startOfDay()->setTime(8, 0),
                'end_at' => now()->addMonths(4)->endOfDay()->setTime(20, 0),
                'location_name' => 'Downtown Market Square',
                'location_address' => '789 Market Street',
                'location_city' => 'San Francisco',
                'location_state' => 'CA',
                'location_zip' => '94102',
                'description' => 'Outdoor yarn market with multiple vendors, food trucks, and live music.',
                'website' => 'https://downtownyarnmarket.example.com',
            ],
            [
                'name' => 'Fall Fiber Arts Fair',
                'start_at' => now()->addMonths(6)->startOfDay()->setTime(9, 0),
                'end_at' => now()->addMonths(6)->addDays(3)->endOfDay()->setTime(17, 0),
                'location_name' => 'Fairgrounds',
                'location_address' => '1000 Fairgrounds Road',
                'location_city' => 'Denver',
                'location_state' => 'CO',
                'location_zip' => '80202',
                'description' => 'Multi-day fiber arts fair with workshops, demonstrations, and vendor booths.',
                'website' => 'https://fallfiberartsfair.example.com',
            ],
            [
                'name' => 'Holiday Market',
                'start_at' => now()->addMonths(8)->startOfDay()->setTime(10, 0),
                'end_at' => now()->addMonths(8)->endOfDay()->setTime(16, 0),
                'location_name' => 'Community Center',
                'location_address' => '555 Elm Street',
                'location_city' => 'Boulder',
                'location_state' => 'CO',
                'location_zip' => '80301',
                'description' => 'Holiday market featuring hand-dyed yarns perfect for gift knitting.',
                'website' => null,
            ],
        ];

        foreach ($shows as $showData) {
            Show::create([
                'account_id' => $account->id,
                'name' => $showData['name'],
                'start_at' => $showData['start_at'],
                'end_at' => $showData['end_at'],
                'location_name' => $showData['location_name'],
                'location_address' => $showData['location_address'],
                'location_city' => $showData['location_city'],
                'location_state' => $showData['location_state'],
                'location_zip' => $showData['location_zip'],
                'description' => $showData['description'],
                'website' => $showData['website'],
            ]);
        }
    }

    /**
     * Seed orders.
     */
    protected function seedOrders(Account $account): void
    {
        $user = User::where('account_id', $account->id)->first();

        Order::factory()
            ->count(150)
            ->create([
                'account_id' => $account->id,
                'created_by' => $user?->id,
            ]);
    }

    /**
     * Seed order items.
     */
    protected function seedOrderItems(Account $account): void
    {
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
