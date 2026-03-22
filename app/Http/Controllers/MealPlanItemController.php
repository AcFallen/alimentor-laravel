<?php

namespace App\Http\Controllers;

use App\Http\Requests\MealPlan\CalendarMealPlanRequest;
use App\Http\Requests\MealPlan\DailyMealPlanRequest;
use App\Http\Requests\MealPlan\StoreMealPlanItemRequest;
use App\Http\Requests\MealPlan\UpdateMealPlanItemRequest;
use App\Http\Resources\MealPlanItemResource;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class MealPlanItemController extends Controller
{
    /**
     * Get meal plan calendar.
     *
     * Returns all meal plan items for a date range, grouped by date and meal type.
     *
     * @response array{
     *     start_date: string,
     *     end_date: string,
     *     days: array<string, array{
     *         breakfast?: MealPlanItemResource[],
     *         morning_snack?: MealPlanItemResource[],
     *         lunch?: MealPlanItemResource[],
     *         afternoon_snack?: MealPlanItemResource[],
     *         dinner?: MealPlanItemResource[],
     *     }>
     * }
     */
    public function calendar(CalendarMealPlanRequest $request, MealPlan $mealPlan): JsonResponse
    {
        $items = $mealPlan->items()
            ->with(['recipe:id,name', 'food:id,name'])
            ->whereBetween('date', [$request->validated('start_date'), $request->validated('end_date')])
            ->orderBy('date')
            ->orderBy('meal_type')
            ->orderBy('sort_order')
            ->get();

        $days = $items
            ->groupBy(fn (MealPlanItem $item) => $item->date->toDateString())
            ->map(fn (Collection $dayItems) => $dayItems
                ->groupBy(fn (MealPlanItem $item) => $item->meal_type->value)
                ->map(fn (Collection $mealItems) => $mealItems->map(fn (MealPlanItem $item) => [
                    'id' => $item->id,
                    'name' => $item->recipe?->name ?? $item->food?->name,
                    'type' => $item->recipe_id ? 'recipe' : 'food',
                ]))
            );

        return response()->json([
            'start_date' => $request->validated('start_date'),
            'end_date' => $request->validated('end_date'),
            'days' => $days,
        ]);
    }

    /**
     * Get daily meal plan.
     *
     * Returns all meal plan items for a specific date, grouped by meal type.
     *
     * @response array{
     *     date: string,
     *     meals: array{
     *         breakfast?: MealPlanItemResource[],
     *         morning_snack?: MealPlanItemResource[],
     *         lunch?: MealPlanItemResource[],
     *         afternoon_snack?: MealPlanItemResource[],
     *         dinner?: MealPlanItemResource[],
     *     }
     * }
     */
    public function daily(DailyMealPlanRequest $request, MealPlan $mealPlan): JsonResponse
    {
        $items = $mealPlan->items()
            ->with(['recipe.items.food', 'recipe.items.foodUnit', 'food', 'foodUnit'])
            ->where('date', $request->validated('date'))
            ->orderBy('meal_type')
            ->orderBy('sort_order')
            ->get();

        $grouped = $items->groupBy(fn (MealPlanItem $item) => $item->meal_type->value)
            ->map(fn (Collection $items) => MealPlanItemResource::collection($items));

        return response()->json([
            'date' => $request->validated('date'),
            'meals' => $grouped,
        ]);
    }

    public function index(MealPlan $mealPlan): AnonymousResourceCollection
    {
        $items = $mealPlan->items()
            ->with(['recipe.items.food', 'recipe.items.foodUnit', 'food', 'foodUnit'])
            ->orderBy('date')
            ->orderBy('meal_type')
            ->orderBy('sort_order')
            ->get();

        return MealPlanItemResource::collection($items);
    }

    public function store(StoreMealPlanItemRequest $request, MealPlan $mealPlan): JsonResponse
    {
        $item = $mealPlan->items()->create($request->validated());

        $item->load(['recipe', 'food', 'foodUnit']);

        return response()->json(new MealPlanItemResource($item), 201);
    }

    public function show(MealPlanItem $mealPlanItem): MealPlanItemResource
    {
        $mealPlanItem->load(['recipe', 'food', 'foodUnit']);

        return new MealPlanItemResource($mealPlanItem);
    }

    public function update(UpdateMealPlanItemRequest $request, MealPlanItem $mealPlanItem): MealPlanItemResource
    {
        $mealPlanItem->update($request->validated());

        $mealPlanItem->load(['recipe', 'food', 'foodUnit']);

        return new MealPlanItemResource($mealPlanItem);
    }

    public function destroy(MealPlanItem $mealPlanItem): JsonResponse
    {
        $mealPlanItem->delete();

        return response()->json(null, 204);
    }
}
