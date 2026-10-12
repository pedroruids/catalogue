<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ProductType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_type_id' => ProductType::factory(),
            'name' => fake()->words(3, true),
            'reference' => strtoupper(fake()->unique()->bothify('??-####')),
            'description' => fake()->sentence(),
            'status' => ArticleStatus::Draft,
            'field_values' => [],
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => ArticleStatus::Published]);
    }
}
