<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\RecipeCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'recipe_category_id' => RecipeCategory::factory(),
            'preparation' => fake()->paragraphs(3, true),
        ];
    }
}
