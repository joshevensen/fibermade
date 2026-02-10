<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IntegrationLog>
 */
class IntegrationLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'integration_id' => \App\Models\Integration::factory(),
            'loggable_type' => \App\Models\Order::class,
            'loggable_id' => 1,
            'status' => \App\Enums\IntegrationLogStatus::Success,
            'message' => fake()->sentence(),
            'metadata' => null,
            'synced_at' => now(),
        ];
    }
}
