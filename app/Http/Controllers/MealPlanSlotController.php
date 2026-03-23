<?php

namespace App\Http\Controllers;

use App\Http\Requests\MealPlan\StoreMealPlanSlotRequest;
use App\Http\Requests\MealPlan\UpdateMealPlanSlotRequest;
use App\Http\Resources\MealPlanSlotResource;
use App\Models\MealPlan;
use App\Models\MealPlanSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MealPlanSlotController extends Controller
{
    /** @var array<int, string> */
    private const array ITEM_RELATIONS = [
        'items.recipe.category',
        'items.recipe.items.food.category',
        'items.recipe.items.food.table',
        'items.recipe.items.food.units',
        'items.recipe.items.foodUnit',
        'items.food.category',
        'items.food.table',
        'items.food.units',
        'items.foodUnit',
    ];

    public function index(Request $request, MealPlan $mealPlan): AnonymousResourceCollection
    {
        $slots = $mealPlan->slots()
            ->with(self::ITEM_RELATIONS)
            ->when($request->query('start_date'), function ($query, string $startDate) use ($request) {
                $query->whereBetween('date', [$startDate, $request->query('end_date', $startDate)]);
            })
            ->orderBy('date')
            ->orderBy('meal_type')
            ->get();

        return MealPlanSlotResource::collection($slots);
    }

    public function store(StoreMealPlanSlotRequest $request, MealPlan $mealPlan): JsonResponse
    {
        $slot = $mealPlan->slots()->create($request->validated());

        return response()->json(new MealPlanSlotResource($slot), 201);
    }

    public function show(MealPlanSlot $mealPlanSlot): MealPlanSlotResource
    {
        $mealPlanSlot->load(self::ITEM_RELATIONS);

        return new MealPlanSlotResource($mealPlanSlot);
    }

    public function update(UpdateMealPlanSlotRequest $request, MealPlanSlot $mealPlanSlot): MealPlanSlotResource
    {
        $mealPlanSlot->update($request->validated());

        $mealPlanSlot->load(self::ITEM_RELATIONS);

        return new MealPlanSlotResource($mealPlanSlot);
    }

    public function destroy(MealPlanSlot $mealPlanSlot): JsonResponse
    {
        $mealPlanSlot->delete();

        return response()->json(null, 204);
    }
}
