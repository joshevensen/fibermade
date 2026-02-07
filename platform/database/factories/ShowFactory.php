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
            'account_id' => \App\Models\Account::factory()->creator(),
            'name' => fake()->randomElement($showNames),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'location_name' => fake()->optional(0.8)->randomElement($locationNames),
            'address_line1' => fake()->optional(0.7)->streetAddress(),
            'city' => fake()->optional(0.7)->city(),
            'state_region' => fake()->optional(0.7)->stateAbbr(),
            'postal_code' => fake()->optional(0.7)->postcode(),
            'country_code' => fake()->optional(0.7)->randomElement(['US', 'CA', 'GB', 'AU', 'NZ']),
            'description' => fake()->optional(0.6)->paragraph(),
            'website' => fake()->optional(0.4)->url(),
        ];
    }
}
