<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Colorway;
use App\Models\Dye;
use Illuminate\Database\Seeder;

class DyeSeeder extends Seeder
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
}
