<?php

namespace App\Exports;

use App\Models\Food;
use App\Models\FoodUnit;
use App\Models\MealPlan;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class KitchenOrderPdfExport
{
    public function __construct(
        private readonly MealPlan $mealPlan,
        private readonly string $startDate,
        private readonly string $endDate,
    ) {}

    public function generate(): string
    {
        Carbon::setLocale('es');

        $data = $this->buildReportData();

        $pdf = Pdf::loadView('reports.kitchen-order', $data)
            ->setPaper('a4', 'landscape');

        $filename = 'orden_cocina_'.now()->format('Ymd_His').'.pdf';
        $path = storage_path('app/private/reports/'.$filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $pdf->save($path);

        return $path;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReportData(): array
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        $foodAggregation = $this->aggregateFoods();

        $categories = [];
        $grandTotal = 0.0;

        foreach ($foodAggregation as $categoryName => $foods) {
            $categoryTotal = 0.0;
            $rows = [];

            foreach ($foods as $foodData) {
                $totalKg = $foodData['total_grams'] / 1000;
                $equivalentInGrams = $foodData['equivalent_in_grams'];
                $unitCost = $foodData['unit_cost'];

                $totalUnits = '';
                $cost = '';

                if ($equivalentInGrams > 0 && $unitCost > 0) {
                    $totalUnitsNum = $foodData['total_grams'] / $equivalentInGrams;
                    $costNum = $totalUnitsNum * $unitCost;
                    $totalUnits = number_format($totalUnitsNum, 2);
                    $cost = number_format($costNum, 2);
                    $categoryTotal += $costNum;
                } else {
                    $totalUnits = 'Por configurar';
                    $cost = 'N/A';
                }

                $rows[] = [
                    'name' => $foodData['name'],
                    'totalKg' => number_format($totalKg, 2),
                    'unitName' => $foodData['unit_name'],
                    'totalUnits' => $totalUnits,
                    'totalCost' => $cost,
                ];
            }

            $categories[] = [
                'name' => $categoryName,
                'rows' => $rows,
                'total' => $categoryTotal,
            ];

            $grandTotal += $categoryTotal;
        }

        return [
            'mealPlanName' => $this->mealPlan->name,
            'emissionDate' => now()->format('d/m/Y'),
            'startDate' => $startDate->format('d/m/Y'),
            'endDate' => $endDate->format('d/m/Y'),
            'categories' => $categories,
            'grandTotal' => $grandTotal,
        ];
    }

    /**
     * @return array<string, array<int, array{name: string, unit_name: string, unit_cost: float, equivalent_in_grams: float, total_grams: float}>>
     */
    private function aggregateFoods(): array
    {
        $slots = $this->mealPlan->slots()
            ->with([
                'items' => fn ($q) => $q->with([
                    'recipe.items.food.category',
                    'recipe.items.food.units',
                    'recipe.items.foodUnit',
                    'food.category',
                    'food.units',
                    'foodUnit',
                ]),
            ])
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->get();

        /** @var array<int, array{name: string, category: string, unit_name: string, unit_cost: float, equivalent_in_grams: float, total_grams: float}> */
        $foods = [];

        foreach ($slots as $slot) {
            $slotDiners = $slot->diners ?? 1;

            foreach ($slot->items as $item) {
                $itemDiners = $item->diners ?? $slotDiners;

                if ($item->recipe_id && $item->recipe) {
                    foreach ($item->recipe->items as $recipeItem) {
                        $food = $recipeItem->food;
                        if (! $food) {
                            continue;
                        }

                        $quantityGrams = (float) $recipeItem->quantity * $itemDiners;
                        $this->addFoodEntry($foods, $food, $quantityGrams, $recipeItem->foodUnit);
                    }
                } elseif ($item->food_id && $item->food) {
                    $quantityGrams = (float) $item->quantity * $itemDiners;
                    $this->addFoodEntry($foods, $item->food, $quantityGrams, $item->foodUnit);
                }
            }
        }

        $grouped = [];

        foreach ($foods as $foodData) {
            $grouped[$foodData['category']][] = $foodData;
        }

        ksort($grouped);

        return $grouped;
    }

    /**
     * @param  array<int, array{name: string, category: string, unit_name: string, unit_cost: float, equivalent_in_grams: float, total_grams: float}>  $foods
     */
    private function addFoodEntry(array &$foods, Food $food, float $quantityGrams, ?FoodUnit $foodUnit): void
    {
        $foodId = $food->id;

        if (! isset($foods[$foodId])) {
            $primaryUnit = $foodUnit ?? $food->units->first();

            $foods[$foodId] = [
                'name' => $food->name,
                'category' => $food->category?->name ?? 'Sin categoría',
                'unit_name' => $primaryUnit?->name ?? 'Por configurar',
                'unit_cost' => (float) ($primaryUnit?->cost ?? 0),
                'equivalent_in_grams' => (float) ($primaryUnit?->equivalent_in_grams ?? 0),
                'total_grams' => 0.0,
            ];
        }

        $foods[$foodId]['total_grams'] += $quantityGrams;
    }
}
