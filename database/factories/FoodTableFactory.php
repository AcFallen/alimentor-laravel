<?php

namespace Database\Factories;

use App\Models\FoodTable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FoodTable>
 */
class FoodTableFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->country(),
        ];
    }
}
