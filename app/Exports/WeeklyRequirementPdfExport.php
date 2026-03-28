<?php

namespace App\Exports;

use App\Models\Food;
use App\Models\FoodUnit;
use App\Models\MealPlan;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class WeeklyRequirementPdfExport
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

        $pdf = Pdf::loadView('reports.weekly-requirement', $data)
            ->setPaper('a4', 'landscape');

        $filename = 'requerimiento_semanal_'.now()->format('Ymd_His').'.pdf';
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

        $period = CarbonPeriod::create($this->startDate, $this->endDate);
        $dates = [];

        foreach ($period as $date) {
            $dates[] = [
                'key' => $date->toDateString(),
                'label' => ucfirst($date->translatedFormat('l')),
            ];
        }

        $foodAggregation = $this->aggregateFoods();

        $categories = [];
        $grandTotal = 0.0;

        foreach ($foodAggregation as $categoryName => $foods) {
            $categoryTotal = 0.0;
            $rows = [];

            foreach ($foods as $index => $foodData) {
                $totalGrams = 0.0;
                $dayValues = [];

                foreach ($dates as $date) {
                    $dayGrams = $foodData['days'][$date['key']] ?? 0.0;
                    $totalGrams += $dayGrams;
                    $dayKg = $dayGrams / 1000;
                    $dayValues[] = $dayKg > 0 ? number_format($dayKg, 3) : '';
                }

                $totalKg = $totalGrams / 1000;
                $unitCost = $foodData['unit_cost'];
                $equivalentInGrams = $foodData['equivalent_in_grams'];

                $totalUnits = '';
                $cost = '';

                if ($equivalentInGrams > 0 && $unitCost > 0) {
                    $totalUnitsNum = $totalGrams / $equivalentInGrams;
                    $costNum = $totalUnitsNum * $unitCost;
                    $totalUnits = number_format($totalUnitsNum, 2);
                    $cost = number_format($costNum, 2);
                    $categoryTotal += $costNum;
                } else {
                    $totalUnits = 'Por configurar';
                    $cost = 'N/A';
                }

                $rows[] = [
                    'index' => $index + 1,
                    'name' => $foodData['name'],
                    'unitCost' => $unitCost > 0 ? number_format($unitCost, 2) : 'Por configurar',
                    'unitName' => $foodData['unit_name'],
                    'dayValues' => $dayValues,
                    'totalKg' => number_format($totalKg, 3),
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
            'dates' => $dates,
            'categories' => $categories,
            'grandTotal' => $grandTotal,
        ];
    }

    /**
     * @return array<string, array<int, array{name: string, unit_name: string, unit_cost: float, equivalent_in_grams: float, days: array<string, float>}>>
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

        /** @var array<int, array{name: string, category: string, unit_name: string, unit_cost: float, equivalent_in_grams: float, days: array<string, float>}> */
        $foods = [];

        foreach ($slots as $slot) {
            $date = $slot->date->toDateString();
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
                        $this->addFoodEntry($foods, $food, $date, $quantityGrams, $recipeItem->foodUnit);
                    }
                } elseif ($item->food_id && $item->food) {
                    $quantityGrams = (float) $item->quantity * $itemDiners;
                    $this->addFoodEntry($foods, $item->food, $date, $quantityGrams, $item->foodUnit);
                }
            }
        }

        $grouped = [];

        foreach ($foods as $foodData) {
            $category = $foodData['category'];
            $grouped[$category][] = $foodData;
        }

        ksort($grouped);

        return $grouped;
    }

    /**
     * @param  array<int, array{name: string, category: string, unit_name: string, unit_cost: float, equivalent_in_grams: float, days: array<string, float>}>  $foods
     */
    private function addFoodEntry(array &$foods, Food $food, string $date, float $quantityGrams, ?FoodUnit $foodUnit): void
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
                'days' => [],
            ];
        }

        $foods[$foodId]['days'][$date] = ($foods[$foodId]['days'][$date] ?? 0) + $quantityGrams;
    }
}
