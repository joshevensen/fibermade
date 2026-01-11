<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => \App\Models\Account::factory()->storeType(),
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'owner_name' => fake()->optional()->name(),
            'address_line1' => fake()->streetAddress(),
            'address_line2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'state_region' => fake()->stateAbbr(),
            'postal_code' => fake()->postcode(),
            'country_code' => fake()->randomElement(['US', 'CA', 'GB', 'AU', 'NZ']),
        ];
    }
}
