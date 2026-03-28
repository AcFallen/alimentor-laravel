<?php

namespace App\Exports;

use App\Enums\MealType;
use App\Models\Food;
use App\Models\MealPlan;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class MacronutrientReportPdfExport
{
    /** @var array<string, string> */
    private const array MEAL_TYPE_LABELS = [
        'breakfast' => 'DESAYUNO',
        'morning_snack' => 'MERIENDA 1',
        'lunch' => 'ALMUERZO',
        'afternoon_snack' => 'MERIENDA 2',
        'dinner' => 'CENA',
    ];

    private const float PROTEIN_KCAL_FACTOR = 4.0;

    private const float FAT_KCAL_FACTOR = 9.0;

    private const float CARB_KCAL_FACTOR = 4.0;

    public function __construct(
        private readonly MealPlan $mealPlan,
        private readonly string $startDate,
        private readonly string $endDate,
    ) {}

    public function generate(): string
    {
        Carbon::setLocale('es');

        $data = $this->buildReportData();

        $pdf = Pdf::loadView('reports.macronutrient', $data)
            ->setPaper('a4', 'landscape');

        $filename = 'reporte_macronutrientes_'.now()->format('Ymd_His').'.pdf';
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

        $slots = $this->mealPlan->slots()
            ->with([
                'items' => fn ($q) => $q->with([
                    'recipe.items.food',
                    'recipe.items.foodUnit',
                    'food',
                    'foodUnit',
                ])->orderBy('sort_order'),
            ])
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->orderBy('date')
            ->orderBy('meal_type')
            ->get();

        $slotsByDate = $slots->groupBy(fn ($slot) => $slot->date->toDateString());

        $days = [];

        foreach ($dates as $dateString) {
            $daySlots = $slotsByDate->get($dateString);

            if (! $daySlots || $daySlots->isEmpty()) {
                continue;
            }

            $slotsByMealType = $daySlots->keyBy(fn ($s) => $s->meal_type->value);
            $dayTotals = ['net' => 0.0, 'gross' => 0.0, 'prot_g' => 0.0, 'prot_kcal' => 0.0, 'fat_g' => 0.0, 'fat_kcal' => 0.0, 'carb_g' => 0.0, 'carb_kcal' => 0.0, 'total_kcal' => 0.0];
            $meals = [];
            $mealTotalsMap = [];

            foreach (MealType::cases() as $mealType) {
                if (! $slotsByMealType->has($mealType->value)) {
                    continue;
                }

                $slot = $slotsByMealType->get($mealType->value);
                $mealLabel = self::MEAL_TYPE_LABELS[$mealType->value];
                $mealSum = ['net' => 0.0, 'gross' => 0.0, 'prot_g' => 0.0, 'prot_kcal' => 0.0, 'fat_g' => 0.0, 'fat_kcal' => 0.0, 'carb_g' => 0.0, 'carb_kcal' => 0.0, 'total_kcal' => 0.0];
                $sections = [];

                foreach ($slot->items as $item) {
                    if ($item->recipe_id && $item->recipe) {
                        $recipeRows = [];

                        foreach ($item->recipe->items as $recipeItem) {
                            $food = $recipeItem->food;
                            if (! $food) {
                                continue;
                            }

                            $quantityGrams = (float) $recipeItem->quantity;
                            $performance = (float) ($food->performance ?? 100);
                            $grossGrams = $performance > 0 ? ($quantityGrams / ($performance / 100)) : $quantityGrams;
                            $macros = $this->calculateMacros($food, $quantityGrams);
                            $this->addToSum($mealSum, $quantityGrams, $grossGrams, $macros);

                            $recipeRows[] = $this->formatFoodRow($food->name, $quantityGrams, $grossGrams, $macros);
                        }

                        $sections[] = ['type' => 'recipe', 'name' => $item->recipe->name, 'rows' => $recipeRows];
                    } elseif ($item->food_id && $item->food) {
                        $food = $item->food;
                        $quantityGrams = (float) $item->quantity;
                        $performance = (float) ($food->performance ?? 100);
                        $grossGrams = $performance > 0 ? ($quantityGrams / ($performance / 100)) : $quantityGrams;
                        $macros = $this->calculateMacros($food, $quantityGrams);
                        $this->addToSum($mealSum, $quantityGrams, $grossGrams, $macros);

                        $sections[] = ['type' => 'food', 'rows' => [$this->formatFoodRow($food->name, $quantityGrams, $grossGrams, $macros)]];
                    }
                }

                foreach ($dayTotals as $key => $val) {
                    $dayTotals[$key] += $mealSum[$key];
                }

                $mealTotalsMap[$mealType->value] = $mealSum;

                $meals[] = [
                    'label' => $mealLabel,
                    'sections' => $sections,
                    'subtotal' => $this->formatTotalRow($mealSum),
                    'vcKcal' => number_format($mealSum['total_kcal'], 2),
                ];
            }

            $totalKcal = $dayTotals['total_kcal'];
            $mealDists = [];

            foreach ($mealTotalsMap as $mealValue => $mealSum) {
                $mealDists[self::MEAL_TYPE_LABELS[$mealValue]] = $totalKcal > 0 ? number_format($mealSum['total_kcal'] / $totalKcal * 100, 1).'%' : '0.0%';
            }

            $protPercent = $totalKcal > 0 ? ($dayTotals['prot_kcal'] / $totalKcal * 100) : 0;
            $fatPercent = $totalKcal > 0 ? ($dayTotals['fat_kcal'] / $totalKcal * 100) : 0;
            $carbPercent = $totalKcal > 0 ? ($dayTotals['carb_kcal'] / $totalKcal * 100) : 0;

            $days[] = [
                'date' => ucfirst(Carbon::parse($dateString)->translatedFormat('l')).' '.Carbon::parse($dateString)->format('d/m/Y'),
                'meals' => $meals,
                'mealDists' => $mealDists,
                'total' => $this->formatTotalRow($dayTotals),
                'vct' => [
                    'g' => ['prot' => number_format($dayTotals['prot_g'], 2), 'fat' => number_format($dayTotals['fat_g'], 2), 'carb' => number_format($dayTotals['carb_g'], 2)],
                    'kcal' => ['prot' => number_format($dayTotals['prot_kcal'], 2), 'fat' => number_format($dayTotals['fat_kcal'], 2), 'carb' => number_format($dayTotals['carb_kcal'], 2)],
                    'percent' => ['prot' => number_format($protPercent, 1).'%', 'fat' => number_format($fatPercent, 1).'%', 'carb' => number_format($carbPercent, 1).'%'],
                ],
            ];
        }

        return [
            'mealPlanName' => $this->mealPlan->name,
            'emissionDate' => now()->format('d/m/Y'),
            'startDate' => $startDate->format('d/m/Y'),
            'endDate' => $endDate->format('d/m/Y'),
            'days' => $days,
        ];
    }

    /**
     * @return array{prot_g: float, prot_kcal: float, fat_g: float, fat_kcal: float, carb_g: float, carb_kcal: float, total_kcal: float}
     */
    private function calculateMacros(Food $food, float $quantityGrams): array
    {
        $nutrients = $food->nutrients ?? [];
        $nutrientMap = [];

        foreach ($nutrients as $nutrient) {
            $nutrientMap[$nutrient['key']] = (float) ($nutrient['value'] ?? 0);
        }

        $factor = $quantityGrams / 100;

        $protG = ($nutrientMap['proteinas'] ?? 0) * $factor;
        $fatG = ($nutrientMap['grasa_total'] ?? 0) * $factor;
        $carbG = ($nutrientMap['carbohidratos_t'] ?? 0) * $factor;

        $protKcal = $protG * self::PROTEIN_KCAL_FACTOR;
        $fatKcal = $fatG * self::FAT_KCAL_FACTOR;
        $carbKcal = $carbG * self::CARB_KCAL_FACTOR;

        return [
            'prot_g' => $protG, 'prot_kcal' => $protKcal,
            'fat_g' => $fatG, 'fat_kcal' => $fatKcal,
            'carb_g' => $carbG, 'carb_kcal' => $carbKcal,
            'total_kcal' => $protKcal + $fatKcal + $carbKcal,
        ];
    }

    /**
     * @param  array<string, float>  $sum
     * @param  array{prot_g: float, prot_kcal: float, fat_g: float, fat_kcal: float, carb_g: float, carb_kcal: float, total_kcal: float}  $macros
     */
    private function addToSum(array &$sum, float $net, float $gross, array $macros): void
    {
        $sum['net'] += $net;
        $sum['gross'] += $gross;

        foreach ($macros as $key => $val) {
            $sum[$key] += $val;
        }
    }

    /**
     * @param  array{prot_g: float, prot_kcal: float, fat_g: float, fat_kcal: float, carb_g: float, carb_kcal: float, total_kcal: float}  $macros
     * @return array<string, string>
     */
    private function formatFoodRow(string $name, float $net, float $gross, array $macros): array
    {
        return [
            'name' => $name,
            'net' => number_format($net, 2),
            'gross' => number_format($gross, 2),
            'prot_g' => number_format($macros['prot_g'], 2),
            'prot_kcal' => number_format($macros['prot_kcal'], 2),
            'fat_g' => number_format($macros['fat_g'], 2),
            'fat_kcal' => number_format($macros['fat_kcal'], 2),
            'carb_g' => number_format($macros['carb_g'], 2),
            'carb_kcal' => number_format($macros['carb_kcal'], 2),
            'total_kcal' => number_format($macros['total_kcal'], 2),
        ];
    }

    /**
     * @param  array<string, float>  $sum
     * @return array<string, string>
     */
    private function formatTotalRow(array $sum): array
    {
        return [
            'net' => number_format($sum['net'], 2),
            'gross' => number_format($sum['gross'], 2),
            'prot_g' => number_format($sum['prot_g'], 2),
            'prot_kcal' => number_format($sum['prot_kcal'], 2),
            'fat_g' => number_format($sum['fat_g'], 2),
            'fat_kcal' => number_format($sum['fat_kcal'], 2),
            'carb_g' => number_format($sum['carb_g'], 2),
            'carb_kcal' => number_format($sum['carb_kcal'], 2),
            'total_kcal' => number_format($sum['total_kcal'], 2),
        ];
    }
}
