<?php

namespace App\Http\Controllers;

use App\Http\Requests\Recipe\SearchRecipeRequest;
use App\Http\Requests\Recipe\StoreRecipeRequest;
use App\Http\Requests\Recipe\UpdateRecipeRequest;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
    public function search(SearchRecipeRequest $request): AnonymousResourceCollection
    {
        $recipes = Recipe::query()
            ->with(['category', 'items.food', 'items.foodUnit'])
            ->where('name', 'like', '%'.$request->validated('search').'%')
            ->when($request->validated('food_table_id'), function ($query, int $foodTableId) {
                $query->whereHas('items.food', function ($query) use ($foodTableId) {
                    $query->where('food_table_id', $foodTableId);
                });
            })
            ->limit(20)
            ->get();

        return RecipeResource::collection($recipes);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $recipes = Recipe::query()
            ->with(['category', 'items.food', 'items.foodUnit'])
            ->when($request->query('search'), function ($query, string $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->query('recipe_category_id'), function ($query, string $categoryId) {
                $query->where('recipe_category_id', $categoryId);
            })
            ->paginate($request->integer('per_page', 15));

        return RecipeResource::collection($recipes);
    }

    public function store(StoreRecipeRequest $request): JsonResponse
    {
        $recipe = DB::transaction(function () use ($request): Recipe {
            $recipe = Recipe::query()->create($request->safe()->except('items'));

            if ($request->has('items')) {
                $recipe->items()->createMany($request->validated('items'));
            }

            return $recipe;
        });

        $recipe->load(['category', 'items.food', 'items.foodUnit']);

        return response()->json(new RecipeResource($recipe), 201);
    }

    public function show(Recipe $recipe): RecipeResource
    {
        $recipe->load(['category', 'items.food.units', 'items.foodUnit']);

        return new RecipeResource($recipe);
    }

    public function update(UpdateRecipeRequest $request, Recipe $recipe): RecipeResource
    {
        DB::transaction(function () use ($request, $recipe): void {
            $recipe->update($request->safe()->except('items'));

            if ($request->has('items')) {
                $recipe->items()->delete();
                $recipe->items()->createMany($request->validated('items'));
            }
        });

        $recipe->load(['category', 'items.food', 'items.foodUnit']);

        return new RecipeResource($recipe);
    }

    public function destroy(Recipe $recipe): JsonResponse
    {
        $recipe->delete();

        return response()->json(null, 204);
    }
}
