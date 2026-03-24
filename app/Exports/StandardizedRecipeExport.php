<?php

namespace App\Exports;

use App\Enums\MealType;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\MealPlanSlot;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class StandardizedRecipeExport
{
    private int $currentRow = 1;

    private Worksheet $sheet;

    /** @var array<string, string> */
    private const array MEAL_TYPE_LABELS = [
        'breakfast' => 'Desayuno',
        'morning_snack' => 'Snack 1',
        'lunch' => 'Almuerzo',
        'afternoon_snack' => 'Snack 2',
        'dinner' => 'Cena',
    ];

    private const string COL_NUM = 'A';

    private const string COL_INGREDIENT = 'B';

    private const string COL_NET_QTY = 'C';

    private const string COL_UNIT = 'D';

    private const string COL_PERFORMANCE = 'E';

    private const string COL_GROSS_QTY = 'F';

    private const string COL_UNIT_COST = 'G';

    private const string COL_PORTION_COST = 'H';

    private const string COL_TOTAL_COST = 'I';

    public function __construct(
        private readonly MealPlan $mealPlan,
        private readonly string $startDate,
        private readonly string $endDate,
    ) {}

    public function generate(): string
    {
        $spreadsheet = new Spreadsheet;
        $this->sheet = $spreadsheet->getActiveSheet();
        $this->sheet->setTitle('Receta Estandarizada');

        $this->setupColumnWidths();
        $this->writeHeader();
        $this->writeReportBody();

        $filename = 'receta_estandarizada_'.now()->format('Ymd_His').'.xlsx';
        $path = storage_path('app/private/reports/'.$filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return $path;
    }

    private function setupColumnWidths(): void
    {
        $this->sheet->getColumnDimension(self::COL_NUM)->setWidth(5);
        $this->sheet->getColumnDimension(self::COL_INGREDIENT)->setWidth(45);
        $this->sheet->getColumnDimension(self::COL_NET_QTY)->setWidth(18);
        $this->sheet->getColumnDimension(self::COL_UNIT)->setWidth(18);
        $this->sheet->getColumnDimension(self::COL_PERFORMANCE)->setWidth(22);
        $this->sheet->getColumnDimension(self::COL_GROSS_QTY)->setWidth(18);
        $this->sheet->getColumnDimension(self::COL_UNIT_COST)->setWidth(14);
        $this->sheet->getColumnDimension(self::COL_PORTION_COST)->setWidth(18);
        $this->sheet->getColumnDimension(self::COL_TOTAL_COST)->setWidth(18);
    }

    private function writeHeader(): void
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        $this->sheet->mergeCells("A{$this->currentRow}:I{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'ALIMENTOR - SISTEMA DE PLANIFICACIÓN DE DIETAS');
        $this->applyStyle("A{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow++;

        $this->sheet->mergeCells("A{$this->currentRow}:I{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'Comprometidos con tu bienestar');
        $this->applyStyle("A{$this->currentRow}", [
            'font' => ['italic' => true, 'size' => 11, 'color' => ['argb' => 'FF5B9BD5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow++;

        $this->sheet->mergeCells("A{$this->currentRow}:I{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'REPORTE DE RECETA ESTANDARIZADA');
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
        $this->setCellValue("E{$this->currentRow}", $this->mealPlan->name);
        $this->currentRow++;

        $this->setCellValue("A{$this->currentRow}", 'Período:');
        $this->applyStyle("A{$this->currentRow}", ['font' => ['bold' => true]]);
        $this->setCellValue("B{$this->currentRow}", $startDate->format('d/m/Y').' al '.$endDate->format('d/m/Y'));

        $this->setCellValue("D{$this->currentRow}", 'Usuario:');
        $this->applyStyle("D{$this->currentRow}", ['font' => ['bold' => true]]);
        $this->setCellValue("E{$this->currentRow}", 'Sistema Alimentor');
        $this->currentRow += 2;
    }

    private function writeReportBody(): void
    {
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

        foreach ($slotsByDate as $date => $daySlots) {
            $this->writeDateHeader(Carbon::parse($date));

            $slotsByMealType = $daySlots->keyBy(fn (MealPlanSlot $slot) => $slot->meal_type->value);

            foreach (MealType::cases() as $mealType) {
                if (! $slotsByMealType->has($mealType->value)) {
                    continue;
                }

                $slot = $slotsByMealType->get($mealType->value);
                $this->writeMealTypeSection($slot);
            }
        }
    }

    private function writeDateHeader(Carbon $date): void
    {
        $this->sheet->mergeCells("A{$this->currentRow}:I{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", $date->translatedFormat('l d/m/Y'));
        $this->applyStyle("A{$this->currentRow}:I{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 12, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow++;
    }

    private function writeMealTypeSection(MealPlanSlot $slot): void
    {
        $mealLabel = self::MEAL_TYPE_LABELS[$slot->meal_type->value] ?? $slot->meal_type->value;

        $this->sheet->mergeCells("A{$this->currentRow}:I{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", $mealLabel);
        $this->applyStyle("A{$this->currentRow}:I{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2E75B6']],
        ]);
        $this->currentRow++;

        $recipeItems = $slot->items->filter(fn (MealPlanItem $item) => $item->recipe_id !== null);
        $looseItems = $slot->items->filter(fn (MealPlanItem $item) => $item->recipe_id === null && $item->food_id !== null);

        $mealPortionCostTotal = 0.0;
        $mealTotalCostTotal = 0.0;

        if ($recipeItems->isNotEmpty()) {
            $this->sheet->mergeCells("A{$this->currentRow}:I{$this->currentRow}");
            $this->setCellValue("A{$this->currentRow}", 'RECETAS');
            $this->applyStyle("A{$this->currentRow}", ['font' => ['bold' => true, 'italic' => true]]);
            $this->currentRow++;

            foreach ($recipeItems as $mealPlanItem) {
                [$portionCost, $totalCost] = $this->writeRecipeBlock($mealPlanItem);
                $mealPortionCostTotal += $portionCost;
                $mealTotalCostTotal += $totalCost;
            }
        }

        if ($looseItems->isNotEmpty()) {
            $this->sheet->mergeCells("A{$this->currentRow}:I{$this->currentRow}");
            $this->setCellValue("A{$this->currentRow}", 'ALIMENTOS INDIVIDUALES');
            $this->applyStyle("A{$this->currentRow}", ['font' => ['bold' => true, 'italic' => true]]);
            $this->currentRow++;

            $this->writeTableHeader();

            $index = 1;
            foreach ($looseItems as $item) {
                [$portionCost, $totalCost] = $this->writeLooseFoodRow($item, $index);
                $mealPortionCostTotal += $portionCost;
                $mealTotalCostTotal += $totalCost;
                $index++;
            }
        }

        $this->writeMealTotal($mealLabel, $mealPortionCostTotal, $mealTotalCostTotal);
        $this->currentRow++;
    }

    /**
     * @return array{float, float}
     */
    private function writeRecipeBlock(MealPlanItem $mealPlanItem): array
    {
        $recipe = $mealPlanItem->recipe;
        $diners = $mealPlanItem->diners ?? 1;

        $this->sheet->mergeCells("A{$this->currentRow}:I{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", "{$recipe->name} - Porciones: {$diners}");
        $this->applyStyle("A{$this->currentRow}", [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD6E4F0']],
        ]);
        $this->currentRow++;

        $this->writeTableHeader();

        $recipeTotalPortionCost = 0.0;
        $recipeTotalCost = 0.0;

        foreach ($recipe->items as $index => $recipeItem) {
            $food = $recipeItem->food;
            $foodUnit = $recipeItem->foodUnit;
            $quantity = (float) $recipeItem->quantity;

            $unitName = $foodUnit?->name ?? 'Por configurar';
            $equivalentInGrams = (float) ($foodUnit?->equivalent_in_grams ?? 1);
            $netQuantityGrams = $quantity * $equivalentInGrams;

            $performance = (float) ($food?->performance ?? 100);
            $grossQuantityGrams = $performance > 0 ? ($netQuantityGrams / ($performance / 100)) : $netQuantityGrams;

            $unitCost = (float) ($foodUnit?->cost ?? 0);
            $portionCost = $unitCost * $quantity;
            $totalCost = $portionCost * $diners;

            $recipeTotalPortionCost += $portionCost;
            $recipeTotalCost += $totalCost;

            $row = $this->currentRow;
            $this->setCellValue("A{$row}", $index + 1);
            $this->setCellValue("B{$row}", $food?->name ?? 'Desconocido');
            $this->setCellValue("C{$row}", number_format($netQuantityGrams, 2));
            $this->setCellValue("D{$row}", $unitName);
            $this->setCellValue("E{$row}", number_format($performance, 1));
            $this->setCellValue("F{$row}", number_format($grossQuantityGrams, 2));
            $this->setCellValue("G{$row}", number_format($unitCost, 2));
            $this->setCellValue("H{$row}", number_format($portionCost, 2));
            $this->setCellValue("I{$row}", number_format($totalCost, 2));

            $this->applyStyle("A{$row}:I{$row}", [
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD9D9D9']]],
            ]);
            $this->applyStyle("C{$row}:I{$row}", [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ]);

            $this->currentRow++;
        }

        return [$recipeTotalPortionCost, $recipeTotalCost];
    }

    /**
     * @return array{float, float}
     */
    private function writeLooseFoodRow(MealPlanItem $item, int $index): array
    {
        $food = $item->food;
        $foodUnit = $item->foodUnit;
        $quantity = (float) $item->quantity;
        $diners = $item->diners ?? 1;

        $unitName = $foodUnit?->name ?? 'Por configurar';
        $equivalentInGrams = (float) ($foodUnit?->equivalent_in_grams ?? 1);
        $netQuantityGrams = $quantity * $equivalentInGrams;

        $performance = (float) ($food?->performance ?? 100);
        $grossQuantityGrams = $performance > 0 ? ($netQuantityGrams / ($performance / 100)) : $netQuantityGrams;

        $unitCost = (float) ($foodUnit?->cost ?? 0);
        $portionCost = $unitCost * $quantity;
        $totalCost = $portionCost * $diners;

        $row = $this->currentRow;
        $this->setCellValue("A{$row}", $index);
        $this->setCellValue("B{$row}", $food?->name ?? 'Desconocido');
        $this->setCellValue("C{$row}", number_format($netQuantityGrams, 2));
        $this->setCellValue("D{$row}", $unitName);
        $this->setCellValue("E{$row}", number_format($performance, 1));
        $this->setCellValue("F{$row}", number_format($grossQuantityGrams, 2));
        $this->setCellValue("G{$row}", number_format($unitCost, 2));
        $this->setCellValue("H{$row}", number_format($portionCost, 2));
        $this->setCellValue("I{$row}", number_format($totalCost, 2));

        $this->applyStyle("A{$row}:I{$row}", [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD9D9D9']]],
        ]);
        $this->applyStyle("C{$row}:I{$row}", [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        $this->currentRow++;

        return [$portionCost, $totalCost];
    }

    private function writeTableHeader(): void
    {
        $headers = ['N°', 'Ingrediente', 'Cantidad Neta (g)', 'Unidad', 'Rendimiento Estimado (%)', 'Cantidad Bruta (g)', 'Costo/Unidad', 'Costo por Porción', 'Presupuesto Final'];
        $cols = [self::COL_NUM, self::COL_INGREDIENT, self::COL_NET_QTY, self::COL_UNIT, self::COL_PERFORMANCE, self::COL_GROSS_QTY, self::COL_UNIT_COST, self::COL_PORTION_COST, self::COL_TOTAL_COST];

        foreach ($cols as $i => $col) {
            $this->setCellValue("{$col}{$this->currentRow}", $headers[$i]);
        }

        $this->applyStyle("A{$this->currentRow}:I{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $this->currentRow++;
    }

    private function writeMealTotal(string $mealLabel, float $portionCost, float $totalCost): void
    {
        $this->sheet->mergeCells("A{$this->currentRow}:F{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", "Total {$mealLabel}");
        $this->sheet->mergeCells("G{$this->currentRow}:H{$this->currentRow}");
        $this->setCellValue("G{$this->currentRow}", number_format($portionCost, 2));
        $this->setCellValue("I{$this->currentRow}", number_format($totalCost, 2));

        $this->applyStyle("A{$this->currentRow}:I{$this->currentRow}", [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFE2EFDA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
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
