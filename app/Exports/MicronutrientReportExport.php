<?php

namespace App\Exports;

use App\Enums\MealType;
use App\Models\Food;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MicronutrientReportExport
{
    private int $currentRow = 1;

    private Worksheet $sheet;

    /** @var list<string> */
    private array $nutrientKeys;

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
        array $nutrientKeys,
    ) {
        $this->nutrientKeys = $nutrientKeys;
    }

    public function generate(): string
    {
        Carbon::setLocale('es');

        $spreadsheet = new Spreadsheet;
        $this->sheet = $spreadsheet->getActiveSheet();
        $this->sheet->setTitle('Micronutrientes');

        $this->setupColumnWidths();
        $this->writeHeader();
        $this->writeReportBody();

        $filename = 'reporte_micronutrientes_'.now()->format('Ymd_His').'.xlsx';
        $path = storage_path('app/private/reports/'.$filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }

    private function lastColumn(): string
    {
        $totalCols = 3 + count($this->nutrientKeys);

        return $this->columnLetter($totalCols - 1);
    }

    private function columnLetter(int $index): string
    {
        $letter = '';

        while ($index >= 0) {
            $letter = chr(65 + ($index % 26)).$letter;
            $index = intdiv($index, 26) - 1;
        }

        return $letter;
    }

    private function setupColumnWidths(): void
    {
        $this->sheet->getColumnDimension('A')->setWidth(42);
        $this->sheet->getColumnDimension('B')->setWidth(15);
        $this->sheet->getColumnDimension('C')->setWidth(16);

        foreach ($this->nutrientKeys as $i => $key) {
            $col = $this->columnLetter(3 + $i);
            $this->sheet->getColumnDimension($col)->setWidth(14);
        }
    }

    private function writeHeader(): void
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);
        $lastCol = $this->lastColumn();

        $this->sheet->mergeCells("A{$this->currentRow}:{$lastCol}{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'ALIMENTOR - SISTEMA DE PLANIFICACIÓN DE DIETAS');
        $this->applyStyle("A{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow++;

        $this->sheet->mergeCells("A{$this->currentRow}:{$lastCol}{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'Comprometidos con tu bienestar');
        $this->applyStyle("A{$this->currentRow}", [
            'font' => ['italic' => true, 'size' => 11, 'color' => ['argb' => 'FF5B9BD5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow++;

        $this->sheet->mergeCells("A{$this->currentRow}:{$lastCol}{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'REPORTE GENERAL DE MICRONUTRIENTES');
        $this->applyStyle("A{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF2E75B6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow += 2;

        $this->setCellValue("A{$this->currentRow}", 'Fecha de emisión:');
        $this->applyStyle("A{$this->currentRow}", ['font' => ['bold' => true]]);
        $this->setCellValue("B{$this->currentRow}", now()->format('d/m/Y'));
        $this->currentRow++;

        $this->setCellValue("A{$this->currentRow}", 'Planificación:');
        $this->applyStyle("A{$this->currentRow}", ['font' => ['bold' => true]]);
        $this->setCellValue("B{$this->currentRow}", $this->mealPlan->name);
        $this->currentRow++;

        $this->setCellValue("A{$this->currentRow}", 'Período:');
        $this->applyStyle("A{$this->currentRow}", ['font' => ['bold' => true]]);
        $this->setCellValue("B{$this->currentRow}", $startDate->format('d/m/Y').' - '.$endDate->format('d/m/Y'));
        $this->currentRow += 2;
    }

    private function writeTableHeader(): void
    {
        $lastCol = $this->lastColumn();

        $this->setCellValue("A{$this->currentRow}", 'ALIMENTO');
        $this->setCellValue("B{$this->currentRow}", 'PESO NETO (g)');
        $this->setCellValue("C{$this->currentRow}", 'PESO BRUTO (g)');

        foreach ($this->nutrientKeys as $i => $key) {
            $col = $this->columnLetter(3 + $i);
            $label = self::NUTRIENT_LABELS[$key] ?? $key;
            $this->setCellValue("{$col}{$this->currentRow}", $label);
        }

        $this->applyStyle("A{$this->currentRow}:{$lastCol}{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $this->currentRow++;
    }

    private function writeReportBody(): void
    {
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

        foreach ($dates as $dateString) {
            $daySlots = $slotsByDate->get($dateString);

            if (! $daySlots || $daySlots->isEmpty()) {
                continue;
            }

            $this->writeDateHeader(Carbon::parse($dateString));
            $this->writeTableHeader();

            $lastCol = $this->lastColumn();
            $dayTotals = $this->emptyNutrientRow();
            $slotsByMealType = $daySlots->keyBy(fn ($s) => $s->meal_type->value);

            foreach (MealType::cases() as $mealType) {
                if (! $slotsByMealType->has($mealType->value)) {
                    continue;
                }

                $slot = $slotsByMealType->get($mealType->value);
                $mealLabel = self::MEAL_TYPE_LABELS[$mealType->value];

                $this->sheet->mergeCells("A{$this->currentRow}:{$lastCol}{$this->currentRow}");
                $this->setCellValue("A{$this->currentRow}", $mealLabel);
                $this->applyStyle("A{$this->currentRow}:{$lastCol}{$this->currentRow}", [
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2E75B6']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $this->currentRow++;

                $mealSum = $this->emptyNutrientRow();

                foreach ($slot->items as $item) {
                    if ($item->recipe_id && $item->recipe) {
                        $this->writeRecipeSection($item, $mealSum);
                    } elseif ($item->food_id && $item->food) {
                        $this->writeLooseFood($item, $mealSum);
                    }
                }

                $this->writeSubtotal($mealLabel, $mealSum);
                $this->addToRow($dayTotals, $mealSum);
            }

            $this->writeDayTotal($dateString, $dayTotals);
            $this->currentRow += 2;
        }
    }

    /**
     * @param  array<string, float>  $mealSum
     */
    private function writeRecipeSection(MealPlanItem $item, array &$mealSum): void
    {
        $lastCol = $this->lastColumn();

        $this->sheet->mergeCells("A{$this->currentRow}:{$lastCol}{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'Recetas: '.$item->recipe->name);
        $this->applyStyle("A{$this->currentRow}", [
            'font' => ['italic' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD6E4F0']],
        ]);
        $this->currentRow++;

        foreach ($item->recipe->items as $recipeItem) {
            $food = $recipeItem->food;
            if (! $food) {
                continue;
            }

            $quantityGrams = (float) $recipeItem->quantity;
            $performance = (float) ($food->performance ?? 100);
            $grossGrams = $performance > 0 ? ($quantityGrams / ($performance / 100)) : $quantityGrams;

            $nutrients = $this->calculateNutrients($food, $quantityGrams);
            $this->writeFoodRow($food->name, $quantityGrams, $grossGrams, $nutrients);
            $this->addToRow($mealSum, $nutrients);
        }
    }

    /**
     * @param  array<string, float>  $mealSum
     */
    private function writeLooseFood(MealPlanItem $item, array &$mealSum): void
    {
        $food = $item->food;
        $quantityGrams = (float) $item->quantity;
        $performance = (float) ($food->performance ?? 100);
        $grossGrams = $performance > 0 ? ($quantityGrams / ($performance / 100)) : $quantityGrams;

        $nutrients = $this->calculateNutrients($food, $quantityGrams);
        $this->writeFoodRow($food->name, $quantityGrams, $grossGrams, $nutrients);
        $this->addToRow($mealSum, $nutrients);
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
        $result = ['net' => $quantityGrams, 'gross' => $quantityGrams];

        foreach ($this->nutrientKeys as $key) {
            $result[$key] = ($nutrientMap[$key] ?? 0) * $factor;
        }

        return $result;
    }

    /**
     * @param  array<string, float>  $nutrients
     */
    private function writeFoodRow(string $name, float $netGrams, float $grossGrams, array $nutrients): void
    {
        $lastCol = $this->lastColumn();

        $this->setCellValue("A{$this->currentRow}", $name);
        $this->setCellValue("B{$this->currentRow}", $netGrams);
        $this->setCellValue("C{$this->currentRow}", $grossGrams);

        foreach ($this->nutrientKeys as $i => $key) {
            $col = $this->columnLetter(3 + $i);
            $this->setCellValue("{$col}{$this->currentRow}", round($nutrients[$key], 2));
        }

        $this->applyStyle("B{$this->currentRow}:{$lastCol}{$this->currentRow}", [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        $this->applyStyle("A{$this->currentRow}:{$lastCol}{$this->currentRow}", [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD9D9D9']]],
        ]);

        $this->currentRow++;
    }

    /**
     * @param  array<string, float>  $sum
     */
    private function writeSubtotal(string $mealLabel, array $sum): void
    {
        $lastCol = $this->lastColumn();

        $this->setCellValue("A{$this->currentRow}", "SUBTOTAL {$mealLabel}");
        $this->setCellValue("B{$this->currentRow}", round($sum['net'], 2));
        $this->setCellValue("C{$this->currentRow}", round($sum['gross'], 2));

        foreach ($this->nutrientKeys as $i => $key) {
            $col = $this->columnLetter(3 + $i);
            $this->setCellValue("{$col}{$this->currentRow}", round($sum[$key], 2));
        }

        $this->applyStyle("A{$this->currentRow}:{$lastCol}{$this->currentRow}", [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD6E4F0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $this->applyStyle("A{$this->currentRow}", ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]]);

        $this->currentRow++;
    }

    /**
     * @param  array<string, float>  $totals
     */
    private function writeDayTotal(string $dateString, array $totals): void
    {
        $lastCol = $this->lastColumn();
        $dateFormatted = Carbon::parse($dateString)->format('d/m/Y');

        $this->setCellValue("A{$this->currentRow}", "TOTAL DEL DÍA - {$dateFormatted}");
        $this->setCellValue("B{$this->currentRow}", number_format($totals['net'], 2));
        $this->setCellValue("C{$this->currentRow}", number_format($totals['gross'], 2));

        foreach ($this->nutrientKeys as $i => $key) {
            $col = $this->columnLetter(3 + $i);
            $this->setCellValue("{$col}{$this->currentRow}", number_format($totals[$key], 2));
        }

        $this->applyStyle("A{$this->currentRow}:{$lastCol}{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $this->applyStyle("A{$this->currentRow}", ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]]);

        $this->currentRow++;
    }

    /**
     * @return array<string, float>
     */
    private function emptyNutrientRow(): array
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

    private function writeDateHeader(Carbon $date): void
    {
        $lastCol = $this->lastColumn();

        $this->sheet->mergeCells("A{$this->currentRow}:{$lastCol}{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'FECHA: '.$date->format('d/m/Y'));
        $this->applyStyle("A{$this->currentRow}:{$lastCol}{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow++;
    }

    private function setCellValue(string $cell, mixed $value): void
    {
        $this->sheet->setCellValue($cell, $value);
    }

    /**
     * @param  array<string, mixed>  $styles
     */
    private function applyStyle(string $range, array $styles): void
    {
        $this->sheet->getStyle($range)->applyFromArray($styles);
    }
}
