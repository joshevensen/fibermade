<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Creator>
 */
class CreatorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory()->creator(),
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'address_line1' => fake()->optional()->streetAddress(),
            'address_line2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->optional()->city(),
            'state_region' => fake()->optional()->stateAbbr(),
            'postal_code' => fake()->optional()->postcode(),
            'country_code' => fake()->optional()->randomElement(['US', 'CA', 'GB', 'AU', 'NZ']),
        ];
    }
}
