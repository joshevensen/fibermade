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
            'account_id' => \App\Models\Account::factory(),
            'name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'owner_name' => fake()->optional()->name(),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'zip' => fake()->postcode(),
            'country' => fake()->country(),
            'discount_rate' => fake()->optional()->randomFloat(2, 0, 50),
            'minimum_order_quantity' => fake()->optional()->numberBetween(1, 100),
            'minimum_order_value' => fake()->optional()->randomFloat(2, 10, 1000),
            'payment_terms' => fake()->optional()->randomElement(['Net 30', 'Net 60', 'Due on Receipt', '50% Deposit']),
            'lead_time_days' => fake()->optional()->numberBetween(7, 60),
            'allows_preorders' => fake()->boolean(30),
            'status' => 'active',
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
