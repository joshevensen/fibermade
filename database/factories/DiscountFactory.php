<?php

namespace Database\Factories;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Discount>
 */
class DiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(DiscountType::cases());
        $code = strtoupper(fake()->bothify('???####'));

        $parameters = match ($type) {
            DiscountType::OrderThresholdFreeShipping => [
                'threshold' => fake()->randomElement([50, 75, 100, 125]),
            ],
            DiscountType::QuantityPerSkein => [
                'min_quantity' => fake()->numberBetween(3, 6),
                'discount_per_skein' => fake()->randomFloat(2, 1, 5),
            ],
            DiscountType::Percentage => [
                'percentage' => fake()->randomElement([10, 15, 20, 25]),
            ],
            DiscountType::ManualFreeShipping => [],
            DiscountType::TimeBoxed => [
                'percentage' => fake()->randomElement([15, 20, 25, 30]),
                'starts_at' => now()->subDays(fake()->numberBetween(0, 7))->toIso8601String(),
                'ends_at' => now()->addDays(fake()->numberBetween(7, 30))->toIso8601String(),
            ],
        };

        return [
            'name' => fake()->sentence(3),
            'type' => $type,
            'code' => $code,
            'parameters' => $parameters,
            'starts_at' => fake()->optional(0.3)->dateTimeBetween('-1 week', '+1 week'),
            'ends_at' => fake()->optional(0.3)->dateTimeBetween('+1 week', '+1 month'),
            'is_active' => fake()->boolean(80),
        ];
    }
}
