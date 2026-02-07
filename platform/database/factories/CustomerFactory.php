<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => \App\Models\Account::factory()->creator(),
            'name' => fake()->name(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'address_line1' => fake()->optional()->streetAddress(),
            'address_line2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->optional()->city(),
            'state_region' => fake()->optional()->stateAbbr(),
            'postal_code' => fake()->optional()->postcode(),
            'country_code' => fake()->optional()->randomElement(['US', 'CA', 'GB', 'AU', 'NZ']),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
