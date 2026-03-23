<?php

use App\Models\Food;
use App\Models\FoodTable;
use App\Models\FoodUnit;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('returns a paginated list of recipes', function () {
    Recipe::factory()->count(3)->create();

    $response = $this->actingAs($this->user)
        ->getJson('/api/recipes');

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'preparation', 'created_at', 'updated_at'],
            ],
            'meta',
            'links',
        ]);
});

it('filters recipes by search query', function () {
    Recipe::factory()->create(['name' => 'Arroz con pollo']);
    Recipe::factory()->create(['name' => 'Ensalada verde']);

    $response = $this->actingAs($this->user)
        ->getJson('/api/recipes?search=Arroz');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Arroz con pollo');
});

it('filters recipes by recipe_category_id', function () {
    $recipe = Recipe::factory()->create();
    Recipe::factory()->create();

    $response = $this->actingAs($this->user)
        ->getJson("/api/recipes?recipe_category_id={$recipe->recipe_category_id}");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $recipe->id);
});

it('filters recipes by food_table_id', function () {
    $targetTable = FoodTable::factory()->create();
    $otherTable = FoodTable::factory()->create();

    $targetFood = Food::factory()->create(['food_table_id' => $targetTable->id]);
    $targetUnit = FoodUnit::factory()->create(['food_id' => $targetFood->id]);

    $otherFood = Food::factory()->create(['food_table_id' => $otherTable->id]);
    $otherUnit = FoodUnit::factory()->create(['food_id' => $otherFood->id]);

    $matchingRecipe = Recipe::factory()->create(['name' => 'Matching recipe']);
    RecipeItem::factory()->create([
        'recipe_id' => $matchingRecipe->id,
        'food_id' => $targetFood->id,
        'food_unit_id' => $targetUnit->id,
    ]);

    $nonMatchingRecipe = Recipe::factory()->create(['name' => 'Non matching recipe']);
    RecipeItem::factory()->create([
        'recipe_id' => $nonMatchingRecipe->id,
        'food_id' => $otherFood->id,
        'food_unit_id' => $otherUnit->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/recipes?food_table_id={$targetTable->id}");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Matching recipe');
});

it('combines multiple filters together', function () {
    $targetTable = FoodTable::factory()->create();

    $food = Food::factory()->create(['food_table_id' => $targetTable->id]);
    $unit = FoodUnit::factory()->create(['food_id' => $food->id]);

    $matchingRecipe = Recipe::factory()->create(['name' => 'Arroz con pollo']);
    RecipeItem::factory()->create([
        'recipe_id' => $matchingRecipe->id,
        'food_id' => $food->id,
        'food_unit_id' => $unit->id,
    ]);

    $sameTableDifferentName = Recipe::factory()->create(['name' => 'Ensalada verde']);
    RecipeItem::factory()->create([
        'recipe_id' => $sameTableDifferentName->id,
        'food_id' => $food->id,
        'food_unit_id' => $unit->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/recipes?food_table_id={$targetTable->id}&search=Arroz");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Arroz con pollo');
});

it('returns all recipes when no filters are applied', function () {
    Recipe::factory()->count(5)->create();

    $response = $this->actingAs($this->user)
        ->getJson('/api/recipes');

    $response->assertOk()
        ->assertJsonCount(5, 'data');
});

it('requires authentication', function () {
    $this->getJson('/api/recipes')->assertUnauthorized();
});
