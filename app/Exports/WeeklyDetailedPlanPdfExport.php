<?php

namespace App\Exports;

use App\Enums\MealType;
use App\Models\Food;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class WeeklyDetailedPlanPdfExport
{
    /** @var array<string, string> */
    private const array MEAL_TYPE_LABELS = [
        'breakfast' => 'Desayuno',
        'morning_snack' => 'Refrigerio 1',
        'lunch' => 'Almuerzo',
        'afternoon_snack' => 'Refrigerio 2',
        'dinner' => 'Cena',
    ];

    public function __construct(
        private readonly MealPlan $mealPlan,
        private readonly string $startDate,
        private readonly string $endDate,
        private readonly string $objective,
        private readonly string $userName,
        private readonly string $nutritionist,
    ) {}

    public function generate(): string
    {
        Carbon::setLocale('es');

        $data = $this->buildReportData();

        $pdf = Pdf::loadView('reports.weekly-detailed-plan', $data)
            ->setPaper('a4', 'landscape');

        $filename = 'plan_semanal_detallado_'.now()->format('Ymd_His').'.pdf';
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
                    'food',
                ])->orderBy('sort_order'),
            ])
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->orderBy('date')
            ->orderBy('meal_type')
            ->get();

        $slotsByDate = $slots->groupBy(fn ($slot) => $slot->date->toDateString());

        $days = [];
        $dayNumber = 1;

        foreach ($dates as $dateString) {
            $date = Carbon::parse($dateString);
            $daySlots = $slotsByDate->get($dateString);
            $slotsByMealType = $daySlots ? $daySlots->keyBy(fn ($s) => $s->meal_type->value) : collect();

            $dayKcal = 0.0;
            $meals = [];

            foreach (MealType::cases() as $mealType) {
                $mealLabel = self::MEAL_TYPE_LABELS[$mealType->value];
                $slot = $slotsByMealType->get($mealType->value);

                $itemName = '';
                $itemKcal = 0.0;

                if ($slot) {
                    $names = [];

                    foreach ($slot->items as $item) {
                        if ($item->recipe_id && $item->recipe) {
                            $names[] = mb_strtoupper($item->recipe->name);
                            $itemKcal += $this->calculateItemKcal($item);
                        } elseif ($item->food_id && $item->food) {
                            $names[] = mb_strtoupper($item->food->name);
                            $itemKcal += $this->calculateFoodKcal($item->food, (float) $item->quantity);
                        }
                    }

                    $itemName = implode(', ', $names);
                }

                $dayKcal += $itemKcal;

                $meals[] = [
                    'label' => $mealLabel,
                    'detail' => $itemName,
                    'kcal' => $itemKcal > 0 ? round($itemKcal) : '',
                ];
            }

            $days[] = [
                'number' => $dayNumber,
                'date' => ucfirst($date->translatedFormat('l')),
                'dateShort' => $date->format('d/m/Y'),
                'meals' => $meals,
                'totalKcal' => round($dayKcal),
            ];

            $dayNumber++;
        }

        return [
            'mealPlanName' => $this->mealPlan->name,
            'emissionDate' => now()->format('d/m/Y'),
            'startDate' => $startDate->format('d/m/Y'),
            'endDate' => $endDate->format('d/m/Y'),
            'objective' => $this->objective,
            'userName' => $this->userName,
            'nutritionist' => $this->nutritionist,
            'days' => $days,
        ];
    }

    private function calculateItemKcal(MealPlanItem $item): float
    {
        $totalKcal = 0.0;

        foreach ($item->recipe->items as $recipeItem) {
            if (! $recipeItem->food) {
                continue;
            }

            $totalKcal += $this->calculateFoodKcal($recipeItem->food, (float) $recipeItem->quantity);
        }

        return $totalKcal;
    }

    private function calculateFoodKcal(Food $food, float $quantityGrams): float
    {
        $nutrients = $food->nutrients ?? [];

        foreach ($nutrients as $nutrient) {
            if ($nutrient['key'] === 'energia_kcal') {
                return ($quantityGrams / 100) * (float) ($nutrient['value'] ?? 0);
            }
        }

        return 0.0;
    }
}
