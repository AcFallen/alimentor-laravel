<?php

namespace App\Exports;

use App\Enums\MealType;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\MealPlanSlot;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class StandardizedRecipePdfExport
{
    /** @var array<string, string> */
    private const array MEAL_TYPE_LABELS = [
        'breakfast' => 'Desayuno',
        'morning_snack' => 'Snack 1',
        'lunch' => 'Almuerzo',
        'afternoon_snack' => 'Snack 2',
        'dinner' => 'Cena',
    ];

    public function __construct(
        private readonly MealPlan $mealPlan,
        private readonly string $startDate,
        private readonly string $endDate,
    ) {}

    public function generate(): string
    {
        Carbon::setLocale('es');

        $data = $this->buildReportData();

        $pdf = Pdf::loadView('reports.standardized-recipe', $data)
            ->setPaper('a4', 'landscape');

        $filename = 'receta_estandarizada_'.now()->format('Ymd_His').'.pdf';
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

        $slots = $this->mealPlan->slots()
            ->with([
                'items' => fn ($q) => $q->with([
                    'recipe.items.food.units',
                    'recipe.items.foodUnit',
                    'food.units',
                    'foodUnit',
                ])->orderBy('sort_order'),
            ])
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->orderBy('date')
            ->orderBy('meal_type')
            ->get();

        $slotsByDate = $slots->groupBy(fn (MealPlanSlot $slot) => $slot->date->toDateString());

        $days = [];
        $grandPortionCost = 0.0;
        $grandTotalCost = 0.0;

        foreach ($slotsByDate as $date => $daySlots) {
            $slotsByMealType = $daySlots->keyBy(fn (MealPlanSlot $slot) => $slot->meal_type->value);

            $dayPortionCost = 0.0;
            $dayTotalCost = 0.0;
            $meals = [];

            foreach (MealType::cases() as $mealType) {
                if (! $slotsByMealType->has($mealType->value)) {
                    continue;
                }

                $slot = $slotsByMealType->get($mealType->value);
                $mealData = $this->buildMealData($slot);
                $meals[] = $mealData;
                $dayPortionCost += $mealData['portionCost'];
                $dayTotalCost += $mealData['totalCost'];
            }

            $parsedDate = Carbon::parse($date);

            $days[] = [
                'date' => $parsedDate->translatedFormat('l d/m/Y'),
                'dateShort' => $parsedDate->format('d/m/Y'),
                'meals' => $meals,
                'portionCost' => $dayPortionCost,
                'totalCost' => $dayTotalCost,
            ];

            $grandPortionCost += $dayPortionCost;
            $grandTotalCost += $dayTotalCost;
        }

        return [
            'mealPlanName' => $this->mealPlan->name,
            'emissionDate' => now()->format('d/m/Y'),
            'startDate' => $startDate->format('d/m/Y'),
            'endDate' => $endDate->format('d/m/Y'),
            'days' => $days,
            'grandPortionCost' => $grandPortionCost,
            'grandTotalCost' => $grandTotalCost,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMealData(MealPlanSlot $slot): array
    {
        $mealLabel = self::MEAL_TYPE_LABELS[$slot->meal_type->value] ?? $slot->meal_type->value;

        $recipeItems = $slot->items->filter(fn (MealPlanItem $item) => $item->recipe_id !== null);
        $looseItems = $slot->items->filter(fn (MealPlanItem $item) => $item->recipe_id === null && $item->food_id !== null);

        $recipes = [];
        $looseFoods = [];
        $mealPortionCost = 0.0;
        $mealTotalCost = 0.0;

        foreach ($recipeItems as $mealPlanItem) {
            $recipeData = $this->buildRecipeData($mealPlanItem);
            $recipes[] = $recipeData;
            $mealPortionCost += $recipeData['portionCost'];
            $mealTotalCost += $recipeData['totalCost'];
        }

        foreach ($looseItems as $item) {
            $foodData = $this->buildLooseFoodData($item);
            $looseFoods[] = $foodData;
            $mealPortionCost += $foodData['portionCost'];
            $mealTotalCost += $foodData['totalCost'];
        }

        return [
            'label' => $mealLabel,
            'recipes' => $recipes,
            'looseFoods' => $looseFoods,
            'portionCost' => $mealPortionCost,
            'totalCost' => $mealTotalCost,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRecipeData(MealPlanItem $mealPlanItem): array
    {
        $recipe = $mealPlanItem->recipe;
        $diners = $mealPlanItem->diners ?? 1;

        $ingredients = [];
        $recipeTotalPortionCost = 0.0;
        $recipeTotalCost = 0.0;

        foreach ($recipe->items as $index => $recipeItem) {
            $food = $recipeItem->food;
            $foodUnit = $recipeItem->foodUnit;
            $quantity = (float) $recipeItem->quantity;

            $unitName = $foodUnit?->name ?? 'Por configurar';
            $equivalentInGrams = (float) ($foodUnit?->equivalent_in_grams ?? 1);
            $netQuantityGrams = $quantity;

            $performance = (float) ($food?->performance ?? 100);
            $grossQuantityGrams = $performance > 0 ? ($netQuantityGrams / ($performance / 100)) : $netQuantityGrams;

            $unitCost = (float) ($foodUnit?->cost ?? 0);
            $unitsUsed = $equivalentInGrams > 0 ? ($netQuantityGrams / $equivalentInGrams) : 0;
            $portionCost = $unitCost * $unitsUsed;
            $totalCost = $portionCost * $diners;

            $recipeTotalPortionCost += $portionCost;
            $recipeTotalCost += $totalCost;

            $ingredients[] = [
                'index' => $index + 1,
                'name' => $food?->name ?? 'Desconocido',
                'netQty' => number_format($netQuantityGrams, 2),
                'unit' => $unitName,
                'performance' => number_format($performance, 1),
                'grossQty' => number_format($grossQuantityGrams, 2),
                'unitCost' => number_format($unitCost, 2),
                'portionCost' => number_format($portionCost, 2),
                'totalCost' => number_format($totalCost, 2),
            ];
        }

        return [
            'name' => $recipe->name,
            'diners' => $diners,
            'ingredients' => $ingredients,
            'portionCost' => $recipeTotalPortionCost,
            'totalCost' => $recipeTotalCost,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildLooseFoodData(MealPlanItem $item): array
    {
        $food = $item->food;
        $foodUnit = $item->foodUnit;
        $quantity = (float) $item->quantity;
        $diners = $item->diners ?? 1;

        $unitName = $foodUnit?->name ?? 'Por configurar';
        $equivalentInGrams = (float) ($foodUnit?->equivalent_in_grams ?? 1);
        $netQuantityGrams = $quantity;

        $performance = (float) ($food?->performance ?? 100);
        $grossQuantityGrams = $performance > 0 ? ($netQuantityGrams / ($performance / 100)) : $netQuantityGrams;

        $unitCost = (float) ($foodUnit?->cost ?? 0);
        $unitsUsed = $equivalentInGrams > 0 ? ($netQuantityGrams / $equivalentInGrams) : 0;
        $portionCost = $unitCost * $unitsUsed;
        $totalCost = $portionCost * $diners;

        return [
            'name' => $food?->name ?? 'Desconocido',
            'netQty' => number_format($netQuantityGrams, 2),
            'unit' => $unitName,
            'performance' => number_format($performance, 1),
            'grossQty' => number_format($grossQuantityGrams, 2),
            'unitCost' => number_format($unitCost, 2),
            'portionCost' => number_format($portionCost, 2),
            'totalCost' => number_format($totalCost, 2),
        ];
    }
}
