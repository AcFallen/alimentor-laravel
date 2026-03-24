<?php

use App\Enums\MealType;
use App\Models\Food;
use App\Models\FoodUnit;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\MealPlanSlot;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->mealPlan = MealPlan::factory()->create();
});

it('downloads an xlsx file for a valid date range', function () {
    $food = Food::factory()->create();
    $foodUnit = FoodUnit::factory()->for($food)->create();
    $recipe = Recipe::factory()->create();
    RecipeItem::factory()->create([
        'recipe_id' => $recipe->id,
        'food_id' => $food->id,
        'food_unit_id' => $foodUnit->id,
        'quantity' => 50,
    ]);

    $slot = MealPlanSlot::factory()->create([
        'meal_plan_id' => $this->mealPlan->id,
        'date' => '2026-03-23',
        'meal_type' => MealType::Breakfast,
    ]);

    MealPlanItem::factory()->create([
        'meal_plan_slot_id' => $slot->id,
        'recipe_id' => $recipe->id,
        'food_id' => null,
        'food_unit_id' => null,
        'quantity' => null,
        'diners' => 1,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/meal-plans/'.$this->mealPlan->id.'/reports/standardized-recipe?start_date=2026-03-23&end_date=2026-03-29');

    $response->assertSuccessful()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('downloads an xlsx file with loose food items', function () {
    $food = Food::factory()->create();
    $foodUnit = FoodUnit::factory()->for($food)->create();

    $slot = MealPlanSlot::factory()->create([
        'meal_plan_id' => $this->mealPlan->id,
        'date' => '2026-03-23',
        'meal_type' => MealType::Lunch,
    ]);

    MealPlanItem::factory()->create([
        'meal_plan_slot_id' => $slot->id,
        'food_id' => $food->id,
        'food_unit_id' => $foodUnit->id,
        'quantity' => 100,
        'diners' => 2,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/meal-plans/'.$this->mealPlan->id.'/reports/standardized-recipe?start_date=2026-03-23&end_date=2026-03-29');

    $response->assertSuccessful()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('returns an empty report when no slots exist in range', function () {
    $response = $this->actingAs($this->user)
        ->getJson('/api/meal-plans/'.$this->mealPlan->id.'/reports/standardized-recipe?start_date=2026-03-23&end_date=2026-03-29');

    $response->assertSuccessful()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('validates required date parameters', function () {
    $this->actingAs($this->user)
        ->getJson('/api/meal-plans/'.$this->mealPlan->id.'/reports/standardized-recipe')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['start_date', 'end_date']);
});

it('validates end_date is after or equal to start_date', function () {
    $this->actingAs($this->user)
        ->getJson('/api/meal-plans/'.$this->mealPlan->id.'/reports/standardized-recipe?start_date=2026-03-29&end_date=2026-03-23')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['end_date']);
});

it('requires authentication', function () {
    $this->getJson('/api/meal-plans/'.$this->mealPlan->id.'/reports/standardized-recipe?start_date=2026-03-23&end_date=2026-03-29')
        ->assertUnauthorized();
});
