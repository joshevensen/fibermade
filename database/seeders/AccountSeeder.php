<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\UserRole;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $account = Account::create([
            'type' => AccountType::Creator->value,
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

        // Yarnivore account (Store type)
        $yarnivoreAccount = Account::create([
            'type' => AccountType::Store->value,
            'status' => 'active',
            'name' => 'Yarnivore',
        ]);

        $caryn = User::where('email', 'caryn@yarnivoresa.net')->first();
        $han = User::where('email', 'han@yarnivoresa.net')->first();

        if ($caryn) {
            $caryn->update([
                'account_id' => $yarnivoreAccount->id,
                'role' => UserRole::Owner->value,
            ]);
        }

        if ($han) {
            $han->update([
                'account_id' => $yarnivoreAccount->id,
                'role' => UserRole::Owner->value,
            ]);
        }

        // Bad Frog Yarn Co. wholesales to Yarnivore
        DB::table('store_vendor')->updateOrInsert(
            [
                'store_id' => $yarnivoreAccount->id,
                'vendor_id' => $account->id,
            ],
            [
                'discount_rate' => 60,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
