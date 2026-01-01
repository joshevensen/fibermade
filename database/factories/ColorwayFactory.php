<?php

namespace Database\Factories;

use App\Enums\Color;
use App\Enums\ColorwayStatus;
use App\Enums\Technique;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Colorway>
 */
class ColorwayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $colorwayNames = [
            'Midnight Sky',
            'Sunset Glow',
            'Ocean Wave',
            'Forest Floor',
            'Autumn Leaves',
            'Rose Petal',
            'Lavender Fields',
            'Golden Hour',
            'Storm Cloud',
            'Peacock Feather',
            'Cherry Blossom',
            'Mountain Mist',
            'Desert Bloom',
            'Arctic Ice',
            'Coral Reef',
        ];

        $name = fake()->randomElement($colorwayNames);
        $technique = fake()->randomElement(Technique::cases());
        $status = fake()->randomElement(ColorwayStatus::cases());

        // Generate 1-3 colors for the colorway
        $colorCount = fake()->numberBetween(1, 3);
        $colors = collect(Color::cases())
            ->random($colorCount)
            ->map(fn ($color) => $color->value)
            ->toArray();

        return [
            'name' => $name,
            'description' => fake()->optional(0.7)->sentence(),
            'technique' => $technique,
            'colors' => $colors,
            'per_pan' => fake()->numberBetween(1, 6),
            'recipe' => fake()->optional(0.5)->paragraph(),
            'notes' => fake()->optional(0.4)->sentence(),
            'status' => $status,
        ];
    }
}
