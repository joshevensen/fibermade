<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dye>
 */
class DyeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dyeNames = [
            'Turquoise Blue',
            'Scarlet Red',
            'Forest Green',
            'Sunset Orange',
            'Lavender Purple',
            'Golden Yellow',
            'Charcoal Black',
            'Ivory White',
            'Rose Pink',
            'Ocean Teal',
            'Amber Brown',
            'Coral Peach',
        ];

        return [
            'name' => fake()->randomElement($dyeNames),
            'manufacturer' => fake()->randomElement(['Dharma', 'Jacquard', 'Other Manufacturer']),
            'notes' => fake()->optional(0.6)->sentence(),
            'does_bleed' => fake()->boolean(30),
            'do_like' => fake()->boolean(80),
        ];
    }
}
