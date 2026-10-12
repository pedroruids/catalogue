<?php

namespace Database\Factories;

use App\Enums\FieldType;
use App\Models\FieldDefinition;
use App\Models\ProductType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FieldDefinition>
 */
class FieldDefinitionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_type_id' => ProductType::factory(),
            'name' => fake()->unique()->words(2, true),
            'data_type' => FieldType::Text,
            'required' => false,
            'options' => null,
            'position' => 0,
        ];
    }

    public function required(): static
    {
        return $this->state(['required' => true]);
    }

    /**
     * @param  list<string>  $options
     */
    public function select(array $options = ['A', 'B']): static
    {
        return $this->state(['data_type' => FieldType::Select, 'options' => $options]);
    }
}
