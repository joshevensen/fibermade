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
            DB::table('account_user')->updateOrInsert(
                [
                    'account_id' => $account->id,
                    'user_id' => $josh->id,
                ],
                [
                    'role' => UserRole::Owner->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        if ($kristen) {
            DB::table('account_user')->updateOrInsert(
                [
                    'account_id' => $account->id,
                    'user_id' => $kristen->id,
                ],
                [
                    'role' => UserRole::Owner->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
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
            DB::table('account_user')->updateOrInsert(
                [
                    'account_id' => $yarnivoreAccount->id,
                    'user_id' => $caryn->id,
                ],
                [
                    'role' => UserRole::Owner->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        if ($han) {
            DB::table('account_user')->updateOrInsert(
                [
                    'account_id' => $yarnivoreAccount->id,
                    'user_id' => $han->id,
                ],
                [
                    'role' => UserRole::Owner->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
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
