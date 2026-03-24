<?php

namespace App\Exports;

use App\Models\Food;
use App\Models\FoodUnit;
use App\Models\MealPlan;
use Carbon\Carbon;
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

class KitchenOrderExport
{
    private int $currentRow = 1;

    private Worksheet $sheet;

    private const string LAST_COL = 'E';

    public function __construct(
        private readonly MealPlan $mealPlan,
        private readonly string $startDate,
        private readonly string $endDate,
    ) {}

    public function generate(): string
    {
        Carbon::setLocale('es');

        $spreadsheet = new Spreadsheet;
        $this->sheet = $spreadsheet->getActiveSheet();
        $this->sheet->setTitle('Orden de Cocina');

        $this->setupColumnWidths();
        $this->writeHeader();
        $this->writeReportBody();

        $filename = 'orden_cocina_'.now()->format('Ymd_His').'.xlsx';
        $path = storage_path('app/private/reports/'.$filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        $writer->save($path);

        return $path;
    }

    private function setupColumnWidths(): void
    {
        $this->sheet->getColumnDimension('A')->setWidth(40);
        $this->sheet->getColumnDimension('B')->setWidth(18);
        $this->sheet->getColumnDimension('C')->setWidth(18);
        $this->sheet->getColumnDimension('D')->setWidth(24);
        $this->sheet->getColumnDimension('E')->setWidth(14);
    }

    private function writeHeader(): void
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        $this->sheet->mergeCells("A{$this->currentRow}:E{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'ALIMENTOR - SISTEMA DE PLANIFICACIÓN DE DIETAS');
        $this->applyStyle("A{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow++;

        $this->sheet->mergeCells("A{$this->currentRow}:E{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'Comprometidos con tu bienestar');
        $this->applyStyle("A{$this->currentRow}", [
            'font' => ['italic' => true, 'size' => 11, 'color' => ['argb' => 'FF5B9BD5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow++;

        $this->sheet->mergeCells("A{$this->currentRow}:E{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'ORDEN DE COCINA');
        $this->applyStyle("A{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF2E75B6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow += 2;

        $this->setCellValue("A{$this->currentRow}", 'Fecha de emisión:');
        $this->applyStyle("A{$this->currentRow}", ['font' => ['bold' => true]]);
        $this->setCellValue("B{$this->currentRow}", now()->format('d/m/Y'));
        $this->setCellValue("C{$this->currentRow}", 'Planificación:');
        $this->applyStyle("C{$this->currentRow}", ['font' => ['bold' => true]]);
        $this->setCellValue("D{$this->currentRow}", $this->mealPlan->name);
        $this->currentRow++;

        $this->setCellValue("A{$this->currentRow}", 'Período:');
        $this->applyStyle("A{$this->currentRow}", ['font' => ['bold' => true]]);
        $this->setCellValue("B{$this->currentRow}", $startDate->format('d/m/Y').' al '.$endDate->format('d/m/Y'));
        $this->setCellValue("C{$this->currentRow}", 'Usuario:');
        $this->applyStyle("C{$this->currentRow}", ['font' => ['bold' => true]]);
        $this->setCellValue("D{$this->currentRow}", 'Sistema Alimentor');
        $this->currentRow += 2;
    }

    private function writeTableHeader(): void
    {
        $headers = ['Alimento', 'Cantidad Total', 'Unidad', 'Total por unidad (aprox)', 'Costo Total'];
        $cols = ['A', 'B', 'C', 'D', 'E'];

        foreach ($cols as $i => $col) {
            $this->setCellValue("{$col}{$this->currentRow}", $headers[$i]);
        }

        $this->applyStyle("A{$this->currentRow}:E{$this->currentRow}", [
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

        $tableStartRow = $this->currentRow;
        $grandTotal = 0.0;
        /** @var array<string, float> */
        $categoryTotals = [];

        foreach ($foodAggregation as $categoryName => $foods) {
            $this->sheet->mergeCells("A{$this->currentRow}:E{$this->currentRow}");
            $this->setCellValue("A{$this->currentRow}", $categoryName);
            $this->applyStyle("A{$this->currentRow}:E{$this->currentRow}", [
                'font' => ['bold' => true, 'italic' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD6E4F0']],
            ]);
            $this->currentRow++;

            $categoryTotal = 0.0;

            foreach ($foods as $foodData) {
                $totalKg = $foodData['total_grams'] / 1000;
                $unitName = $foodData['unit_name'];
                $equivalentInGrams = $foodData['equivalent_in_grams'];
                $unitCost = $foodData['unit_cost'];

                $this->setCellValue("A{$this->currentRow}", $foodData['name']);
                $this->setCellValue("B{$this->currentRow}", number_format($totalKg, 2));
                $this->setCellValue("C{$this->currentRow}", $unitName);

                if ($equivalentInGrams > 0 && $unitCost > 0) {
                    $totalUnits = $foodData['total_grams'] / $equivalentInGrams;
                    $cost = $totalUnits * $unitCost;
                    $this->setCellValue("D{$this->currentRow}", number_format($totalUnits, 2));
                    $this->setCellValue("E{$this->currentRow}", number_format($cost, 2));
                    $categoryTotal += $cost;
                } else {
                    $this->setCellValue("D{$this->currentRow}", 'Por configurar');
                    $this->setCellValue("E{$this->currentRow}", 'N/A');
                }

                $this->applyStyle("A{$this->currentRow}:E{$this->currentRow}", [
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD9D9D9']]],
                ]);
                $this->applyStyle("B{$this->currentRow}:E{$this->currentRow}", [
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);

                $this->currentRow++;
            }

            $this->sheet->mergeCells("A{$this->currentRow}:D{$this->currentRow}");
            $this->setCellValue("A{$this->currentRow}", "Subtotal {$categoryName}:");
            $this->setCellValue("E{$this->currentRow}", number_format($categoryTotal, 2));

            $this->applyStyle("A{$this->currentRow}:E{$this->currentRow}", [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2EFDA']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);

            $categoryTotals[$categoryName] = $categoryTotal;
            $grandTotal += $categoryTotal;
            $this->currentRow += 2;
        }

        $this->sheet->mergeCells("A{$this->currentRow}:D{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'COSTO TOTAL PLANIFICACIÓN');
        $this->setCellValue("E{$this->currentRow}", number_format($grandTotal, 2));

        $this->applyStyle("A{$this->currentRow}:E{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);

        $this->writeCategoryCostChart($categoryTotals, $tableStartRow);
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

    /**
     * @param  array<string, float>  $categoryTotals
     */
    private function writeCategoryCostChart(array $categoryTotals, int $tableStartRow): void
    {
        $filtered = array_filter($categoryTotals, fn (float $cost) => $cost > 0);

        if (empty($filtered)) {
            return;
        }

        $chartDataRow = $this->currentRow + 3;
        $categories = array_keys($filtered);
        $values = array_values($filtered);

        foreach ($categories as $i => $name) {
            $this->setCellValue('G'.($chartDataRow + $i), $name);
            $this->setCellValue('H'.($chartDataRow + $i), $values[$i]);
        }

        $count = count($categories);
        $lastDataRow = $chartDataRow + $count - 1;
        $sheetTitle = $this->sheet->getTitle();

        $categoryLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!G{$chartDataRow}:G{$lastDataRow}", null, $count),
        ];

        $dataSeriesValues = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!H{$chartDataRow}:H{$lastDataRow}", null, $count);

        $colors = ['4472C4', 'ED7D31', 'A5A5A5', 'FFC000', '5B9BD5', '70AD47', '264478', '9B57A0', '636363', 'EB5757', '8FAADC', 'C55A11'];
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

        $chart->setTopLeftPosition("G{$tableStartRow}");
        $chart->setBottomRightPosition('N'.($tableStartRow + 20));

        $this->sheet->addChart($chart);
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
