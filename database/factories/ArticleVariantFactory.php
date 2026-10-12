<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\ArticleVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleVariant>
 */
class ArticleVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'options' => [],
            'reference' => strtoupper(fake()->unique()->bothify('VR-####??')),
            'is_active' => true,
        ];
    }
}
