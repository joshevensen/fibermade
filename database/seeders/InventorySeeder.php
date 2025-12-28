<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Base;
use App\Models\Colorway;
use App\Models\Inventory;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
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

        $bases = Base::where('account_id', $account->id)->get();
        $colorways = Colorway::where('account_id', $account->id)->get();

        if ($bases->isEmpty() || $colorways->isEmpty()) {
            return;
        }

        // Create inventory entries for various colorway + base combinations
        // Not every combination, but a good sampling
        foreach ($colorways->take(10) as $colorway) {
            // Each colorway gets inventory for 2-3 different bases
            $selectedBases = $bases->random(fake()->numberBetween(2, 3));

            foreach ($selectedBases as $base) {
                Inventory::create([
                    'account_id' => $account->id,
                    'colorway_id' => $colorway->id,
                    'base_id' => $base->id,
                    'quantity' => fake()->numberBetween(5, 50),
                ]);
            }
        }
    }
}
