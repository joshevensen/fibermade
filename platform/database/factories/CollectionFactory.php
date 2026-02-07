<?php

namespace Database\Factories;

use App\Enums\BaseStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Collection>
 */
class CollectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $collectionNames = [
            'Fall Collection',
            'Rainbow Series',
            'Ocean Depths',
            'Forest Dreams',
            'Sunset Palette',
            'Winter Wonderland',
            'Spring Blooms',
            'Desert Sands',
            'Mountain Vista',
            'Garden Party',
        ];

        $name = fake()->randomElement($collectionNames);
        $status = fake()->randomElement(BaseStatus::cases());

        return [
            'name' => $name,
            'description' => fake()->optional(0.7)->paragraph(),
            'status' => $status,
        ];
    }
}
