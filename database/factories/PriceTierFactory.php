<?php

namespace Database\Factories;

use App\Models\ArticleVariant;
use App\Models\PriceTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PriceTier>
 */
class PriceTierFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_variant_id' => ArticleVariant::factory(),
            'min_quantity' => 1,
            'unit_price_cents' => fake()->numberBetween(100, 5000),
        ];
    }
}
