<?php

namespace App\Http\Controllers;

use App\Http\Requests\Food\SearchFoodRequest;
use App\Http\Requests\Food\StoreFoodRequest;
use App\Http\Requests\Food\UpdateFoodRequest;
use App\Http\Resources\FoodResource;
use App\Models\Food;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FoodController extends Controller
{
    public function search(SearchFoodRequest $request): AnonymousResourceCollection
    {
        $foods = Food::query()
            ->with(['category', 'table', 'units'])
            ->where('food_table_id', $request->validated('food_table_id'))
            ->where('name', 'like', '%'.$request->validated('search').'%')
            ->limit(20)
            ->get();

        return FoodResource::collection($foods);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $foods = Food::query()
            ->with(['category', 'table', 'units'])
            ->when($request->query('search'), function ($query, string $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->query('food_category_id'), function ($query, string $categoryId) {
                $query->where('food_category_id', $categoryId);
            })
            ->when($request->query('food_table_id'), function ($query, string $tableId) {
                $query->where('food_table_id', $tableId);
            })
            ->paginate($request->integer('per_page', 15));

        return FoodResource::collection($foods);
    }

    public function store(StoreFoodRequest $request): JsonResponse
    {
        $food = Food::query()->create($request->validated());

        return response()->json(new FoodResource($food), 201);
    }

    public function show(Food $food): FoodResource
    {
        $food->load(['category', 'table', 'units']);

        return new FoodResource($food);
    }

    public function update(UpdateFoodRequest $request, Food $food): FoodResource
    {
        $food->update($request->validated());

        return new FoodResource($food);
    }

    public function destroy(Food $food): JsonResponse
    {
        $food->delete();

        return response()->json(null, 204);
    }
}
