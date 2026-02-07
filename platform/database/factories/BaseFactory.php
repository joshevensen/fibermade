<?php

namespace Database\Factories;

use App\Enums\BaseStatus;
use App\Enums\Weight;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Base>
 */
class BaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $baseDescriptors = [
            'Merino Wool',
            'Alpaca Blend',
            'Superwash Merino',
            'DK Weight',
            'Fingering Weight',
            'Worsted Weight',
            'Bulky Weight',
            'Lace Weight',
            'Cashmere Blend',
            'Silk Blend',
        ];

        $descriptor = fake()->randomElement($baseDescriptors);
        $weight = fake()->randomElement(Weight::cases());
        $status = fake()->randomElement(BaseStatus::cases());

        // Generate code from descriptor initials
        $code = \App\Models\Base::generateCodeFromDescriptor($descriptor);

        // Generate realistic fiber percentages that sum to ~100%
        $woolPercent = fake()->optional(0.8)->randomFloat(2, 50, 100);
        $alpacaPercent = fake()->optional(0.3)->randomFloat(2, 10, 50);
        $nylonPercent = fake()->optional(0.4)->randomFloat(2, 5, 30);
        $yakPercent = fake()->optional(0.2)->randomFloat(2, 10, 40);
        $camelPercent = fake()->optional(0.1)->randomFloat(2, 10, 30);
        $cottonPercent = fake()->optional(0.2)->randomFloat(2, 20, 50);
        $bambooPercent = fake()->optional(0.2)->randomFloat(2, 20, 50);

        return [
            'account_id' => \App\Models\Account::factory()->creator(),
            'description' => fake()->optional(0.7)->sentence(),
            'status' => $status,
            'weight' => $weight,
            'descriptor' => $descriptor,
            'code' => $code,
            'size' => fake()->optional(0.6)->randomElement([20, 50, 100]),
            'cost' => fake()->optional(0.8)->randomFloat(2, 5, 25),
            'retail_price' => fake()->optional(0.8)->randomFloat(2, 15, 45),
            'wool_percent' => $woolPercent,
            'nylon_percent' => $nylonPercent,
            'alpaca_percent' => $alpacaPercent,
            'yak_percent' => $yakPercent,
            'camel_percent' => $camelPercent,
            'cotton_percent' => $cottonPercent,
            'bamboo_percent' => $bambooPercent,
        ];
    }
}
