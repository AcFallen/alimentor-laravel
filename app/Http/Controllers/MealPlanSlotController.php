<?php

namespace App\Http\Controllers;

use App\Http\Requests\MealPlan\ClearDayMealPlanRequest;
use App\Http\Requests\MealPlan\CopyDayMealPlanRequest;
use App\Http\Requests\MealPlan\CopyRangeMealPlanRequest;
use App\Http\Requests\MealPlan\StoreMealPlanSlotRequest;
use App\Http\Requests\MealPlan\UpdateMealPlanSlotRequest;
use App\Http\Resources\MealPlanSlotResource;
use App\Models\MealPlan;
use App\Models\MealPlanSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /**
     * Copy all slots and items from one date to another.
     *
     * Duplicates all meal plan slots and their items from source_date to target_date.
     * If the target date already has slots, they will be deleted first.
     */
    public function copyDay(CopyDayMealPlanRequest $request, MealPlan $mealPlan): JsonResponse
    {
        $sourceDate = $request->validated('source_date');
        $targetDate = $request->validated('target_date');

        $sourceSlots = $mealPlan->slots()
            ->with('items')
            ->where('date', $sourceDate)
            ->get();

        if ($sourceSlots->isEmpty()) {
            return response()->json(['message' => 'No hay datos para copiar en la fecha origen.'], 422);
        }

        DB::transaction(function () use ($mealPlan, $sourceSlots, $targetDate): void {
            $mealPlan->slots()->where('date', $targetDate)->delete();
            $this->replicateSlots($mealPlan, $sourceSlots, $targetDate);
        });

        $newSlots = $mealPlan->slots()
            ->where('date', $targetDate)
            ->orderBy('meal_type')
            ->get();

        return response()->json([
            'message' => 'Día copiado exitosamente.',
            'data' => MealPlanSlotResource::collection($newSlots),
        ], 201);
    }

    /**
     * Copy all slots and items from a date range to another starting date.
     *
     * Copies each day in the source range (start_date to end_date) to consecutive days
     * starting from target_date. If target dates already have slots, they will be deleted first.
     */
    public function copyRange(CopyRangeMealPlanRequest $request, MealPlan $mealPlan): JsonResponse
    {
        $startDate = Carbon::parse($request->validated('start_date'));
        $endDate = Carbon::parse($request->validated('end_date'));
        $targetDate = Carbon::parse($request->validated('target_date'));

        $sourceSlots = $mealPlan->slots()
            ->with('items')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();

        if ($sourceSlots->isEmpty()) {
            return response()->json(['message' => 'No hay datos para copiar en el rango de fechas origen.'], 422);
        }

        $slotsByDate = $sourceSlots->groupBy(fn (MealPlanSlot $slot) => $slot->date->toDateString());
        $rangeDays = $startDate->diffInDays($endDate);
        $newEndDate = $targetDate->copy()->addDays($rangeDays)->toDateString();
        $newStartDate = $targetDate->toDateString();

        DB::transaction(function () use ($mealPlan, $slotsByDate, $startDate, $targetDate, $newStartDate, $newEndDate): void {
            $mealPlan->slots()->whereBetween('date', [$newStartDate, $newEndDate])->delete();

            foreach ($slotsByDate as $dateString => $daySlots) {
                $sourceDayOffset = $startDate->diffInDays(Carbon::parse($dateString));
                $newDate = $targetDate->copy()->addDays($sourceDayOffset)->toDateString();

                $this->replicateSlots($mealPlan, $daySlots, $newDate);
            }
        });

        $newSlots = $mealPlan->slots()
            ->whereBetween('date', [$newStartDate, $newEndDate])
            ->orderBy('date')
            ->orderBy('meal_type')
            ->get();

        return response()->json([
            'message' => 'Rango copiado exitosamente.',
            'data' => MealPlanSlotResource::collection($newSlots),
        ], 201);
    }

    /**
     * Clear all slots and items for a specific date.
     *
     * Deletes all meal plan slots (and their items via cascade) for the given date.
     */
    public function clearDay(ClearDayMealPlanRequest $request, MealPlan $mealPlan): JsonResponse
    {
        $date = $request->validated('date');

        $deleted = $mealPlan->slots()->where('date', $date)->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'No hay datos para limpiar en esa fecha.'], 422);
        }

        return response()->json(['message' => 'Día limpiado exitosamente.']);
    }

    /**
     * Replicate a collection of slots with their items to a new date.
     *
     * @param  Collection<int, MealPlanSlot>  $slots
     */
    private function replicateSlots(MealPlan $mealPlan, Collection $slots, string $targetDate): void
    {
        foreach ($slots as $sourceSlot) {
            $newSlot = $mealPlan->slots()->create([
                'date' => $targetDate,
                'meal_type' => $sourceSlot->meal_type,
                'diners' => $sourceSlot->diners,
            ]);

            foreach ($sourceSlot->items as $item) {
                $newSlot->items()->create([
                    'option_group' => $item->option_group,
                    'recipe_id' => $item->recipe_id,
                    'food_id' => $item->food_id,
                    'food_unit_id' => $item->food_unit_id,
                    'quantity' => $item->quantity,
                    'diners' => $item->diners,
                    'sort_order' => $item->sort_order,
                ]);
            }
        }
    }
}
