<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Enums\BaseStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => BaseStatus::Active,
            'type' => AccountType::Creator,
            'onboarded_at' => null,
        ];
    }

    /**
     * Indicate that the account is a creator type.
     */
    public function creator(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::Creator,
        ]);
    }

    /**
     * Indicate that the account is a store type.
     */
    public function storeType(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::Store,
        ]);
    }

    /**
     * Indicate that the account is a buyer type.
     */
    public function buyer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AccountType::Buyer,
        ]);
    }

    /**
     * Indicate that the account has completed onboarding.
     */
    public function onboarded(): static
    {
        return $this->state(fn (array $attributes) => [
            'onboarded_at' => now(),
        ]);
    }
}
