<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
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
    }
}
