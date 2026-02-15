<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\BaseStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Account;
use App\Models\Creator;
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
            'subscription_status' => SubscriptionStatus::Active,
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
}
