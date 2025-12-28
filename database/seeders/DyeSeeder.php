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
            ['name' => 'Turquoise Blue', 'notes' => 'Vibrant and colorfast', 'does_bleed' => false, 'do_like' => true],
            ['name' => 'Scarlet Red', 'notes' => 'Rich red, slight bleeding', 'does_bleed' => true, 'do_like' => true],
            ['name' => 'Forest Green', 'notes' => 'Deep emerald green', 'does_bleed' => false, 'do_like' => true],
            ['name' => 'Sunset Orange', 'notes' => 'Warm orange with yellow undertones', 'does_bleed' => false, 'do_like' => true],
            ['name' => 'Lavender Purple', 'notes' => 'Soft purple, very colorfast', 'does_bleed' => false, 'do_like' => true],
            ['name' => 'Golden Yellow', 'notes' => 'Bright yellow, excellent coverage', 'does_bleed' => false, 'do_like' => true],
            ['name' => 'Charcoal Black', 'notes' => 'Deep black, may bleed', 'does_bleed' => true, 'do_like' => true],
            ['name' => 'Ivory White', 'notes' => 'Natural white base', 'does_bleed' => false, 'do_like' => true],
            ['name' => 'Rose Pink', 'notes' => 'Delicate pink, colorfast', 'does_bleed' => false, 'do_like' => true],
            ['name' => 'Ocean Teal', 'notes' => 'Blue-green blend', 'does_bleed' => false, 'do_like' => true],
            ['name' => 'Amber Brown', 'notes' => 'Warm brown with orange hints', 'does_bleed' => false, 'do_like' => true],
            ['name' => 'Coral Peach', 'notes' => 'Soft coral with pink undertones', 'does_bleed' => false, 'do_like' => true],
        ];

        $createdDyes = [];

        foreach ($dyes as $dyeData) {
            $dye = Dye::create([
                'account_id' => $account->id,
                'name' => $dyeData['name'],
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
