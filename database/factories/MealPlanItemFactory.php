<?php

namespace Database\Factories;

use App\Models\Food;
use App\Models\FoodUnit;
use App\Models\MealPlanItem;
use App\Models\MealPlanSlot;
use App\Models\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealPlanItem>
 */
class MealPlanItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $food = Food::factory()->create();
        $foodUnit = FoodUnit::factory()->for($food)->create();

        return [
            'meal_plan_slot_id' => MealPlanSlot::factory(),
            'option_group' => null,
            'recipe_id' => null,
            'food_id' => $food->id,
            'food_unit_id' => $foodUnit->id,
            'quantity' => fake()->randomFloat(2, 10, 500),
            'diners' => 1,
            'sort_order' => 0,
        ];
    }

    public function withRecipe(): static
    {
        return $this->state(fn () => [
            'recipe_id' => Recipe::factory(),
            'food_id' => null,
            'food_unit_id' => null,
            'quantity' => null,
        ]);
    }
}
