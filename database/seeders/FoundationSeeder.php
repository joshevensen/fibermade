<?php

namespace Database\Seeders;

use App\Enums\IntegrationType;
use App\Enums\UserRole;
use App\Models\Account;
use App\Models\Integration;
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
            'status' => 'active',
            'name' => 'Bad Frog Yarn Co.',
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
}
