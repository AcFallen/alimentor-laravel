<?php

namespace App\Exports;

use App\Models\Food;
use App\Models\FoodUnit;
use App\Models\MealPlan;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Layout;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class WeeklyRequirementExport
{
    private int $currentRow = 1;

    private Worksheet $sheet;

    /** @var list<string> */
    private array $dates = [];

    /** @var list<string> */
    private array $dayColumns = [];

    private string $colTotal = '';

    private string $colTotalUnits = '';

    private string $colTotalCost = '';

    private string $lastColumn = '';

    public function __construct(
        private readonly MealPlan $mealPlan,
        private readonly string $startDate,
        private readonly string $endDate,
    ) {}

    public function generate(): string
    {
        Carbon::setLocale('es');

        $this->buildDateColumns();

        $spreadsheet = new Spreadsheet;
        $this->sheet = $spreadsheet->getActiveSheet();
        $this->sheet->setTitle('Requerimiento Semanal');

        $this->setupColumnWidths();
        $this->writeHeader();
        $this->writeReportBody();

        $filename = 'requerimiento_semanal_'.now()->format('Ymd_His').'.xlsx';
        $path = storage_path('app/private/reports/'.$filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        $writer->save($path);

        return $path;
    }

    private function buildDateColumns(): void
    {
        $period = CarbonPeriod::create($this->startDate, $this->endDate);

        foreach ($period as $date) {
            $this->dates[] = $date->toDateString();
        }

        $fixedColumns = 4;
        $startColIndex = $fixedColumns;

        foreach ($this->dates as $i => $date) {
            $this->dayColumns[$date] = $this->columnLetter($startColIndex + $i);
        }

        $afterDays = $startColIndex + count($this->dates);
        $this->colTotal = $this->columnLetter($afterDays);
        $this->colTotalUnits = $this->columnLetter($afterDays + 1);
        $this->colTotalCost = $this->columnLetter($afterDays + 2);
        $this->lastColumn = $this->colTotalCost;
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
        $this->sheet->getColumnDimension('A')->setWidth(5);
        $this->sheet->getColumnDimension('B')->setWidth(40);
        $this->sheet->getColumnDimension('C')->setWidth(14);
        $this->sheet->getColumnDimension('D')->setWidth(16);

        foreach ($this->dayColumns as $col) {
            $this->sheet->getColumnDimension($col)->setWidth(14);
        }

        $this->sheet->getColumnDimension($this->colTotal)->setWidth(18);
        $this->sheet->getColumnDimension($this->colTotalUnits)->setWidth(22);
        $this->sheet->getColumnDimension($this->colTotalCost)->setWidth(14);
    }

    private function writeHeader(): void
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        $this->sheet->mergeCells("A{$this->currentRow}:{$this->lastColumn}{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'ALIMENTOR - SISTEMA DE PLANIFICACIÓN DE DIETAS');
        $this->applyStyle("A{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow++;

        $this->sheet->mergeCells("A{$this->currentRow}:{$this->lastColumn}{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'Comprometidos con tu bienestar');
        $this->applyStyle("A{$this->currentRow}", [
            'font' => ['italic' => true, 'size' => 11, 'color' => ['argb' => 'FF5B9BD5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow++;

        $this->sheet->mergeCells("A{$this->currentRow}:{$this->lastColumn}{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'INFORME SEMANAL DE REQUERIMIENTO DE ALIMENTOS');
        $this->applyStyle("A{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF2E75B6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow += 2;

        $this->setCellValue("A{$this->currentRow}", 'Fecha de emisión:');
        $this->applyStyle("A{$this->currentRow}", ['font' => ['bold' => true]]);
        $this->setCellValue("B{$this->currentRow}", now()->format('d/m/Y'));
        $this->setCellValue("D{$this->currentRow}", 'Planificación:');
        $this->applyStyle("D{$this->currentRow}", ['font' => ['bold' => true]]);
        $firstDayCol = reset($this->dayColumns);
        $this->setCellValue("{$firstDayCol}{$this->currentRow}", $this->mealPlan->name);
        $this->currentRow++;

        $this->setCellValue("A{$this->currentRow}", 'Período:');
        $this->applyStyle("A{$this->currentRow}", ['font' => ['bold' => true]]);
        $this->setCellValue("B{$this->currentRow}", $startDate->format('d/m/Y').' al '.$endDate->format('d/m/Y'));
        $this->setCellValue("D{$this->currentRow}", 'Usuario:');
        $this->applyStyle("D{$this->currentRow}", ['font' => ['bold' => true]]);
        $this->setCellValue("{$firstDayCol}{$this->currentRow}", 'Sistema Alimentor');
        $this->currentRow += 2;
    }

    private function writeTableHeader(): void
    {
        $this->setCellValue("A{$this->currentRow}", 'N°');
        $this->setCellValue("B{$this->currentRow}", 'Alimento');
        $this->setCellValue("C{$this->currentRow}", 'Costo Unidad');
        $this->setCellValue("D{$this->currentRow}", 'Unidad');

        foreach ($this->dayColumns as $date => $col) {
            $dayName = Carbon::parse($date)->translatedFormat('l');
            $this->setCellValue("{$col}{$this->currentRow}", ucfirst($dayName));
        }

        $this->setCellValue("{$this->colTotal}{$this->currentRow}", 'CANTIDAD TOTAL');
        $this->setCellValue("{$this->colTotalUnits}{$this->currentRow}", 'TOTAL POR UNIDAD (APROX)');
        $this->setCellValue("{$this->colTotalCost}{$this->currentRow}", 'COSTO TOTAL');

        $this->applyStyle("A{$this->currentRow}:{$this->lastColumn}{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $this->currentRow++;
    }

    private function writeReportBody(): void
    {
        $foodAggregation = $this->aggregateFoods();

        $this->writeTableHeader();

        $grandTotal = 0.0;
        /** @var array<string, float> */
        $categoryTotals = [];

        foreach ($foodAggregation as $categoryName => $foods) {
            $this->sheet->mergeCells("A{$this->currentRow}:{$this->lastColumn}{$this->currentRow}");
            $this->setCellValue("A{$this->currentRow}", $categoryName);
            $this->applyStyle("A{$this->currentRow}:{$this->lastColumn}{$this->currentRow}", [
                'font' => ['bold' => true, 'italic' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD6E4F0']],
            ]);
            $this->currentRow++;

            $categoryTotal = 0.0;
            $index = 1;

            foreach ($foods as $foodData) {
                $this->setCellValue("A{$this->currentRow}", $index);
                $this->setCellValue("B{$this->currentRow}", $foodData['name']);

                $unitName = $foodData['unit_name'];
                $unitCost = $foodData['unit_cost'];
                $equivalentInGrams = $foodData['equivalent_in_grams'];

                $this->setCellValue("C{$this->currentRow}", $unitCost > 0 ? number_format($unitCost, 2) : 'Por configurar');
                $this->setCellValue("D{$this->currentRow}", $unitName);

                $totalGrams = 0.0;

                foreach ($this->dates as $date) {
                    $col = $this->dayColumns[$date];
                    $dayGrams = $foodData['days'][$date] ?? 0.0;
                    $dayKg = $dayGrams / 1000;
                    $totalGrams += $dayGrams;

                    if ($dayKg > 0) {
                        $this->setCellValue("{$col}{$this->currentRow}", number_format($dayKg, 3));
                    }
                }

                $totalKg = $totalGrams / 1000;
                $this->setCellValue("{$this->colTotal}{$this->currentRow}", number_format($totalKg, 3));

                if ($equivalentInGrams > 0 && $unitCost > 0) {
                    $totalUnits = $totalGrams / $equivalentInGrams;
                    $cost = $totalUnits * $unitCost;
                    $this->setCellValue("{$this->colTotalUnits}{$this->currentRow}", number_format($totalUnits, 2));
                    $this->setCellValue("{$this->colTotalCost}{$this->currentRow}", number_format($cost, 2));
                    $categoryTotal += $cost;
                } else {
                    $this->setCellValue("{$this->colTotalUnits}{$this->currentRow}", 'Por configurar');
                    $this->setCellValue("{$this->colTotalCost}{$this->currentRow}", 'N/A');
                }

                $this->applyStyle("A{$this->currentRow}:{$this->lastColumn}{$this->currentRow}", [
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD9D9D9']]],
                ]);
                $this->applyStyle("C{$this->currentRow}:{$this->lastColumn}{$this->currentRow}", [
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);

                $this->currentRow++;
                $index++;
            }

            $this->sheet->mergeCells("A{$this->currentRow}:{$this->colTotalUnits}{$this->currentRow}");
            $this->setCellValue("A{$this->currentRow}", "Subtotal {$categoryName}:");
            $this->setCellValue("{$this->colTotalCost}{$this->currentRow}", number_format($categoryTotal, 2));

            $this->applyStyle("A{$this->currentRow}:{$this->lastColumn}{$this->currentRow}", [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2EFDA']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);

            $categoryTotals[$categoryName] = $categoryTotal;
            $grandTotal += $categoryTotal;
            $this->currentRow += 2;
        }

        $this->writeGrandTotal($grandTotal);
        $this->currentRow += 2;
        $this->writeCategoryCostChart($categoryTotals);
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

    /**
     * @param  array<string, float>  $categoryTotals
     */
    private function writeCategoryCostChart(array $categoryTotals): void
    {
        $filtered = array_filter($categoryTotals, fn (float $cost) => $cost > 0);

        if (empty($filtered)) {
            return;
        }

        $chartDataRow = $this->currentRow;
        $categories = array_keys($filtered);
        $values = array_values($filtered);

        foreach ($categories as $i => $name) {
            $this->setCellValue('A'.($chartDataRow + $i), $name);
            $this->setCellValue('B'.($chartDataRow + $i), $values[$i]);
        }

        $count = count($categories);
        $lastDataRow = $chartDataRow + $count - 1;
        $sheetTitle = $this->sheet->getTitle();

        $categoryLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!A{$chartDataRow}:A{$lastDataRow}", null, $count),
        ];

        $colors = ['4472C4', 'ED7D31', 'A5A5A5', 'FFC000', '5B9BD5', '70AD47', '264478', '9B57A0', '636363', 'EB5757', '8FAADC', 'C55A11'];

        $dataSeriesValues = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!B{$chartDataRow}:B{$lastDataRow}", null, $count);

        $fillColors = [];
        for ($i = 0; $i < $count; $i++) {
            $fillColors[] = $colors[$i % count($colors)];
        }
        $dataSeriesValues->setFillColor($fillColors);

        $series = new DataSeries(
            DataSeries::TYPE_BARCHART,
            DataSeries::GROUPING_CLUSTERED,
            range(0, 0),
            [],
            $categoryLabels,
            [$dataSeriesValues],
        );
        $series->setPlotDirection(DataSeries::DIRECTION_HORIZONTAL);

        $plotArea = new PlotArea(new Layout, [$series]);
        $legend = new Legend(Legend::POSITION_BOTTOM, null, false);
        $title = new Title('Costo Total por Categoría');

        $chart = new Chart(
            'category_cost_chart',
            $title,
            $legend,
            $plotArea,
        );

        $chartStartRow = $lastDataRow + 2;
        $chartEndRow = $chartStartRow + 18;
        $chart->setTopLeftPosition("A{$chartStartRow}");
        $chart->setBottomRightPosition("{$this->lastColumn}{$chartEndRow}");

        $this->sheet->addChart($chart);

        $this->currentRow = $chartEndRow + 1;
    }

    private function writeGrandTotal(float $total): void
    {
        $this->sheet->mergeCells("A{$this->currentRow}:{$this->colTotalUnits}{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'COSTO TOTAL PLANIFICACIÓN');
        $this->setCellValue("{$this->colTotalCost}{$this->currentRow}", number_format($total, 2));

        $this->applyStyle("A{$this->currentRow}:{$this->lastColumn}{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]],
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
