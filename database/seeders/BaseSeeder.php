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
                'descriptor' => 'Merino Worsted',
                'description' => 'Super soft 100% merino wool in worsted weight. Perfect for cozy sweaters and accessories. Superwash.',
                'status' => BaseStatus::Active,
                'weight' => Weight::Worsted,
                'size' => 50,
                'cost' => 12.50,
                'retail_price' => 28.00,
                'wool_percent' => 100.00,
            ],
            [
                'descriptor' => 'DK Alpaca Blend',
                'description' => 'Luxurious blend of alpaca and merino wool in DK weight. Lightweight and warm. Non-superwash.',
                'status' => BaseStatus::Active,
                'weight' => Weight::DK,
                'size' => 50,
                'cost' => 15.00,
                'retail_price' => 32.00,
                'wool_percent' => 60.00,
                'alpaca_percent' => 40.00,
            ],
            [
                'descriptor' => 'Fingering Weight Superwash',
                'description' => 'Fine gauge superwash merino perfect for socks and shawls. Superwash.',
                'status' => BaseStatus::Active,
                'weight' => Weight::Fingering,
                'size' => 100,
                'cost' => 10.00,
                'retail_price' => 24.00,
                'wool_percent' => 80.00,
                'nylon_percent' => 20.00,
            ],
            [
                'descriptor' => 'Bulky Merino',
                'description' => 'Chunky weight merino wool for quick projects and cozy blankets. Superwash.',
                'status' => BaseStatus::Active,
                'weight' => Weight::Bulky,
                'size' => 20,
                'cost' => 18.00,
                'retail_price' => 38.00,
                'wool_percent' => 100.00,
            ],
            [
                'descriptor' => 'Lace Weight Silk Blend',
                'description' => 'Delicate lace weight yarn with silk for elegant shawls and lacework. Hand-dyed.',
                'status' => BaseStatus::Active,
                'weight' => Weight::Lace,
                'size' => 100,
                'cost' => 22.00,
                'retail_price' => 45.00,
                'wool_percent' => 70.00,
                'cotton_percent' => 30.00,
            ],
        ];

        foreach ($bases as $baseData) {
            Base::create([
                'account_id' => $account->id,
                'slug' => Str::slug($baseData['descriptor']),
                'description' => $baseData['description'],
                'status' => $baseData['status'],
                'weight' => $baseData['weight'],
                'descriptor' => $baseData['descriptor'],
                'code' => Base::generateCodeFromDescriptor($baseData['descriptor']),
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
