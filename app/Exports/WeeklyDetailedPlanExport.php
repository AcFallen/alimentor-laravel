<?php

namespace App\Exports;

use App\Enums\MealType;
use App\Models\Food;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
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

class WeeklyDetailedPlanExport
{
    private int $currentRow = 1;

    private Worksheet $sheet;

    private const string LAST_COL = 'G';

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

        $spreadsheet = new Spreadsheet;
        $this->sheet = $spreadsheet->getActiveSheet();
        $this->sheet->setTitle('Plan Semanal Detallado');

        $this->setupColumnWidths();
        $this->writeHeader();
        $dayTotals = $this->writeReportBody();
        $this->writeDailyChart($dayTotals);

        $filename = 'plan_semanal_detallado_'.now()->format('Ymd_His').'.xlsx';
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
        $this->sheet->getColumnDimension('A')->setWidth(5);
        $this->sheet->getColumnDimension('B')->setWidth(14);
        $this->sheet->getColumnDimension('C')->setWidth(16);
        $this->sheet->getColumnDimension('D')->setWidth(20);
        $this->sheet->getColumnDimension('E')->setWidth(20);
        $this->sheet->getColumnDimension('F')->setWidth(20);
        $this->sheet->getColumnDimension('G')->setWidth(24);
    }

    private function writeHeader(): void
    {
        $this->sheet->mergeCells("A{$this->currentRow}:G{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'ALIMENTOR - SISTEMA DE PLANIFICACIÓN DE DIETAS');
        $this->applyStyle("A{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow++;

        $this->sheet->mergeCells("A{$this->currentRow}:G{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'Comprometidos con tu bienestar');
        $this->applyStyle("A{$this->currentRow}", [
            'font' => ['italic' => true, 'size' => 11, 'color' => ['argb' => 'FF5B9BD5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow++;

        $this->sheet->mergeCells("A{$this->currentRow}:G{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'INFORME DE PLAN DE ALIMENTACIÓN SEMANAL DETALLADO');
        $this->applyStyle("A{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 13, 'color' => ['argb' => 'FF2E75B6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow += 2;

        $this->setCellValue("A{$this->currentRow}", 'Fecha de emisión:');
        $this->applyStyle("A{$this->currentRow}", ['font' => ['bold' => true]]);
        $this->setCellValue("B{$this->currentRow}", now()->format('d/m/Y'));
        $this->setCellValue("C{$this->currentRow}", 'Objetivo:');
        $this->applyStyle("C{$this->currentRow}", ['font' => ['bold' => true]]);
        $this->sheet->mergeCells("D{$this->currentRow}:F{$this->currentRow}");
        $this->setCellValue("D{$this->currentRow}", $this->objective);
        $this->currentRow++;

        $this->setCellValue("A{$this->currentRow}", 'Usuario:');
        $this->applyStyle("A{$this->currentRow}", ['font' => ['bold' => true]]);
        $this->setCellValue("B{$this->currentRow}", $this->userName);
        $this->setCellValue("C{$this->currentRow}", 'Nutricionista:');
        $this->applyStyle("C{$this->currentRow}", ['font' => ['bold' => true]]);
        $this->sheet->mergeCells("D{$this->currentRow}:F{$this->currentRow}");
        $this->setCellValue("D{$this->currentRow}", $this->nutritionist);
        $this->currentRow += 2;
    }

    private function writeTableHeader(): void
    {
        $this->setCellValue("A{$this->currentRow}", 'N°');
        $this->setCellValue("B{$this->currentRow}", 'FECHA');
        $this->sheet->mergeCells("C{$this->currentRow}:F{$this->currentRow}");
        $this->setCellValue("C{$this->currentRow}", 'DETALLE DEL MENÚ');
        $this->setCellValue("G{$this->currentRow}", 'Aporte Energético (Kcal)');

        $this->applyStyle("A{$this->currentRow}:G{$this->currentRow}", [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2E75B6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $this->currentRow++;
    }

    /**
     * @return array<string, float>
     */
    private function writeReportBody(): array
    {
        $this->writeTableHeader();

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

        /** @var array<string, float> */
        $dayTotals = [];
        $dayNumber = 1;

        foreach ($dates as $dateString) {
            $date = Carbon::parse($dateString);
            $daySlots = $slotsByDate->get($dateString);
            $dayLabel = ucfirst($date->translatedFormat('l'))."\n".$date->format('d/m/Y');

            $slotsByMealType = $daySlots ? $daySlots->keyBy(fn ($s) => $s->meal_type->value) : collect();

            $dayStartRow = $this->currentRow;
            $dayKcal = 0.0;

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

                $this->setCellValue("C{$this->currentRow}", $mealLabel);
                $this->applyStyle("C{$this->currentRow}", [
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD6E4F0']],
                ]);
                $this->sheet->mergeCells("D{$this->currentRow}:F{$this->currentRow}");
                $this->setCellValue("D{$this->currentRow}", $itemName);
                $this->setCellValue("G{$this->currentRow}", $itemKcal > 0 ? round($itemKcal) : '');
                $this->applyStyle("G{$this->currentRow}", ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]]);

                $this->applyStyle("A{$this->currentRow}:G{$this->currentRow}", [
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD9D9D9']]],
                ]);

                $dayKcal += $itemKcal;
                $this->currentRow++;
            }

            $dayEndRow = $this->currentRow - 1;

            $this->sheet->mergeCells("A{$dayStartRow}:A{$dayEndRow}");
            $this->setCellValue("A{$dayStartRow}", $dayNumber);
            $this->applyStyle("A{$dayStartRow}", [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'font' => ['bold' => true],
            ]);

            $this->sheet->mergeCells("B{$dayStartRow}:B{$dayEndRow}");
            $this->setCellValue("B{$dayStartRow}", $dayLabel);
            $this->applyStyle("B{$dayStartRow}", [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'font' => ['bold' => true],
            ]);

            $this->sheet->mergeCells("C{$this->currentRow}:F{$this->currentRow}");
            $this->setCellValue("F{$this->currentRow}", 'Sub Total');
            $this->setCellValue("G{$this->currentRow}", round($dayKcal));

            $this->applyStyle("A{$this->currentRow}:G{$this->currentRow}", [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2EFDA']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);

            $dayTotals[$dayLabel] = $dayKcal;
            $this->currentRow++;
            $dayNumber++;
        }

        return $dayTotals;
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

    /**
     * @param  array<string, float>  $dayTotals
     */
    private function writeDailyChart(array $dayTotals): void
    {
        if (empty($dayTotals)) {
            return;
        }

        $this->currentRow += 2;
        $chartDataRow = $this->currentRow;
        $labels = array_keys($dayTotals);
        $values = array_values($dayTotals);

        foreach ($labels as $i => $label) {
            $cleanLabel = str_replace("\n", ' ', $label);
            $this->setCellValue('A'.($chartDataRow + $i), $cleanLabel);
            $this->setCellValue('B'.($chartDataRow + $i), round($values[$i]));
        }

        $count = count($labels);
        $lastDataRow = $chartDataRow + $count - 1;
        $sheetTitle = $this->sheet->getTitle();

        $categoryLabels = [
            new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'{$sheetTitle}'!A{$chartDataRow}:A{$lastDataRow}", null, $count),
        ];

        $dataSeriesValues = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'{$sheetTitle}'!B{$chartDataRow}:B{$lastDataRow}", null, $count);

        $colors = ['4472C4', 'ED7D31', 'A5A5A5', 'FFC000', '5B9BD5', '70AD47', '264478'];
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
        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $plotArea = new PlotArea(new Layout, [$series]);
        $legend = new Legend(Legend::POSITION_BOTTOM, null, false);
        $title = new Title('Aporte Energético Diario (Kcal)');

        $chart = new Chart(
            'daily_kcal_chart',
            $title,
            $legend,
            $plotArea,
        );

        $chartStartRow = $lastDataRow + 2;
        $chartEndRow = $chartStartRow + 18;
        $chart->setTopLeftPosition("A{$chartStartRow}");
        $chart->setBottomRightPosition("G{$chartEndRow}");

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
