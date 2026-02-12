<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\BaseStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\StoreVendorStatus;
use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Creator;
use App\Models\Customer;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
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
        // Find Bad Frog Yarn Co by creator name
        $creator = Creator::where('name', 'Bad Frog Yarn Co.')->first();

        if (! $creator || ! $creator->account) {
            return;
        }

        $account = $creator->account;

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
        $integration = Integration::where('account_id', $account->id)->first();

        Customer::factory()
            ->count(80)
            ->create([
                'account_id' => $account->id,
            ])
            ->each(function ($customer) use ($integration) {
                // Create external identifier for some customers (30% chance)
                if ($integration && fake()->boolean(30)) {
                    ExternalIdentifier::create([
                        'integration_id' => $integration->id,
                        'identifiable_type' => Customer::class,
                        'identifiable_id' => $customer->id,
                        'external_type' => 'customer',
                        'external_id' => fake()->numerify('##########'),
                    ]);
                }
            });
    }

    /**
     * Seed stores.
     *
     * Note: This creates stores that are already seeded in FoundationSeeder.
     * This method can be removed or updated if FoundationSeeder already handles this.
     * For now, we'll check if stores already exist before creating new ones.
     */
    protected function seedStores(Account $creatorAccount): void
    {
        // Get the creator record for the creator account
        $creator = Creator::where('account_id', $creatorAccount->id)->first();

        if (! $creator) {
            return;
        }

        // Get existing stores that have vendor relationships with this creator
        $existingStoreIds = $creator->stores()->pluck('stores.id');

        // Only create additional stores if we need more
        $storesToCreate = 8 - $existingStoreIds->count();

        if ($storesToCreate > 0) {
            for ($i = 0; $i < $storesToCreate; $i++) {
                // Create store account
                $storeAccount = Account::create([
                    'status' => BaseStatus::Active,
                    'type' => AccountType::Store,
                ]);

                // Create store record
                $store = Store::factory()->create([
                    'account_id' => $storeAccount->id,
                ]);

                // Create vendor relationship
                $creator->stores()->attach($store->id, [
                    'discount_rate' => fake()->randomFloat(2, 10, 30),
                    'minimum_order_quantity' => fake()->numberBetween(5, 20),
                    'minimum_order_value' => fake()->randomFloat(2, 100, 500),
                    'payment_terms' => fake()->randomElement(['Net 30', 'Net 60', 'Due on Receipt']),
                    'lead_time_days' => fake()->numberBetween(14, 60),
                    'allows_preorders' => fake()->boolean(30),
                    'status' => StoreVendorStatus::Active->value,
                    'notes' => fake()->optional()->sentence(),
                ]);
            }
        }
    }

    /**
     * Seed shows.
     */
    protected function seedShows(Account $account): void
    {
        // 8 past shows
        $pastShows = [
            [
                'name' => 'Winter Yarn Market',
                'start_at' => now()->subMonths(2)->startOfDay()->setTime(9, 0),
                'end_at' => now()->subMonths(2)->addDays(2)->endOfDay()->setTime(17, 0),
                'location_name' => 'Convention Center',
                'address_line1' => '123 Main Street',
                'city' => 'Portland',
                'state_region' => 'OR',
                'postal_code' => '97201',
                'description' => 'Winter yarn market featuring local yarn dyers, fiber artists, and workshops.',
                'website' => 'https://winteryarnmarket.example.com',
            ],
            [
                'name' => 'Holiday Trunk Show',
                'start_at' => now()->subMonths(1)->startOfDay()->setTime(10, 0),
                'end_at' => now()->subMonths(1)->startOfDay()->setTime(18, 0),
                'location_name' => 'Knit & Purl Yarn Shop',
                'address_line1' => '456 Oak Avenue',
                'city' => 'Seattle',
                'state_region' => 'WA',
                'postal_code' => '98101',
                'description' => 'Holiday trunk show featuring our latest colorways and bases.',
                'website' => null,
            ],
            [
                'name' => 'Fall Fiber Festival',
                'start_at' => now()->subMonths(3)->startOfDay()->setTime(9, 0),
                'end_at' => now()->subMonths(3)->addDays(3)->endOfDay()->setTime(17, 0),
                'location_name' => 'Fairgrounds',
                'address_line1' => '1000 Fairgrounds Road',
                'city' => 'Denver',
                'state_region' => 'CO',
                'postal_code' => '80202',
                'description' => 'Multi-day fiber festival with workshops, demonstrations, and vendor booths.',
                'website' => 'https://fallfiberfestival.example.com',
            ],
            [
                'name' => 'Summer Yarn Crawl',
                'start_at' => now()->subMonths(4)->startOfDay()->setTime(8, 0),
                'end_at' => now()->subMonths(4)->endOfDay()->setTime(20, 0),
                'location_name' => 'Downtown Market Square',
                'address_line1' => '789 Market Street',
                'city' => 'San Francisco',
                'state_region' => 'CA',
                'postal_code' => '94102',
                'description' => 'Summer yarn crawl with multiple vendors, food trucks, and live music.',
                'website' => 'https://summeryarncrawl.example.com',
            ],
            [
                'name' => 'Spring Fiber Arts Fair',
                'start_at' => now()->subMonths(5)->startOfDay()->setTime(9, 0),
                'end_at' => now()->subMonths(5)->addDays(2)->endOfDay()->setTime(17, 0),
                'location_name' => 'Community Center',
                'address_line1' => '555 Elm Street',
                'city' => 'Boulder',
                'state_region' => 'CO',
                'postal_code' => '80301',
                'description' => 'Spring fiber arts fair featuring hand-dyed yarns and workshops.',
                'website' => null,
            ],
            [
                'name' => 'Local Yarn Store Event',
                'start_at' => now()->subWeeks(6)->setTime(11, 0),
                'end_at' => now()->subWeeks(6)->setTime(19, 0),
                'location_name' => 'Yarn Haven',
                'address_line1' => '321 Yarn Street',
                'city' => 'Austin',
                'state_region' => 'TX',
                'postal_code' => '78701',
                'description' => 'Special event at local yarn store showcasing new collections.',
                'website' => null,
            ],
            [
                'name' => 'Fiber Market Days',
                'start_at' => now()->subWeeks(8)->startOfDay()->setTime(9, 0),
                'end_at' => now()->subWeeks(8)->addDays(1)->endOfDay()->setTime(16, 0),
                'location_name' => 'Market Hall',
                'address_line1' => '888 Fiber Avenue',
                'city' => 'Nashville',
                'state_region' => 'TN',
                'postal_code' => '37201',
                'description' => 'Weekend fiber market with local artisans and vendors.',
                'website' => 'https://fibermarketdays.example.com',
            ],
            [
                'name' => 'Yarn Dyers Showcase',
                'start_at' => now()->subWeeks(10)->setTime(10, 0),
                'end_at' => now()->subWeeks(10)->setTime(17, 0),
                'location_name' => 'Art Gallery',
                'address_line1' => '777 Creative Lane',
                'city' => 'Portland',
                'state_region' => 'OR',
                'postal_code' => '97202',
                'description' => 'Showcase event featuring work from local yarn dyers.',
                'website' => null,
            ],
        ];

        // 6 future shows
        $futureShows = [
            [
                'name' => 'Spring Fiber Festival',
                'start_at' => now()->addMonths(2)->startOfDay()->setTime(9, 0),
                'end_at' => now()->addMonths(2)->addDays(2)->endOfDay()->setTime(17, 0),
                'location_name' => 'Convention Center',
                'address_line1' => '123 Main Street',
                'city' => 'Portland',
                'state_region' => 'OR',
                'postal_code' => '97201',
                'description' => 'Annual spring fiber festival featuring local yarn dyers, fiber artists, and workshops.',
                'website' => 'https://springfiberfestival.example.com',
            ],
            [
                'name' => 'Trunk Show at Local Yarn Store',
                'start_at' => now()->addWeeks(3)->setTime(10, 0),
                'end_at' => now()->addWeeks(3)->setTime(18, 0),
                'location_name' => 'Knit & Purl Yarn Shop',
                'address_line1' => '456 Oak Avenue',
                'city' => 'Seattle',
                'state_region' => 'WA',
                'postal_code' => '98101',
                'description' => 'Exclusive trunk show featuring our latest colorways and bases.',
                'website' => null,
            ],
            [
                'name' => 'Downtown Yarn Market',
                'start_at' => now()->addMonths(4)->startOfDay()->setTime(8, 0),
                'end_at' => now()->addMonths(4)->endOfDay()->setTime(20, 0),
                'location_name' => 'Downtown Market Square',
                'address_line1' => '789 Market Street',
                'city' => 'San Francisco',
                'state_region' => 'CA',
                'postal_code' => '94102',
                'description' => 'Outdoor yarn market with multiple vendors, food trucks, and live music.',
                'website' => 'https://downtownyarnmarket.example.com',
            ],
            [
                'name' => 'Fall Fiber Arts Fair',
                'start_at' => now()->addMonths(6)->startOfDay()->setTime(9, 0),
                'end_at' => now()->addMonths(6)->addDays(3)->endOfDay()->setTime(17, 0),
                'location_name' => 'Fairgrounds',
                'address_line1' => '1000 Fairgrounds Road',
                'city' => 'Denver',
                'state_region' => 'CO',
                'postal_code' => '80202',
                'description' => 'Multi-day fiber arts fair with workshops, demonstrations, and vendor booths.',
                'website' => 'https://fallfiberartsfair.example.com',
            ],
            [
                'name' => 'Holiday Market',
                'start_at' => now()->addMonths(8)->startOfDay()->setTime(10, 0),
                'end_at' => now()->addMonths(8)->endOfDay()->setTime(16, 0),
                'location_name' => 'Community Center',
                'address_line1' => '555 Elm Street',
                'city' => 'Boulder',
                'state_region' => 'CO',
                'postal_code' => '80301',
                'description' => 'Holiday market featuring hand-dyed yarns perfect for gift knitting.',
                'website' => null,
            ],
            [
                'name' => 'Summer Yarn Expo',
                'start_at' => now()->addWeeks(2)->startOfDay()->setTime(9, 0),
                'end_at' => now()->addWeeks(2)->addDays(1)->endOfDay()->setTime(17, 0),
                'location_name' => 'Expo Center',
                'address_line1' => '999 Expo Boulevard',
                'city' => 'Chicago',
                'state_region' => 'IL',
                'postal_code' => '60601',
                'description' => 'Summer yarn expo featuring national and international vendors.',
                'website' => 'https://summeryarnexpo.example.com',
            ],
        ];

        $allShows = array_merge($pastShows, $futureShows);

        foreach ($allShows as $showData) {
            Show::create([
                'account_id' => $account->id,
                'name' => $showData['name'],
                'start_at' => $showData['start_at'],
                'end_at' => $showData['end_at'],
                'location_name' => $showData['location_name'],
                'address_line1' => $showData['address_line1'],
                'city' => $showData['city'],
                'state_region' => $showData['state_region'],
                'postal_code' => $showData['postal_code'],
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
        $integration = Integration::where('account_id', $account->id)->first();

        // Create orders with specific status distribution:
        // 124 delivered, 12 cancelled, 10 open, 4 draft
        $statusDistribution = [
            [OrderStatus::Delivered, 124],
            [OrderStatus::Cancelled, 12],
            [OrderStatus::Open, 10],
            [OrderStatus::Draft, 4],
        ];

        foreach ($statusDistribution as [$status, $count]) {
            Order::factory()
                ->count($count)
                ->create([
                    'account_id' => $account->id,
                    'status' => $status,
                    'created_by' => $user?->id,
                ])
                ->each(function ($order) use ($integration) {
                    // Create external identifier for retail orders (70% chance)
                    if ($integration && $order->type === OrderType::Retail && fake()->boolean(70)) {
                        ExternalIdentifier::create([
                            'integration_id' => $integration->id,
                            'identifiable_type' => Order::class,
                            'identifiable_id' => $order->id,
                            'external_type' => 'order',
                            'external_id' => fake()->numerify('##########'),
                        ]);
                    }
                });
        }
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

            // Recalculate order totals after creating items
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
