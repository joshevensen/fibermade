<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\BaseStatus;
use App\Enums\IntegrationType;
use App\Enums\StoreVendorStatus;
use App\Enums\UserRole;
use App\Models\Account;
use App\Models\Creator;
use App\Models\Integration;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FoundationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedUsers();
        $account = $this->seedAccount();
        $this->seedIntegration($account);
        $this->seedStoreAccounts($account);
    }

    /**
     * Seed users.
     */
    protected function seedUsers(): void
    {
        // Admin user (no account)
        User::factory()->create([
            'name' => 'Josh Evensen',
            'email' => 'josh@fibermade.app',
            'is_admin' => true,
            'password' => Hash::make('password'),
        ]);

        // Bad Frog Yarn Co. users (will be associated with account in seedAccount)
        User::factory()->create([
            'name' => 'Josh Evensen',
            'email' => 'josh@badfrogyarnco.com',
            'password' => Hash::make('password'),
        ]);

        User::factory()->create([
            'name' => 'Kristen Matte',
            'email' => 'kristen@badfrogyarnco.com',
            'password' => Hash::make('password'),
        ]);
    }

    /**
     * Seed account and associate users.
     */
    protected function seedAccount(): Account
    {
        $account = Account::create([
            'status' => BaseStatus::Active,
            'type' => AccountType::Creator,
        ]);

        Creator::create([
            'account_id' => $account->id,
            'name' => 'Bad Frog Yarn Co.',
            'email' => 'info@badfrogyarnco.com',
            'phone' => '+1-555-0100',
            'address_line1' => '123 Main Street',
            'city' => 'Portland',
            'state_region' => 'OR',
            'postal_code' => '97201',
            'country_code' => 'US',
        ]);

        $josh = User::where('email', 'josh@badfrogyarnco.com')->first();
        $kristen = User::where('email', 'kristen@badfrogyarnco.com')->first();

        if ($josh) {
            $josh->update([
                'account_id' => $account->id,
                'role' => UserRole::Owner->value,
            ]);
        }

        if ($kristen) {
            $kristen->update([
                'account_id' => $account->id,
                'role' => UserRole::Owner->value,
            ]);
        }

        return $account;
    }

    /**
     * Seed integration for the demo account.
     */
    protected function seedIntegration(Account $account): void
    {
        Integration::create([
            'account_id' => $account->id,
            'type' => IntegrationType::Shopify,
            'credentials' => encrypt(json_encode([
                'shop' => 'demo-shop.myshopify.com',
                'access_token' => 'demo_token',
            ])),
            'settings' => [
                'store_name' => 'Bad Frog Yarn Co.',
            ],
            'active' => true,
        ]);
    }

    /**
     * Seed store accounts and vendor relationships.
     */
    protected function seedStoreAccounts(Account $creatorAccount): void
    {
        $stores = [
            [
                'name' => 'Yarnivore',
                'email' => 'orders@yarnivore.com',
                'owner_name' => 'Sarah Johnson',
                'address_line1' => '456 Yarn Street',
                'city' => 'Seattle',
                'state_region' => 'WA',
                'postal_code' => '98101',
                'country_code' => 'US',
                'user_email' => 'sarah@yarnivore.com',
                'user_name' => 'Sarah Johnson',
            ],
            [
                'name' => 'Unraveled',
                'email' => 'hello@unraveled.com',
                'owner_name' => 'Mike Chen',
                'address_line1' => '789 Fiber Avenue',
                'city' => 'San Francisco',
                'state_region' => 'CA',
                'postal_code' => '94102',
                'country_code' => 'US',
                'user_email' => 'mike@unraveled.com',
                'user_name' => 'Mike Chen',
            ],
            [
                'name' => 'Craftique',
                'email' => 'info@craftique.com',
                'owner_name' => 'Emily Rodriguez',
                'address_line1' => '321 Craft Lane',
                'city' => 'Austin',
                'state_region' => 'TX',
                'postal_code' => '78701',
                'country_code' => 'US',
                'user_email' => 'emily@craftique.com',
                'user_name' => 'Emily Rodriguez',
            ],
        ];

        foreach ($stores as $storeData) {
            // Create store account
            $storeAccount = Account::create([
                'status' => BaseStatus::Active,
                'type' => AccountType::Store,
            ]);

            // Create store record
            $store = Store::create([
                'account_id' => $storeAccount->id,
                'name' => $storeData['name'],
                'email' => $storeData['email'],
                'owner_name' => $storeData['owner_name'],
                'address_line1' => $storeData['address_line1'],
                'city' => $storeData['city'],
                'state_region' => $storeData['state_region'],
                'postal_code' => $storeData['postal_code'],
                'country_code' => $storeData['country_code'],
            ]);

            // Create user for store
            User::create([
                'name' => $storeData['user_name'],
                'email' => $storeData['user_email'],
                'password' => Hash::make('password'),
                'account_id' => $storeAccount->id,
                'role' => UserRole::Owner->value,
                'email_verified_at' => now(),
            ]);

            // Get the creator record for the creator account
            $creator = Creator::where('account_id', $creatorAccount->id)->first();

            if ($creator) {
                // Create vendor relationship between creator and store
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
}
