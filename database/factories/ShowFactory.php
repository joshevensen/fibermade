<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Show>
 */
class ShowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $showNames = [
            'Spring Fiber Festival',
            'Downtown Yarn Market',
            'Trunk Show at Local Yarn Store',
            'Fall Fiber Arts Fair',
            'Holiday Market',
            'Summer Yarn Festival',
            'Community Craft Market',
            'Fiber Arts Expo',
        ];

        $locationNames = [
            'Community Center',
            'Convention Hall',
            'Local Yarn Store',
            'Downtown Market',
            'Fairgrounds',
            'Craft Center',
            'Exhibition Hall',
        ];

        $startAt = fake()->dateTimeBetween('now', '+1 year');
        $endAt = fake()->dateTimeBetween($startAt, (clone $startAt)->modify('+7 days'));

        return [
            'account_id' => \App\Models\Account::factory(),
            'name' => fake()->randomElement($showNames),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'location_name' => fake()->optional(0.8)->randomElement($locationNames),
            'location_address' => fake()->optional(0.7)->streetAddress(),
            'location_city' => fake()->optional(0.7)->city(),
            'location_state' => fake()->optional(0.7)->stateAbbr(),
            'location_zip' => fake()->optional(0.7)->postcode(),
            'description' => fake()->optional(0.6)->paragraph(),
            'website' => fake()->optional(0.4)->url(),
        ];
    }
}
