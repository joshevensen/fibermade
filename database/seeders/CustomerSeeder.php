<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
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

        Customer::factory()
            ->count(10)
            ->create([
                'account_id' => $account->id,
            ]);
    }
}
