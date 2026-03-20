<?php

namespace Database\Factories;

use App\Models\Food;
use App\Models\FoodUnit;
use App\Models\Recipe;
use App\Models\RecipeItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecipeItem>
 */
class RecipeItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'food_id' => Food::factory(),
            'food_unit_id' => FoodUnit::factory(),
            'quantity' => fake()->randomFloat(2, 0.1, 10),
        ];
    }
}
