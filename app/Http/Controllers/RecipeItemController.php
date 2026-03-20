<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecipeItem\StoreRecipeItemRequest;
use App\Http\Requests\RecipeItem\UpdateRecipeItemRequest;
use App\Http\Resources\RecipeItemResource;
use App\Models\Recipe;
use App\Models\RecipeItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecipeItemController extends Controller
{
    public function index(Recipe $recipe): AnonymousResourceCollection
    {
        return RecipeItemResource::collection(
            $recipe->items()->with(['food', 'foodUnit'])->get()
        );
    }

    public function store(StoreRecipeItemRequest $request, Recipe $recipe): JsonResponse
    {
        $item = $recipe->items()->create($request->validated());
        $item->load(['food', 'foodUnit']);

        return response()->json(new RecipeItemResource($item), 201);
    }

    public function show(Recipe $recipe, RecipeItem $item): RecipeItemResource
    {
        $item->load(['food', 'foodUnit']);

        return new RecipeItemResource($item);
    }

    public function update(UpdateRecipeItemRequest $request, Recipe $recipe, RecipeItem $item): RecipeItemResource
    {
        $item->update($request->validated());
        $item->load(['food', 'foodUnit']);

        return new RecipeItemResource($item);
    }

    public function destroy(Recipe $recipe, RecipeItem $item): JsonResponse
    {
        $item->delete();

        return response()->json(null, 204);
    }
}
