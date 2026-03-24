<?php

namespace App\Http\Controllers;

use App\Http\Requests\FoodUnit\StoreFoodUnitRequest;
use App\Http\Requests\FoodUnit\UpdateFoodUnitRequest;
use App\Http\Resources\FoodUnitResource;
use App\Models\Food;
use App\Models\FoodUnit;
use App\Models\MealPlanItem;
use App\Models\RecipeItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FoodUnitController extends Controller
{
    public function index(Food $food): AnonymousResourceCollection
    {
        return FoodUnitResource::collection($food->units);
    }

    public function store(StoreFoodUnitRequest $request, Food $food): JsonResponse
    {
        $unit = $food->units()->create($request->validated());

        return response()->json(new FoodUnitResource($unit), 201);
    }

    public function show(Food $food, FoodUnit $unit): FoodUnitResource
    {
        return new FoodUnitResource($unit);
    }

    public function update(UpdateFoodUnitRequest $request, Food $food, FoodUnit $unit): FoodUnitResource
    {
        $unit->update($request->validated());

        return new FoodUnitResource($unit);
    }

    public function destroy(Food $food, FoodUnit $unit): JsonResponse
    {
        $isUsed = RecipeItem::where('food_unit_id', $unit->id)->exists()
            || MealPlanItem::where('food_unit_id', $unit->id)->exists();

        if ($isUsed) {
            return response()->json([
                'message' => 'No se puede eliminar la unidad porque está siendo utilizada en recetas o planificaciones.',
            ], 409);
        }

        $unit->delete();

        return response()->json(null, 204);
    }
}
