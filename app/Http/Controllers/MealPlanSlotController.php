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
    /**
     * List meal plan slots.
     *
     * Returns slots for a meal plan, optionally filtered by date or date range.
     *
     * @response array{data: array{id: int, date: string, meal_type: string, diners: int, created_at: string, updated_at: string}[]}
     */
    public function index(Request $request, MealPlan $mealPlan): AnonymousResourceCollection
    {
        $slots = $mealPlan->slots()
            ->when($request->query('date'), function ($query, string $date) {
                $query->where('date', $date);
            })
            ->when(! $request->query('date') && $request->query('start_date'), function ($query) use ($request) {
                $query->whereBetween('date', [
                    $request->query('start_date'),
                    $request->query('end_date', $request->query('start_date')),
                ]);
            })
            ->orderBy('date')
            ->orderBy('meal_type')
            ->get();

        return MealPlanSlotResource::collection($slots);
    }

    /**
     * Create a meal plan slot.
     *
     * @response 201 array{id: int, date: string, meal_type: string, diners: int, created_at: string, updated_at: string}
     */
    public function store(StoreMealPlanSlotRequest $request, MealPlan $mealPlan): JsonResponse
    {
        $slot = $mealPlan->slots()->create($request->validated());

        return response()->json(new MealPlanSlotResource($slot), 201);
    }

    /**
     * Show a meal plan slot.
     *
     * @response array{id: int, date: string, meal_type: string, diners: int, created_at: string, updated_at: string}
     */
    public function show(MealPlanSlot $mealPlanSlot): MealPlanSlotResource
    {
        return new MealPlanSlotResource($mealPlanSlot);
    }

    /**
     * Update a meal plan slot.
     *
     * @response array{id: int, date: string, meal_type: string, diners: int, created_at: string, updated_at: string}
     */
    public function update(UpdateMealPlanSlotRequest $request, MealPlanSlot $mealPlanSlot): MealPlanSlotResource
    {
        $mealPlanSlot->update($request->validated());

        return new MealPlanSlotResource($mealPlanSlot);
    }

    /**
     * Delete a meal plan slot.
     *
     * @response 204
     */
    public function destroy(MealPlanSlot $mealPlanSlot): JsonResponse
    {
        $mealPlanSlot->delete();

        return response()->json(null, 204);
    }
}
