<?php

namespace Database\Seeders;

use App\Enums\BaseStatus;
use App\Enums\Weight;
use App\Models\Account;
use App\Models\Base;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BaseSeeder extends Seeder
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

        $bases = [
            [
                'name' => 'Merino Worsted',
                'description' => 'Super soft 100% merino wool in worsted weight. Perfect for cozy sweaters and accessories.',
                'status' => BaseStatus::Active,
                'weight' => Weight::Worsted,
                'descriptor' => 'Superwash',
                'size' => 218,
                'cost' => 12.50,
                'retail_price' => 28.00,
                'wool_percent' => 100.00,
            ],
            [
                'name' => 'DK Alpaca Blend',
                'description' => 'Luxurious blend of alpaca and merino wool in DK weight. Lightweight and warm.',
                'status' => BaseStatus::Active,
                'weight' => Weight::DK,
                'descriptor' => 'Non-superwash',
                'size' => 250,
                'cost' => 15.00,
                'retail_price' => 32.00,
                'wool_percent' => 60.00,
                'alpaca_percent' => 40.00,
            ],
            [
                'name' => 'Fingering Weight Superwash',
                'description' => 'Fine gauge superwash merino perfect for socks and shawls.',
                'status' => BaseStatus::Active,
                'weight' => Weight::Fingering,
                'descriptor' => 'Superwash',
                'size' => 400,
                'cost' => 10.00,
                'retail_price' => 24.00,
                'wool_percent' => 80.00,
                'nylon_percent' => 20.00,
            ],
            [
                'name' => 'Bulky Merino',
                'description' => 'Chunky weight merino wool for quick projects and cozy blankets.',
                'status' => BaseStatus::Active,
                'weight' => Weight::Bulky,
                'descriptor' => 'Superwash',
                'size' => 100,
                'cost' => 18.00,
                'retail_price' => 38.00,
                'wool_percent' => 100.00,
            ],
            [
                'name' => 'Lace Weight Silk Blend',
                'description' => 'Delicate lace weight yarn with silk for elegant shawls and lacework.',
                'status' => BaseStatus::Active,
                'weight' => Weight::Lace,
                'descriptor' => 'Hand-dyed',
                'size' => 800,
                'cost' => 22.00,
                'retail_price' => 45.00,
                'wool_percent' => 70.00,
                'cotton_percent' => 30.00,
            ],
        ];

        foreach ($bases as $baseData) {
            Base::create([
                'account_id' => $account->id,
                'name' => $baseData['name'],
                'slug' => Str::slug($baseData['name']),
                'description' => $baseData['description'],
                'status' => $baseData['status'],
                'weight' => $baseData['weight'],
                'descriptor' => $baseData['descriptor'],
                'size' => $baseData['size'],
                'cost' => $baseData['cost'],
                'retail_price' => $baseData['retail_price'],
                'wool_percent' => $baseData['wool_percent'] ?? null,
                'alpaca_percent' => $baseData['alpaca_percent'] ?? null,
                'nylon_percent' => $baseData['nylon_percent'] ?? null,
                'yak_percent' => $baseData['yak_percent'] ?? null,
                'camel_percent' => $baseData['camel_percent'] ?? null,
                'cotton_percent' => $baseData['cotton_percent'] ?? null,
                'bamboo_percent' => $baseData['bamboo_percent'] ?? null,
            ]);
        }
    }
}
