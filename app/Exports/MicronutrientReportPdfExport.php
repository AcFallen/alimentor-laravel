<?php

namespace App\Exports;

use App\Enums\MealType;
use App\Models\Food;
use App\Models\MealPlan;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class MicronutrientReportPdfExport
{
    /** @var array<string, string> */
    private const array NUTRIENT_LABELS = [
        'calcio' => 'Calcio',
        'fosforo' => 'Fósforo',
        'zinc' => 'Zinc',
        'hierro' => 'Hierro',
        'beta_caroteno' => 'Beta Caroteno',
        'vitamina_a' => 'Vitamina A',
        'tiamina' => 'Tiamina',
        'riboflavina' => 'Riboflavina',
        'niacina' => 'Niacina',
        'vitamina_c' => 'Vitamina C',
        'acido_folico' => 'Ácido Fólico',
        'sodio' => 'Sodio',
        'potasio' => 'Potasio',
    ];

    /** @var array<string, string> */
    private const array MEAL_TYPE_LABELS = [
        'breakfast' => 'DESAYUNO',
        'morning_snack' => 'MERIENDA 1',
        'lunch' => 'ALMUERZO',
        'afternoon_snack' => 'MERIENDA 2',
        'dinner' => 'CENA',
    ];

    /**
     * @param  list<string>  $nutrientKeys
     */
    public function __construct(
        private readonly MealPlan $mealPlan,
        private readonly string $startDate,
        private readonly string $endDate,
        private readonly array $nutrientKeys,
    ) {}

    public function generate(): string
    {
        Carbon::setLocale('es');

        $data = $this->buildReportData();

        $pdf = Pdf::loadView('reports.micronutrient', $data)
            ->setPaper('a4', 'landscape');

        $filename = 'reporte_micronutrientes_'.now()->format('Ymd_His').'.pdf';
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

        $nutrientHeaders = [];

        foreach ($this->nutrientKeys as $key) {
            $nutrientHeaders[] = ['key' => $key, 'label' => self::NUTRIENT_LABELS[$key] ?? $key];
        }

        $period = CarbonPeriod::create($this->startDate, $this->endDate);
        $dates = [];

        foreach ($period as $date) {
            $dates[] = $date->toDateString();
        }

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

        $slotsByDate = $slots->groupBy(fn ($slot) => $slot->date->toDateString());

        $days = [];

        foreach ($dates as $dateString) {
            $daySlots = $slotsByDate->get($dateString);

            if (! $daySlots || $daySlots->isEmpty()) {
                continue;
            }

            $slotsByMealType = $daySlots->keyBy(fn ($s) => $s->meal_type->value);
            $dayTotals = $this->emptyRow();
            $meals = [];

            foreach (MealType::cases() as $mealType) {
                if (! $slotsByMealType->has($mealType->value)) {
                    continue;
                }

                $slot = $slotsByMealType->get($mealType->value);
                $mealLabel = self::MEAL_TYPE_LABELS[$mealType->value];
                $mealSum = $this->emptyRow();
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
                            $nutrients = $this->calculateNutrients($food, $quantityGrams);
                            $this->addToRow($mealSum, ['net' => $quantityGrams, 'gross' => $grossGrams] + $nutrients);

                            $recipeRows[] = $this->formatRow($food->name, $quantityGrams, $grossGrams, $nutrients);
                        }

                        $sections[] = ['type' => 'recipe', 'name' => $item->recipe->name, 'rows' => $recipeRows];
                    } elseif ($item->food_id && $item->food) {
                        $food = $item->food;
                        $quantityGrams = (float) $item->quantity;
                        $performance = (float) ($food->performance ?? 100);
                        $grossGrams = $performance > 0 ? ($quantityGrams / ($performance / 100)) : $quantityGrams;
                        $nutrients = $this->calculateNutrients($food, $quantityGrams);
                        $this->addToRow($mealSum, ['net' => $quantityGrams, 'gross' => $grossGrams] + $nutrients);

                        $sections[] = ['type' => 'food', 'rows' => [$this->formatRow($food->name, $quantityGrams, $grossGrams, $nutrients)]];
                    }
                }

                $this->addToRow($dayTotals, $mealSum);

                $meals[] = [
                    'label' => $mealLabel,
                    'sections' => $sections,
                    'subtotal' => $this->formatTotalValues($mealSum),
                ];
            }

            $days[] = [
                'date' => Carbon::parse($dateString)->format('d/m/Y'),
                'meals' => $meals,
                'total' => $this->formatTotalValues($dayTotals),
            ];
        }

        return [
            'mealPlanName' => $this->mealPlan->name,
            'emissionDate' => now()->format('d/m/Y'),
            'startDate' => $startDate->format('d/m/Y'),
            'endDate' => $endDate->format('d/m/Y'),
            'nutrientHeaders' => $nutrientHeaders,
            'days' => $days,
        ];
    }

    /**
     * @return array<string, float>
     */
    private function calculateNutrients(Food $food, float $quantityGrams): array
    {
        $foodNutrients = $food->nutrients ?? [];
        $nutrientMap = [];

        foreach ($foodNutrients as $nutrient) {
            $nutrientMap[$nutrient['key']] = (float) ($nutrient['value'] ?? 0);
        }

        $factor = $quantityGrams / 100;
        $result = [];

        foreach ($this->nutrientKeys as $key) {
            $result[$key] = ($nutrientMap[$key] ?? 0) * $factor;
        }

        return $result;
    }

    /**
     * @param  array<string, float>  $nutrients
     * @return array<string, string>
     */
    private function formatRow(string $name, float $net, float $gross, array $nutrients): array
    {
        $row = ['name' => $name, 'net' => number_format($net, 2), 'gross' => number_format($gross, 2)];

        foreach ($this->nutrientKeys as $key) {
            $row[$key] = number_format($nutrients[$key] ?? 0, 2);
        }

        return $row;
    }

    /**
     * @param  array<string, float>  $sum
     * @return array<string, string>
     */
    private function formatTotalValues(array $sum): array
    {
        $row = ['net' => number_format($sum['net'], 2), 'gross' => number_format($sum['gross'], 2)];

        foreach ($this->nutrientKeys as $key) {
            $row[$key] = number_format($sum[$key] ?? 0, 2);
        }

        return $row;
    }

    /**
     * @return array<string, float>
     */
    private function emptyRow(): array
    {
        $row = ['net' => 0.0, 'gross' => 0.0];

        foreach ($this->nutrientKeys as $key) {
            $row[$key] = 0.0;
        }

        return $row;
    }

    /**
     * @param  array<string, float>  $target
     * @param  array<string, float>  $source
     */
    private function addToRow(array &$target, array $source): void
    {
        foreach ($target as $key => $val) {
            $target[$key] += $source[$key] ?? 0;
        }
    }
}
