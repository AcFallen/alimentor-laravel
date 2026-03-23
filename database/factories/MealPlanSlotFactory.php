<?php

namespace Database\Factories;

use App\Enums\MealType;
use App\Models\MealPlan;
use App\Models\MealPlanSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealPlanSlot>
 */
class MealPlanSlotFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meal_plan_id' => MealPlan::factory(),
            'date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'meal_type' => fake()->randomElement(MealType::cases()),
            'diners' => fake()->numberBetween(1, 30),
        ];
    }
}
