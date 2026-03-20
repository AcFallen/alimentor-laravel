<?php

namespace App\Http\Controllers;

use App\Http\Requests\FoodTable\StoreFoodTableRequest;
use App\Http\Requests\FoodTable\UpdateFoodTableRequest;
use App\Models\FoodTable;
use Illuminate\Http\JsonResponse;

class FoodTableController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(FoodTable::all());
    }

    public function store(StoreFoodTableRequest $request): JsonResponse
    {
        $foodTable = FoodTable::query()->create($request->validated());

        return response()->json($foodTable, 201);
    }

    public function show(FoodTable $foodTable): JsonResponse
    {
        return response()->json($foodTable);
    }

    public function update(UpdateFoodTableRequest $request, FoodTable $foodTable): JsonResponse
    {
        $foodTable->update($request->validated());

        return response()->json($foodTable);
    }

    public function destroy(FoodTable $foodTable): JsonResponse
    {
        $foodTable->delete();

        return response()->json(null, 204);
    }
}
