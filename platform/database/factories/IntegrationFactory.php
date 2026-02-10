<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Integration>
 */
class IntegrationFactory extends Factory
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
            'type' => \App\Enums\IntegrationType::Shopify,
            'credentials' => 'test-credentials-'.fake()->uuid(),
            'settings' => ['store_url' => 'https://store-'.fake()->unique()->numberBetween(10000, 99999).'.myshopify.com'],
            'active' => true,
        ];
    }
}
