<?php

namespace App\Http\Controllers;

use App\Http\Requests\FoodUnit\StoreFoodUnitRequest;
use App\Http\Requests\FoodUnit\UpdateFoodUnitRequest;
use App\Http\Resources\FoodUnitResource;
use App\Models\Food;
use App\Models\FoodUnit;
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
        $unit->delete();

        return response()->json(null, 204);
    }
}
