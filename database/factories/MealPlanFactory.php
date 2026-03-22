<?php

namespace Database\Factories;

use App\Enums\ActivityFactor;
use App\Models\FoodTable;
use App\Models\MealPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealPlan>
 */
class MealPlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'food_table_id' => FoodTable::factory(),
            'sex' => fake()->randomElement(['M', 'F']),
            'age' => fake()->numberBetween(1, 90),
            'weight' => fake()->randomFloat(2, 30, 150),
            'height_cm' => fake()->randomFloat(2, 50, 200),
            'formula' => fake()->optional()->word(),
            'geb' => fake()->optional()->randomFloat(2, 1000, 3000),
            'get' => fake()->optional()->randomFloat(2, 1000, 4000),
            'activity_factor' => fake()->optional()->randomElement(ActivityFactor::cases()),
            'breakfast_percentage' => 20,
            'morning_snack_percentage' => 10,
            'lunch_percentage' => 30,
            'afternoon_snack_percentage' => 10,
            'dinner_percentage' => 30,
            'protein_percentage' => 15,
            'fat_percentage' => 30,
            'carbohydrate_percentage' => 55,
        ];
    }
}
