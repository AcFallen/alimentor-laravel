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

class NutritionalReportExport
{
    private int $currentRow = 1;

    private Worksheet $sheet;

    /** @var list<string> */
    private array $dates = [];

    private const int COLS_PER_DAY = 5;

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
        $this->buildDates();

        $spreadsheet = new Spreadsheet;
        $this->sheet = $spreadsheet->getActiveSheet();
        $this->sheet->setTitle('Reporte Nutricional');

        $this->setupColumnWidths();
        $this->writeHeader();
        $this->writeReportBody();

        $filename = 'reporte_nutricional_'.now()->format('Ymd_His').'.xlsx';
        $path = storage_path('app/private/reports/'.$filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }

    private function buildDates(): void
    {
        $period = CarbonPeriod::create($this->startDate, $this->endDate);

        foreach ($period as $date) {
            $this->dates[] = $date->toDateString();
        }
    }

    private function lastColumn(): string
    {
        $totalCols = 1 + (count($this->dates) * self::COLS_PER_DAY);

        return $this->columnLetter($totalCols - 1);
    }

    private function dayStartCol(int $dayIndex): int
    {
        return 1 + ($dayIndex * self::COLS_PER_DAY);
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
        $this->sheet->getColumnDimension('A')->setWidth(16);

        foreach ($this->dates as $dayIndex => $date) {
            $startCol = $this->dayStartCol($dayIndex);
            $this->sheet->getColumnDimension($this->columnLetter($startCol))->setWidth(30);
            $this->sheet->getColumnDimension($this->columnLetter($startCol + 1))->setWidth(10);
            $this->sheet->getColumnDimension($this->columnLetter($startCol + 2))->setWidth(10);
            $this->sheet->getColumnDimension($this->columnLetter($startCol + 3))->setWidth(10);
            $this->sheet->getColumnDimension($this->columnLetter($startCol + 4))->setWidth(10);
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
        $this->setCellValue("A{$this->currentRow}", 'REPORTE NUTRICIONAL');
        $this->applyStyle("A{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF2E75B6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow += 2;

        $this->setCellValue("A{$this->currentRow}", 'Fecha de emisión:');
        $this->applyStyle("A{$this->currentRow}", ['font' => ['bold' => true]]);
        $firstDayCol = $this->columnLetter($this->dayStartCol(0));
        $this->setCellValue("{$firstDayCol}{$this->currentRow}", now()->format('d/m/Y'));
        $thirdCol = $this->columnLetter($this->dayStartCol(0) + 2);
        $this->setCellValue("{$thirdCol}{$this->currentRow}", 'Planificación:');
        $this->applyStyle("{$thirdCol}{$this->currentRow}", ['font' => ['bold' => true]]);
        $fourthCol = $this->columnLetter($this->dayStartCol(0) + 3);
        $this->setCellValue("{$fourthCol}{$this->currentRow}", $this->mealPlan->name);
        $this->currentRow++;

        $this->setCellValue("A{$this->currentRow}", 'Período:');
        $this->applyStyle("A{$this->currentRow}", ['font' => ['bold' => true]]);
        $this->setCellValue("{$firstDayCol}{$this->currentRow}", $startDate->format('d/m/Y').' al '.$endDate->format('d/m/Y'));
        $this->setCellValue("{$thirdCol}{$this->currentRow}", 'Usuario:');
        $this->applyStyle("{$thirdCol}{$this->currentRow}", ['font' => ['bold' => true]]);
        $this->setCellValue("{$fourthCol}{$this->currentRow}", 'Sistema Alimentor');
        $this->currentRow += 2;
    }

    private function writeTableHeaders(): void
    {
        $lastCol = $this->lastColumn();

        $this->setCellValue("A{$this->currentRow}", 'Tiempo de Comida');

        foreach ($this->dates as $dayIndex => $date) {
            $startCol = $this->dayStartCol($dayIndex);
            $endCol = $startCol + self::COLS_PER_DAY - 1;
            $startLetter = $this->columnLetter($startCol);
            $endLetter = $this->columnLetter($endCol);

            $dayLabel = ucfirst(Carbon::parse($date)->translatedFormat('l')).'-'.Carbon::parse($date)->format('d/m');
            $this->sheet->mergeCells("{$startLetter}{$this->currentRow}:{$endLetter}{$this->currentRow}");
            $this->setCellValue("{$startLetter}{$this->currentRow}", $dayLabel);
        }

        $this->applyStyle("A{$this->currentRow}:{$lastCol}{$this->currentRow}", [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2E75B6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $this->currentRow++;

        $this->setCellValue("A{$this->currentRow}", '');
        $subHeaders = ['Alimento', 'Ener', 'Prot', 'Carb', 'Grasa'];

        foreach ($this->dates as $dayIndex => $date) {
            $startCol = $this->dayStartCol($dayIndex);

            foreach ($subHeaders as $i => $header) {
                $col = $this->columnLetter($startCol + $i);
                $this->setCellValue("{$col}{$this->currentRow}", $header);
            }
        }

        $this->applyStyle("A{$this->currentRow}:{$lastCol}{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $this->currentRow++;
    }

    private function writeReportBody(): void
    {
        $data = $this->aggregateData();

        $this->writeTableHeaders();

        $lastCol = $this->lastColumn();

        /** @var array<string, array<int, array{float, float, float, float}>> */
        $grandTotals = [];
        foreach ($this->dates as $date) {
            $grandTotals[$date] = [0.0, 0.0, 0.0, 0.0];
        }

        foreach (MealType::cases() as $mealType) {
            $mealLabel = self::MEAL_TYPE_LABELS[$mealType->value];
            $mealData = $data[$mealType->value] ?? [];

            $maxItems = 0;
            foreach ($this->dates as $date) {
                $count = count($mealData[$date] ?? []);
                $maxItems = max($maxItems, $count);
            }

            if ($maxItems === 0) {
                $maxItems = 1;
            }

            $mealStartRow = $this->currentRow;

            $this->setCellValue("A{$this->currentRow}", $mealLabel);
            $this->applyStyle("A{$this->currentRow}", [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2E75B6']],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            for ($itemIndex = 0; $itemIndex < $maxItems; $itemIndex++) {
                $row = $mealStartRow + $itemIndex;

                foreach ($this->dates as $dayIndex => $date) {
                    $startCol = $this->dayStartCol($dayIndex);
                    $items = $mealData[$date] ?? [];

                    if (isset($items[$itemIndex])) {
                        $item = $items[$itemIndex];
                        $this->setCellValue($this->columnLetter($startCol).$row, $item['name']);
                        $this->setCellValue($this->columnLetter($startCol + 1).$row, number_format($item['nutrients'][0], 1));
                        $this->setCellValue($this->columnLetter($startCol + 2).$row, number_format($item['nutrients'][1], 1));
                        $this->setCellValue($this->columnLetter($startCol + 3).$row, number_format($item['nutrients'][2], 1));
                        $this->setCellValue($this->columnLetter($startCol + 4).$row, number_format($item['nutrients'][3], 1));

                        $this->applyStyle($this->columnLetter($startCol + 1)."{$row}:".$this->columnLetter($startCol + 4)."{$row}", [
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                        ]);
                    }
                }

                $this->applyStyle("A{$row}:{$lastCol}{$row}", [
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD9D9D9']]],
                ]);
            }

            if ($maxItems > 1) {
                $mergeEnd = $mealStartRow + $maxItems - 1;
                $this->sheet->mergeCells("A{$mealStartRow}:A{$mergeEnd}");
            }

            $this->currentRow = $mealStartRow + $maxItems;

            foreach ($this->dates as $dayIndex => $date) {
                $startCol = $this->dayStartCol($dayIndex);
                $items = $mealData[$date] ?? [];

                $mealNutrients = [0.0, 0.0, 0.0, 0.0];
                foreach ($items as $item) {
                    for ($n = 0; $n < 4; $n++) {
                        $mealNutrients[$n] += $item['nutrients'][$n];
                    }
                }

                $this->setCellValue($this->columnLetter($startCol).$this->currentRow, 'TOTAL');
                $this->setCellValue($this->columnLetter($startCol + 1).$this->currentRow, number_format($mealNutrients[0], 1));
                $this->setCellValue($this->columnLetter($startCol + 2).$this->currentRow, number_format($mealNutrients[1], 1));
                $this->setCellValue($this->columnLetter($startCol + 3).$this->currentRow, number_format($mealNutrients[2], 1));
                $this->setCellValue($this->columnLetter($startCol + 4).$this->currentRow, number_format($mealNutrients[3], 1));

                for ($n = 0; $n < 4; $n++) {
                    $grandTotals[$date][$n] += $mealNutrients[$n];
                }
            }

            $this->applyStyle("A{$this->currentRow}:{$lastCol}{$this->currentRow}", [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD6E4F0']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);

            $this->currentRow += 2;
        }

        $this->setCellValue("A{$this->currentRow}", 'TOTAL GENERAL');
        $this->applyStyle("A{$this->currentRow}", ['font' => ['bold' => true]]);

        foreach ($this->dates as $dayIndex => $date) {
            $startCol = $this->dayStartCol($dayIndex);
            $totals = $grandTotals[$date];

            $this->setCellValue($this->columnLetter($startCol).$this->currentRow, 'TOTAL');
            $this->setCellValue($this->columnLetter($startCol + 1).$this->currentRow, number_format($totals[0], 1));
            $this->setCellValue($this->columnLetter($startCol + 2).$this->currentRow, number_format($totals[1], 1));
            $this->setCellValue($this->columnLetter($startCol + 3).$this->currentRow, number_format($totals[2], 1));
            $this->setCellValue($this->columnLetter($startCol + 4).$this->currentRow, number_format($totals[3], 1));
        }

        $this->applyStyle("A{$this->currentRow}:{$lastCol}{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
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
                $itemDiners = $item->diners ?? $slot->diners ?? 1;

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
