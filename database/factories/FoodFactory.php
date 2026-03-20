<?php

namespace Database\Factories;

use App\Models\Food;
use App\Models\FoodCategory;
use App\Models\FoodTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Food>
 */
class FoodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'food_category_id' => FoodCategory::factory(),
            'food_table_id' => FoodTable::factory(),
            'performance' => fake()->randomFloat(2, 0.1, 100),
            'nutrients' => [
                'calories' => fake()->randomFloat(1, 0, 500),
                'protein' => fake()->randomFloat(1, 0, 50),
                'carbs' => fake()->randomFloat(1, 0, 100),
                'fat' => fake()->randomFloat(1, 0, 50),
            ],
            'is_active' => fake()->boolean(90),
        ];
    }
}
