<?php

namespace Database\Seeders;

use App\Enums\BaseStatus;
use App\Enums\Color;
use App\Enums\ColorwayStatus;
use App\Enums\Technique;
use App\Enums\Weight;
use App\Models\Account;
use App\Models\Base;
use App\Models\Collection;
use App\Models\Colorway;
use App\Models\Dye;
use App\Models\ExternalIdentifier;
use App\Models\Integration;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
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

        $this->seedBases($account);
        $this->seedCollections($account);
        $this->seedColorways($account);
        $this->seedDyes($account);
        $this->seedInventory($account);
        $this->seedMedia();
    }

    /**
     * Seed bases.
     */
    protected function seedBases(Account $account): void
    {
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

    /**
     * Seed collections.
     */
    protected function seedCollections(Account $account): void
    {
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
                'description' => $collectionData['description'],
                'status' => BaseStatus::Active,
            ]);
        }
    }

    /**
     * Seed colorways.
     */
    protected function seedColorways(Account $account): void
    {
        $user = User::where('account_id', $account->id)->first();

        $fallCollection = Collection::where('account_id', $account->id)
            ->where('name', 'Fall Collection')
            ->first();

        $oceanCollection = Collection::where('account_id', $account->id)
            ->where('name', 'Ocean Depths')
            ->first();

        $rainbowCollection = Collection::where('account_id', $account->id)
            ->where('name', 'Rainbow Series')
            ->first();

        $colorways = [
            // Fall Collection
            [
                'name' => 'Pumpkin Spice',
                'description' => 'Warm orange with hints of brown and gold.',
                'technique' => Technique::Variegated,
                'colors' => [Color::Orange->value, Color::Brown->value, Color::Yellow->value],
                'status' => ColorwayStatus::Active,
                'collection' => $fallCollection,
            ],
            [
                'name' => 'Autumn Leaves',
                'description' => 'Rich reds, oranges, and yellows like fall foliage.',
                'technique' => Technique::Variegated,
                'colors' => [Color::Red->value, Color::Orange->value, Color::Yellow->value],
                'status' => ColorwayStatus::Active,
                'collection' => $fallCollection,
            ],
            [
                'name' => 'Chestnut',
                'description' => 'Deep brown with warm undertones.',
                'technique' => Technique::Tonal,
                'colors' => [Color::Brown->value],
                'status' => ColorwayStatus::Active,
                'collection' => $fallCollection,
            ],
            [
                'name' => 'Harvest Gold',
                'description' => 'Golden yellow reminiscent of wheat fields.',
                'technique' => Technique::Solid,
                'colors' => [Color::Yellow->value],
                'status' => ColorwayStatus::Active,
                'collection' => $fallCollection,
            ],

            // Ocean Depths
            [
                'name' => 'Deep Blue Sea',
                'description' => 'Rich navy blue with depth and dimension.',
                'technique' => Technique::Tonal,
                'colors' => [Color::Navy->value, Color::Blue->value],
                'status' => ColorwayStatus::Active,
                'collection' => $oceanCollection,
            ],
            [
                'name' => 'Turquoise Wave',
                'description' => 'Vibrant turquoise with lighter blue highlights.',
                'technique' => Technique::Variegated,
                'colors' => [Color::Turquoise->value, Color::Blue->value],
                'status' => ColorwayStatus::Active,
                'collection' => $oceanCollection,
            ],
            [
                'name' => 'Seafoam',
                'description' => 'Soft minty green-blue like ocean foam.',
                'technique' => Technique::Solid,
                'colors' => [Color::Teal->value],
                'status' => ColorwayStatus::Active,
                'collection' => $oceanCollection,
            ],
            [
                'name' => 'Coral Reef',
                'description' => 'Bright coral pink with orange undertones.',
                'technique' => Technique::Variegated,
                'colors' => [Color::Coral->value, Color::Pink->value],
                'status' => ColorwayStatus::Active,
                'collection' => $oceanCollection,
            ],

            // Rainbow Series
            [
                'name' => 'Rainbow Bright',
                'description' => 'Full spectrum rainbow in vibrant colors.',
                'technique' => Technique::Variegated,
                'colors' => [Color::Red->value, Color::Orange->value, Color::Yellow->value, Color::Green->value, Color::Blue->value, Color::Purple->value],
                'status' => ColorwayStatus::Active,
                'collection' => $rainbowCollection,
            ],
            [
                'name' => 'Sunset Gradient',
                'description' => 'Smooth gradient from pink through orange to yellow.',
                'technique' => Technique::Variegated,
                'colors' => [Color::Pink->value, Color::Orange->value, Color::Yellow->value],
                'status' => ColorwayStatus::Active,
                'collection' => $rainbowCollection,
            ],
            [
                'name' => 'Royal Purple',
                'description' => 'Rich, deep purple fit for royalty.',
                'technique' => Technique::Solid,
                'colors' => [Color::Purple->value],
                'status' => ColorwayStatus::Active,
                'collection' => $rainbowCollection,
            ],
            [
                'name' => 'Emerald Green',
                'description' => 'Vibrant green like precious gemstones.',
                'technique' => Technique::Tonal,
                'colors' => [Color::Green->value],
                'status' => ColorwayStatus::Active,
                'collection' => $rainbowCollection,
            ],

            // Standalone colorways
            [
                'name' => 'Midnight Sky',
                'description' => 'Deep black with subtle blue undertones.',
                'technique' => Technique::Tonal,
                'colors' => [Color::Black->value, Color::Navy->value],
                'status' => ColorwayStatus::Active,
                'collection' => null,
            ],
            [
                'name' => 'Rose Petal',
                'description' => 'Soft pink like delicate rose petals.',
                'technique' => Technique::Solid,
                'colors' => [Color::Pink->value],
                'status' => ColorwayStatus::Active,
                'collection' => null,
            ],
            [
                'name' => 'Speckled Gray',
                'description' => 'Neutral gray with colorful speckles throughout.',
                'technique' => Technique::Speckled,
                'colors' => [Color::Gray->value, Color::Blue->value, Color::Pink->value],
                'status' => ColorwayStatus::Active,
                'collection' => null,
            ],
        ];

        foreach ($colorways as $colorwayData) {
            $colorway = Colorway::create([
                'account_id' => $account->id,
                'name' => $colorwayData['name'],
                'description' => $colorwayData['description'],
                'technique' => $colorwayData['technique'],
                'colors' => $colorwayData['colors'],
                'per_pan' => $colorwayData['per_pan'] ?? fake()->numberBetween(1, 6),
                'recipe' => $colorwayData['recipe'] ?? null,
                'notes' => $colorwayData['notes'] ?? null,
                'status' => $colorwayData['status'],
                'created_by' => $user?->id,
            ]);

            if ($colorwayData['collection']) {
                $colorway->collections()->attach($colorwayData['collection']->id);
            }

            // Create external identifier for some colorways (30% chance)
            if (fake()->boolean(30)) {
                $integration = Integration::where('account_id', $account->id)->first();
                if ($integration) {
                    ExternalIdentifier::create([
                        'integration_id' => $integration->id,
                        'identifiable_type' => Colorway::class,
                        'identifiable_id' => $colorway->id,
                        'external_type' => 'product',
                        'external_id' => fake()->numerify('##########'),
                    ]);
                }
            }
        }
    }

    /**
     * Seed dyes.
     */
    protected function seedDyes(Account $account): void
    {
        $dyes = [
            ['name' => 'Turquoise Blue', 'manufacturer' => 'Dharma', 'notes' => 'Vibrant and colorfast', 'does_bleed' => false, 'do_like' => true],
            ['name' => 'Scarlet Red', 'manufacturer' => 'Jacquard', 'notes' => 'Rich red, slight bleeding', 'does_bleed' => true, 'do_like' => true],
            ['name' => 'Forest Green', 'manufacturer' => 'Dharma', 'notes' => 'Deep emerald green', 'does_bleed' => false, 'do_like' => true],
            ['name' => 'Sunset Orange', 'manufacturer' => 'Jacquard', 'notes' => 'Warm orange with yellow undertones', 'does_bleed' => false, 'do_like' => true],
            ['name' => 'Lavender Purple', 'manufacturer' => 'Dharma', 'notes' => 'Soft purple, very colorfast', 'does_bleed' => false, 'do_like' => true],
            ['name' => 'Golden Yellow', 'manufacturer' => 'Jacquard', 'notes' => 'Bright yellow, excellent coverage', 'does_bleed' => false, 'do_like' => true],
            ['name' => 'Charcoal Black', 'manufacturer' => 'Dharma', 'notes' => 'Deep black, may bleed', 'does_bleed' => true, 'do_like' => true],
            ['name' => 'Ivory White', 'manufacturer' => 'Jacquard', 'notes' => 'Natural white base', 'does_bleed' => false, 'do_like' => true],
            ['name' => 'Rose Pink', 'manufacturer' => 'Dharma', 'notes' => 'Delicate pink, colorfast', 'does_bleed' => false, 'do_like' => true],
            ['name' => 'Ocean Teal', 'manufacturer' => 'Other Manufacturer', 'notes' => 'Blue-green blend', 'does_bleed' => false, 'do_like' => true],
            ['name' => 'Amber Brown', 'manufacturer' => 'Jacquard', 'notes' => 'Warm brown with orange hints', 'does_bleed' => false, 'do_like' => true],
            ['name' => 'Coral Peach', 'manufacturer' => 'Dharma', 'notes' => 'Soft coral with pink undertones', 'does_bleed' => false, 'do_like' => true],
        ];

        $createdDyes = [];

        foreach ($dyes as $dyeData) {
            $dye = Dye::create([
                'account_id' => $account->id,
                'name' => $dyeData['name'],
                'manufacturer' => $dyeData['manufacturer'],
                'notes' => $dyeData['notes'],
                'does_bleed' => $dyeData['does_bleed'],
                'do_like' => $dyeData['do_like'],
            ]);

            $createdDyes[] = $dye;
        }

        // Link some dyes to colorways with pivot data
        $colorways = Colorway::where('account_id', $account->id)->get();
        $createdDyesCollection = collect($createdDyes);

        foreach ($colorways->take(8) as $colorway) {
            $selectedDyes = $createdDyesCollection->random(fake()->numberBetween(1, 3));

            foreach ($selectedDyes as $dye) {
                $colorway->dyes()->attach($dye->id, [
                    'dry_weight' => fake()->randomFloat(2, 0.5, 5.0),
                    'concentration' => fake()->randomFloat(2, 0.1, 2.0),
                    'wet_amount' => fake()->randomFloat(2, 10, 100),
                    'notes' => fake()->optional(0.5)->sentence(),
                ]);
            }
        }
    }

    /**
     * Seed inventory.
     */
    protected function seedInventory(Account $account): void
    {
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
                $inventory = Inventory::create([
                    'account_id' => $account->id,
                    'colorway_id' => $colorway->id,
                    'base_id' => $base->id,
                    'quantity' => fake()->numberBetween(5, 50),
                ]);

                // Create external identifier for some inventory entries (30% chance)
                if (fake()->boolean(30)) {
                    $integration = Integration::where('account_id', $account->id)->first();
                    if ($integration) {
                        ExternalIdentifier::create([
                            'integration_id' => $integration->id,
                            'identifiable_type' => Inventory::class,
                            'identifiable_id' => $inventory->id,
                            'external_type' => 'variant',
                            'external_id' => fake()->numerify('##########'),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Seed media.
     */
    protected function seedMedia(): void
    {
        // Placeholder for future media seeding
    }
}
