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
use App\Models\Creator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection as SupportCollection;

class BadFrogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $creator = Creator::where('name', 'Bad Frog Yarn Co.')->first();

        if (! $creator || ! $creator->account) {
            $this->command->warn('Bad Frog Yarn Co. account not found. Please run FoundationSeeder first.');

            return;
        }

        $account = $creator->account;

        $this->seedBases($account);
        $collections = $this->seedCollections($account);
        $colorways = $this->seedColorways($account);
        $this->attachColorwaysToCollections($colorways, $collections);
    }

    /**
     * Seed bases.
     */
    protected function seedBases(Account $account): void
    {
        $bases = [
            [
                'descriptor' => 'Lilypad Fingering',
                'status' => BaseStatus::Active,
                'weight' => Weight::Fingering,
                'size' => 100,
                'retail_price' => 28.00,
            ],
            [
                'descriptor' => 'Lilypad DK',
                'status' => BaseStatus::Active,
                'weight' => Weight::DK,
                'size' => 100,
                'retail_price' => 28.00,
            ],
            [
                'descriptor' => 'Hopper Sock',
                'status' => BaseStatus::Active,
                'weight' => Weight::Fingering,
                'size' => 100,
                'retail_price' => 28.00,
            ],
            [
                'descriptor' => 'Pollywog',
                'status' => BaseStatus::Active,
                'weight' => Weight::Fingering,
                'size' => 100,
                'retail_price' => 30.00,
            ],
        ];

        foreach ($bases as $baseData) {
            Base::firstOrCreate(
                [
                    'account_id' => $account->id,
                    'descriptor' => $baseData['descriptor'],
                    'weight' => $baseData['weight'],
                ],
                [
                    'status' => $baseData['status'],
                    'code' => Base::generateCodeFromDescriptor($baseData['descriptor']),
                    'size' => $baseData['size'],
                    'retail_price' => $baseData['retail_price'],
                ]
            );
        }
    }

    /**
     * Seed collections.
     *
     * @return SupportCollection<int, Collection>
     */
    protected function seedCollections(Account $account): SupportCollection
    {
        $collections = [
            ['name' => 'Lilypad Classics', 'description' => 'Timeless colorways inspired by pond life and water lilies.'],
            ['name' => 'Hopper Favorites', 'description' => 'Best-selling colorways perfect for socks and everyday wear.'],
            ['name' => 'Pollywog Playground', 'description' => 'Vibrant, playful colorways for the young at heart.'],
            ['name' => 'Marsh & Meadow', 'description' => 'Earthy tones from wetlands and summer meadows.'],
            ['name' => 'Twilight Pond', 'description' => 'Dusk and dawn colorways with subtle shifts.'],
            ['name' => 'Frog Song', 'description' => 'Bold, chorus-inspired colorways that make a statement.'],
            ['name' => 'Seasonal Splash', 'description' => 'Limited seasonal colorways throughout the year.'],
        ];

        $created = new SupportCollection;

        foreach ($collections as $data) {
            $collection = Collection::firstOrCreate(
                [
                    'account_id' => $account->id,
                    'name' => $data['name'],
                ],
                [
                    'description' => $data['description'],
                    'status' => BaseStatus::Active,
                ]
            );
            $created->push($collection);
        }

        return $created;
    }

    /**
     * Seed colorways.
     *
     * @return SupportCollection<int, Colorway>
     */
    protected function seedColorways(Account $account): SupportCollection
    {
        $names = [
            'Lilypad Green', 'Pond Scum', 'Tadpole', 'Frog Spawn', 'Bullfrog',
            'Tree Frog', 'Spring Peeper', 'Marsh Mist', 'Reed Bed', 'Cattail',
            'Duckweed', 'Water Lily', 'Lotus Bloom', 'Frog Prince', 'Peat Bog',
            'Bog Rosemary', 'Sundew', 'Pitcher Plant', 'Cranberry Bog', 'Evening Pool',
            'Morning Dew', 'Dragonfly', 'Damselfly', 'Mayfly', 'Caddisfly',
            'Water Strider', 'Salamander', 'Newt', 'Minnow', 'Frog Song',
            'Croak', 'Ribbit', 'Leap', 'Hibernation', 'Thaw',
            'Polliwog', 'Frog Belly', 'Tree Bark', 'Lichen', 'Moss',
            'Fern', 'Fern Gully', 'Canopy', 'Understory', 'Fallen Log',
            'Creek Bed', 'Stream Stone', 'River Rock', 'Wet Sand', 'Riverbank',
            'Flood Plain', 'Delta', 'Estuary', 'Tide Pool', 'Mangrove',
            'Cypress Knee', 'Swamp Gas', 'Bog Light', 'Reflection', 'Ripple',
            'Still Water', 'Bubble', 'Algae', 'Kelp', 'Seaweed',
            'Otter', 'Beaver', 'Muskrat', 'Heron', 'Kingfisher',
        ];

        $created = new SupportCollection;
        $colorCases = collect(Color::cases());
        $techniques = Technique::cases();

        foreach ($names as $name) {
            $colorCount = fake()->numberBetween(1, 3);
            $colors = $colorCases->random($colorCount)->map(fn (Color $c) => $c->value)->values()->all();
            $technique = fake()->randomElement($techniques);

            $colorway = Colorway::firstOrCreate(
                [
                    'account_id' => $account->id,
                    'name' => $name,
                ],
                [
                    'description' => fake()->boolean(70) ? "Hand-dyed {$name}." : null,
                    'technique' => $technique,
                    'colors' => $colors,
                    'per_pan' => fake()->numberBetween(1, 6),
                    'recipe' => fake()->boolean(50) ? 'Proprietary recipe.' : null,
                    'notes' => fake()->boolean(40) ? 'Small-batch dyed.' : null,
                    'status' => ColorwayStatus::Active,
                ]
            );
            $created->push($colorway);
        }

        return $created;
    }

    /**
     * Attach colorways to collections (10 per collection, 70 total).
     *
     * @param  SupportCollection<int, Colorway>  $colorways
     * @param  SupportCollection<int, Collection>  $collections
     */
    protected function attachColorwaysToCollections(SupportCollection $colorways, SupportCollection $collections): void
    {
        $perCollection = (int) floor($colorways->count() / $collections->count());
        $index = 0;

        foreach ($collections as $collection) {
            $chunk = $colorways->slice($index, $perCollection);
            foreach ($chunk as $colorway) {
                $collection->colorways()->syncWithoutDetaching([$colorway->id]);
            }
            $index += $perCollection;
        }
    }
}
