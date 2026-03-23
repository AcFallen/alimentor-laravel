<?php

namespace App\Http\Controllers;

use App\Http\Requests\MealPlan\CalendarMealPlanRequest;
use App\Http\Requests\MealPlan\DailyMealPlanRequest;
use App\Http\Requests\MealPlan\StoreMealPlanItemRequest;
use App\Http\Requests\MealPlan\UpdateMealPlanItemRequest;
use App\Http\Resources\MealPlanItemResource;
use App\Http\Resources\MealPlanSlotResource;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\MealPlanSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class MealPlanItemController extends Controller
{
    /** @var array<int, string> */
    private const array ITEM_RELATIONS = [
        'recipe.category',
        'recipe.items.food.category',
        'recipe.items.food.table',
        'recipe.items.food.units',
        'recipe.items.foodUnit',
        'food.category',
        'food.table',
        'food.units',
        'foodUnit',
    ];

    /**
     * Get meal plan calendar.
     *
     * Returns all meal plan slots for a date range, grouped by date and meal type.
     */
    public function calendar(CalendarMealPlanRequest $request, MealPlan $mealPlan): JsonResponse
    {
        $slots = $mealPlan->slots()
            ->with(['items' => fn ($q) => $q->with(['recipe:id,name', 'food:id,name'])->orderBy('sort_order')])
            ->whereBetween('date', [$request->validated('start_date'), $request->validated('end_date')])
            ->orderBy('date')
            ->orderBy('meal_type')
            ->get();

        $days = $slots
            ->groupBy(fn (MealPlanSlot $slot) => $slot->date->toDateString())
            ->map(fn (Collection $daySlots) => $daySlots
                ->keyBy(fn (MealPlanSlot $slot) => $slot->meal_type->value)
                ->map(fn (MealPlanSlot $slot) => [
                    'id' => $slot->id,
                    'diners' => $slot->diners,
                    'items' => $slot->items->map(fn (MealPlanItem $item) => [
                        'id' => $item->id,
                        'name' => $item->recipe?->name ?? $item->food?->name,
                        'type' => $item->recipe_id ? 'recipe' : 'food',
                        'diners' => $item->diners,
                    ]),
                ])
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
     * Returns all meal plan slots for a specific date, with full item details.
     */
    public function daily(DailyMealPlanRequest $request, MealPlan $mealPlan): JsonResponse
    {
        $slots = $mealPlan->slots()
            ->with(['items' => fn ($q) => $q->with(self::ITEM_RELATIONS)->orderBy('sort_order')])
            ->where('date', $request->validated('date'))
            ->orderBy('meal_type')
            ->get();

        $meals = $slots
            ->keyBy(fn (MealPlanSlot $slot) => $slot->meal_type->value)
            ->map(fn (MealPlanSlot $slot) => new MealPlanSlotResource($slot));

        return response()->json([
            'date' => $request->validated('date'),
            'meals' => $meals,
        ]);
    }

    public function index(MealPlanSlot $mealPlanSlot): AnonymousResourceCollection
    {
        $items = $mealPlanSlot->items()
            ->with(self::ITEM_RELATIONS)
            ->orderBy('sort_order')
            ->get();

        return MealPlanItemResource::collection($items);
    }

    public function store(StoreMealPlanItemRequest $request, MealPlanSlot $mealPlanSlot): JsonResponse
    {
        $item = $mealPlanSlot->items()->create($request->validated());

        $item->load(self::ITEM_RELATIONS);

        return response()->json(new MealPlanItemResource($item), 201);
    }

    public function show(MealPlanItem $mealPlanItem): MealPlanItemResource
    {
        $mealPlanItem->load(self::ITEM_RELATIONS);

        return new MealPlanItemResource($mealPlanItem);
    }

    public function update(UpdateMealPlanItemRequest $request, MealPlanItem $mealPlanItem): MealPlanItemResource
    {
        $mealPlanItem->update($request->validated());

        $mealPlanItem->load(self::ITEM_RELATIONS);

        return new MealPlanItemResource($mealPlanItem);
    }

    public function destroy(MealPlanItem $mealPlanItem): JsonResponse
    {
        $mealPlanItem->delete();

        return response()->json(null, 204);
    }
}
