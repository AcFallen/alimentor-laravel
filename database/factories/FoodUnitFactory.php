<?php

namespace Database\Factories;

use App\Models\Food;
use App\Models\FoodUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FoodUnit>
 */
class FoodUnitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'food_id' => Food::factory(),
            'name' => fake()->randomElement(['Kilogramo', 'Unidad', 'Taza', 'Cucharada', 'Costal']),
            'equivalent_in_grams' => fake()->randomFloat(2, 10, 50000),
            'cost' => fake()->randomFloat(2, 0.10, 500),
        ];
    }
}
