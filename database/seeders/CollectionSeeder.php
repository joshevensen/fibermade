<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CollectionSeeder extends Seeder
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

        $collections = [
            [
                'name' => 'Fall Collection',
                'description' => 'Warm, earthy tones inspired by autumn leaves and cozy sweaters.',
            ],
            [
                'name' => 'Ocean Depths',
                'description' => 'Cool blues, teals, and greens reminiscent of ocean waves and marine life.',
            ],
            [
                'name' => 'Rainbow Series',
                'description' => 'Vibrant, saturated colors spanning the full spectrum.',
            ],
        ];

        foreach ($collections as $collectionData) {
            Collection::create([
                'account_id' => $account->id,
                'name' => $collectionData['name'],
                'slug' => Str::slug($collectionData['name']),
                'description' => $collectionData['description'],
            ]);
        }
    }
}
