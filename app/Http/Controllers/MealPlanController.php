<?php

namespace App\Http\Controllers;

use App\Http\Requests\MealPlan\StoreMealPlanRequest;
use App\Http\Requests\MealPlan\UpdateMealPlanRequest;
use App\Http\Resources\MealPlanResource;
use App\Models\MealPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MealPlanController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $mealPlans = MealPlan::query()
            ->with(['foodTable'])
            ->when($request->query('search'), function ($query, string $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate($request->integer('per_page', 15));

        return MealPlanResource::collection($mealPlans);
    }

    public function store(StoreMealPlanRequest $request): JsonResponse
    {
        $mealPlan = MealPlan::query()->create($request->validated());

        $mealPlan->load(['foodTable']);

        return response()->json(new MealPlanResource($mealPlan), 201);
    }

    public function show(MealPlan $mealPlan): MealPlanResource
    {
        $mealPlan->load(['foodTable']);

        return new MealPlanResource($mealPlan);
    }

    public function update(UpdateMealPlanRequest $request, MealPlan $mealPlan): MealPlanResource
    {
        $mealPlan->update($request->validated());

        $mealPlan->load(['foodTable']);

        return new MealPlanResource($mealPlan);
    }

    public function destroy(MealPlan $mealPlan): JsonResponse
    {
        $mealPlan->delete();

        return response()->json(null, 204);
    }
}
