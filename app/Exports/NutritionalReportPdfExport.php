<?php

namespace App\Exports;

use App\Enums\MealType;
use App\Models\Food;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class NutritionalReportPdfExport
{
    /** @var array<string, string> */
    private const array MEAL_TYPE_LABELS = [
        'breakfast' => 'Desayuno',
        'morning_snack' => 'Snack 1',
        'lunch' => 'Almuerzo',
        'afternoon_snack' => 'Snack 2',
        'dinner' => 'Cena',
    ];

    /** @var list<string> */
    private const array NUTRIENT_KEYS = ['energia_kcal', 'proteinas', 'carbohidratos_t', 'grasa_total'];

    public function __construct(
        private readonly MealPlan $mealPlan,
        private readonly string $startDate,
        private readonly string $endDate,
    ) {}

    public function generate(): string
    {
        Carbon::setLocale('es');

        $data = $this->buildReportData();

        $pdf = Pdf::loadView('reports.nutritional', $data)
            ->setPaper('a4', 'landscape');

        $filename = 'reporte_nutricional_'.now()->format('Ymd_His').'.pdf';
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
            $dates[] = $date->toDateString();
        }

        $rawData = $this->aggregateData();

        $days = [];
        $grandTotals = [0.0, 0.0, 0.0, 0.0];

        foreach ($dates as $date) {
            $parsedDate = Carbon::parse($date);
            $meals = [];
            $dayTotals = [0.0, 0.0, 0.0, 0.0];

            foreach (MealType::cases() as $mealType) {
                $mealLabel = self::MEAL_TYPE_LABELS[$mealType->value];
                $items = $rawData[$mealType->value][$date] ?? [];

                $mealNutrients = [0.0, 0.0, 0.0, 0.0];
                $formattedItems = [];

                foreach ($items as $item) {
                    $formattedItems[] = [
                        'name' => $item['name'],
                        'energy' => number_format($item['nutrients'][0], 1),
                        'protein' => number_format($item['nutrients'][1], 1),
                        'carbs' => number_format($item['nutrients'][2], 1),
                        'fat' => number_format($item['nutrients'][3], 1),
                    ];

                    for ($n = 0; $n < 4; $n++) {
                        $mealNutrients[$n] += $item['nutrients'][$n];
                    }
                }

                for ($n = 0; $n < 4; $n++) {
                    $dayTotals[$n] += $mealNutrients[$n];
                }

                $meals[] = [
                    'label' => $mealLabel,
                    'items' => $formattedItems,
                    'totals' => [
                        'energy' => number_format($mealNutrients[0], 1),
                        'protein' => number_format($mealNutrients[1], 1),
                        'carbs' => number_format($mealNutrients[2], 1),
                        'fat' => number_format($mealNutrients[3], 1),
                    ],
                ];
            }

            for ($n = 0; $n < 4; $n++) {
                $grandTotals[$n] += $dayTotals[$n];
            }

            $days[] = [
                'date' => $parsedDate->translatedFormat('l d/m/Y'),
                'meals' => $meals,
                'totals' => [
                    'energy' => number_format($dayTotals[0], 1),
                    'protein' => number_format($dayTotals[1], 1),
                    'carbs' => number_format($dayTotals[2], 1),
                    'fat' => number_format($dayTotals[3], 1),
                ],
            ];
        }

        return [
            'mealPlanName' => $this->mealPlan->name,
            'emissionDate' => now()->format('d/m/Y'),
            'startDate' => $startDate->format('d/m/Y'),
            'endDate' => $endDate->format('d/m/Y'),
            'days' => $days,
            'grandTotals' => [
                'energy' => number_format($grandTotals[0], 1),
                'protein' => number_format($grandTotals[1], 1),
                'carbs' => number_format($grandTotals[2], 1),
                'fat' => number_format($grandTotals[3], 1),
            ],
        ];
    }

    /**
     * @return array<string, array<string, list<array{name: string, nutrients: array{float, float, float, float}}>>>
     */
    private function aggregateData(): array
    {
        $slots = $this->mealPlan->slots()
            ->with([
                'items' => fn ($q) => $q->with([
                    'recipe.items.food',
                    'food',
                ])->orderBy('sort_order'),
            ])
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->orderBy('date')
            ->orderBy('meal_type')
            ->get();

        /** @var array<string, array<string, list<array{name: string, nutrients: array{float, float, float, float}}>>> */
        $data = [];

        foreach ($slots as $slot) {
            $date = $slot->date->toDateString();
            $mealType = $slot->meal_type->value;

            foreach ($slot->items as $item) {
                if ($item->recipe_id && $item->recipe) {
                    $nutrients = $this->calculateRecipeNutrients($item);
                    $data[$mealType][$date][] = [
                        'name' => $item->recipe->name,
                        'nutrients' => $nutrients,
                    ];
                } elseif ($item->food_id && $item->food) {
                    $quantityGrams = (float) $item->quantity;
                    $nutrients = $this->getFoodNutrients($item->food, $quantityGrams);
                    $data[$mealType][$date][] = [
                        'name' => $item->food->name,
                        'nutrients' => $nutrients,
                    ];
                }
            }
        }

        return $data;
    }

    /**
     * @return array{float, float, float, float}
     */
    private function calculateRecipeNutrients(MealPlanItem $item): array
    {
        $totals = [0.0, 0.0, 0.0, 0.0];

        foreach ($item->recipe->items as $recipeItem) {
            if (! $recipeItem->food) {
                continue;
            }

            $quantityGrams = (float) $recipeItem->quantity;
            $foodNutrients = $this->getFoodNutrients($recipeItem->food, $quantityGrams);

            for ($i = 0; $i < 4; $i++) {
                $totals[$i] += $foodNutrients[$i];
            }
        }

        return [$totals[0], $totals[1], $totals[2], $totals[3]];
    }

    /**
     * @return array{float, float, float, float}
     */
    private function getFoodNutrients(Food $food, float $quantityGrams): array
    {
        $nutrients = $food->nutrients ?? [];
        $values = [0.0, 0.0, 0.0, 0.0];

        $nutrientMap = [];
        foreach ($nutrients as $nutrient) {
            $nutrientMap[$nutrient['key']] = (float) ($nutrient['value'] ?? 0);
        }

        foreach (self::NUTRIENT_KEYS as $i => $key) {
            $per100g = $nutrientMap[$key] ?? 0.0;
            $values[$i] = ($quantityGrams / 100) * $per100g;
        }

        return [$values[0], $values[1], $values[2], $values[3]];
    }
}
