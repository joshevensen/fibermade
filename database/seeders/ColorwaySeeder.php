<?php

namespace Database\Seeders;

use App\Enums\Color;
use App\Enums\ColorwayStatus;
use App\Enums\Technique;
use App\Models\Account;
use App\Models\Collection;
use App\Models\Colorway;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ColorwaySeeder extends Seeder
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
                'slug' => Str::slug($colorwayData['name']),
                'description' => $colorwayData['description'],
                'technique' => $colorwayData['technique'],
                'colors' => $colorwayData['colors'],
                'status' => $colorwayData['status'],
                'created_by' => $user?->id,
            ]);

            if ($colorwayData['collection']) {
                $colorway->collections()->attach($colorwayData['collection']->id);
            }
        }
    }
}
