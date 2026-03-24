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

class MacronutrientReportExport
{
    private int $currentRow = 1;

    private Worksheet $sheet;

    private const string LAST_COL = 'J';

    /** @var array<string, string> */
    private const array MEAL_TYPE_LABELS = [
        'breakfast' => 'DESAYUNO',
        'morning_snack' => 'MERIENDA 1',
        'lunch' => 'ALMUERZO',
        'afternoon_snack' => 'MERIENDA 2',
        'dinner' => 'CENA',
    ];

    /** @var list<string> */
    private const array NUTRIENT_KEYS = ['proteinas', 'grasa_total', 'carbohidratos_t'];

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

        $spreadsheet = new Spreadsheet;
        $this->sheet = $spreadsheet->getActiveSheet();
        $this->sheet->setTitle('Macronutrientes');

        $this->setupColumnWidths();
        $this->writeHeader();
        $this->writeReportBody();

        $filename = 'reporte_macronutrientes_'.now()->format('Ymd_His').'.xlsx';
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
        $this->sheet->getColumnDimension('A')->setWidth(42);
        $this->sheet->getColumnDimension('B')->setWidth(15);
        $this->sheet->getColumnDimension('C')->setWidth(16);
        $this->sheet->getColumnDimension('D')->setWidth(12);
        $this->sheet->getColumnDimension('E')->setWidth(12);
        $this->sheet->getColumnDimension('F')->setWidth(12);
        $this->sheet->getColumnDimension('G')->setWidth(12);
        $this->sheet->getColumnDimension('H')->setWidth(12);
        $this->sheet->getColumnDimension('I')->setWidth(12);
        $this->sheet->getColumnDimension('J')->setWidth(12);
    }

    private function writeHeader(): void
    {
        $startDate = Carbon::parse($this->startDate);
        $endDate = Carbon::parse($this->endDate);

        $this->sheet->mergeCells("A{$this->currentRow}:J{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'ALIMENTOR - SISTEMA DE PLANIFICACIÓN DE DIETAS');
        $this->applyStyle("A{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow++;

        $this->sheet->mergeCells("A{$this->currentRow}:J{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'Comprometidos con tu bienestar');
        $this->applyStyle("A{$this->currentRow}", [
            'font' => ['italic' => true, 'size' => 11, 'color' => ['argb' => 'FF5B9BD5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow++;

        $this->sheet->mergeCells("A{$this->currentRow}:J{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", 'REPORTE DE MACRONUTRIENTES');
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

    private function writeTableHeader(): void
    {
        $this->setCellValue("A{$this->currentRow}", 'ALIMENTO');
        $this->setCellValue("B{$this->currentRow}", 'PESO NETO (g)');
        $this->setCellValue("C{$this->currentRow}", 'PESO BRUTO (g)');
        $this->sheet->mergeCells("D{$this->currentRow}:E{$this->currentRow}");
        $this->setCellValue("D{$this->currentRow}", 'PROTEÍNAS');
        $this->sheet->mergeCells("F{$this->currentRow}:G{$this->currentRow}");
        $this->setCellValue("F{$this->currentRow}", 'GRASAS');
        $this->sheet->mergeCells("H{$this->currentRow}:I{$this->currentRow}");
        $this->setCellValue("H{$this->currentRow}", 'CARBOHIDRATOS');
        $this->setCellValue("J{$this->currentRow}", 'TOTAL');

        $this->applyStyle("A{$this->currentRow}:J{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $this->currentRow++;

        $subHeaders = ['', '', '', 'g', 'kcal', 'g', 'kcal', 'g', 'kcal', 'kcal'];
        $cols = range('A', 'J');

        foreach ($cols as $i => $col) {
            $this->setCellValue("{$col}{$this->currentRow}", $subHeaders[$i]);
        }

        $this->applyStyle("A{$this->currentRow}:J{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 9, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
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

        foreach ($dates as $dateString) {
            $date = Carbon::parse($dateString);
            $daySlots = $slotsByDate->get($dateString);

            if (! $daySlots || $daySlots->isEmpty()) {
                continue;
            }

            $this->writeDateHeader($date);
            $this->writeTableHeader();

            /** @var array<string, array{net: float, gross: float, prot_g: float, prot_kcal: float, fat_g: float, fat_kcal: float, carb_g: float, carb_kcal: float, total_kcal: float}> */
            $mealTotals = [];
            $dayTotals = ['net' => 0.0, 'gross' => 0.0, 'prot_g' => 0.0, 'prot_kcal' => 0.0, 'fat_g' => 0.0, 'fat_kcal' => 0.0, 'carb_g' => 0.0, 'carb_kcal' => 0.0, 'total_kcal' => 0.0];

            $slotsByMealType = $daySlots->keyBy(fn ($s) => $s->meal_type->value);

            foreach (MealType::cases() as $mealType) {
                if (! $slotsByMealType->has($mealType->value)) {
                    continue;
                }

                $slot = $slotsByMealType->get($mealType->value);
                $mealLabel = self::MEAL_TYPE_LABELS[$mealType->value];

                $this->sheet->mergeCells("A{$this->currentRow}:J{$this->currentRow}");
                $this->setCellValue("A{$this->currentRow}", $mealLabel);
                $this->applyStyle("A{$this->currentRow}:J{$this->currentRow}", [
                    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF2E75B6']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $this->currentRow++;

                $mealSum = ['net' => 0.0, 'gross' => 0.0, 'prot_g' => 0.0, 'prot_kcal' => 0.0, 'fat_g' => 0.0, 'fat_kcal' => 0.0, 'carb_g' => 0.0, 'carb_kcal' => 0.0, 'total_kcal' => 0.0];

                foreach ($slot->items as $item) {
                    if ($item->recipe_id && $item->recipe) {
                        $this->writeRecipeSection($item, $mealSum);
                    } elseif ($item->food_id && $item->food) {
                        $this->writeLooseFoodSection($item, $mealSum);
                    }
                }

                $this->writeSubtotal($mealLabel, $mealSum);
                $this->writeVcAndDist($mealLabel, $mealSum['total_kcal']);

                $mealTotals[$mealType->value] = $mealSum;

                foreach ($dayTotals as $key => $val) {
                    $dayTotals[$key] += $mealSum[$key];
                }

                $this->currentRow++;
            }

            $this->writeDayTotal($dayTotals);
            $this->writeVctSummary($dayTotals, $mealTotals);
            $this->currentRow += 2;
        }
    }

    /**
     * @param  array<string, float>  $mealSum
     */
    private function writeRecipeSection(MealPlanItem $item, array &$mealSum): void
    {
        $this->sheet->mergeCells("A{$this->currentRow}:J{$this->currentRow}");
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

            $macros = $this->calculateMacros($food, $quantityGrams);
            $this->writeFoodRow($food->name, $quantityGrams, $grossGrams, $macros);
            $this->addToSum($mealSum, $quantityGrams, $grossGrams, $macros);
        }
    }

    /**
     * @param  array<string, float>  $mealSum
     */
    private function writeLooseFoodSection(MealPlanItem $item, array &$mealSum): void
    {
        $food = $item->food;
        $quantityGrams = (float) $item->quantity;
        $performance = (float) ($food->performance ?? 100);
        $grossGrams = $performance > 0 ? ($quantityGrams / ($performance / 100)) : $quantityGrams;

        $macros = $this->calculateMacros($food, $quantityGrams);
        $this->writeFoodRow($food->name, $quantityGrams, $grossGrams, $macros);
        $this->addToSum($mealSum, $quantityGrams, $grossGrams, $macros);
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
            'prot_g' => $protG,
            'prot_kcal' => $protKcal,
            'fat_g' => $fatG,
            'fat_kcal' => $fatKcal,
            'carb_g' => $carbG,
            'carb_kcal' => $carbKcal,
            'total_kcal' => $protKcal + $fatKcal + $carbKcal,
        ];
    }

    /**
     * @param  array{prot_g: float, prot_kcal: float, fat_g: float, fat_kcal: float, carb_g: float, carb_kcal: float, total_kcal: float}  $macros
     */
    private function writeFoodRow(string $name, float $netGrams, float $grossGrams, array $macros): void
    {
        $this->setCellValue("A{$this->currentRow}", $name);
        $this->setCellValue("B{$this->currentRow}", number_format($netGrams, 2));
        $this->setCellValue("C{$this->currentRow}", number_format($grossGrams, 2));
        $this->setCellValue("D{$this->currentRow}", number_format($macros['prot_g'], 2));
        $this->setCellValue("E{$this->currentRow}", number_format($macros['prot_kcal'], 2));
        $this->setCellValue("F{$this->currentRow}", number_format($macros['fat_g'], 2));
        $this->setCellValue("G{$this->currentRow}", number_format($macros['fat_kcal'], 2));
        $this->setCellValue("H{$this->currentRow}", number_format($macros['carb_g'], 2));
        $this->setCellValue("I{$this->currentRow}", number_format($macros['carb_kcal'], 2));
        $this->setCellValue("J{$this->currentRow}", number_format($macros['total_kcal'], 2));

        $this->applyStyle("B{$this->currentRow}:J{$this->currentRow}", [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        $this->applyStyle("A{$this->currentRow}:J{$this->currentRow}", [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFD9D9D9']]],
        ]);

        $this->currentRow++;
    }

    /**
     * @param  array<string, float>  $sum
     * @param  array{prot_g: float, prot_kcal: float, fat_g: float, fat_kcal: float, carb_g: float, carb_kcal: float, total_kcal: float}  $macros
     */
    private function addToSum(array &$sum, float $net, float $gross, array $macros): void
    {
        $sum['net'] += $net;
        $sum['gross'] += $gross;
        $sum['prot_g'] += $macros['prot_g'];
        $sum['prot_kcal'] += $macros['prot_kcal'];
        $sum['fat_g'] += $macros['fat_g'];
        $sum['fat_kcal'] += $macros['fat_kcal'];
        $sum['carb_g'] += $macros['carb_g'];
        $sum['carb_kcal'] += $macros['carb_kcal'];
        $sum['total_kcal'] += $macros['total_kcal'];
    }

    /**
     * @param  array<string, float>  $sum
     */
    private function writeSubtotal(string $mealLabel, array $sum): void
    {
        $this->setCellValue("A{$this->currentRow}", "SUBTOTAL {$mealLabel}");
        $this->setCellValue("B{$this->currentRow}", number_format($sum['net'], 2));
        $this->setCellValue("C{$this->currentRow}", number_format($sum['gross'], 2));
        $this->setCellValue("D{$this->currentRow}", number_format($sum['prot_g'], 2));
        $this->setCellValue("E{$this->currentRow}", number_format($sum['prot_kcal'], 2));
        $this->setCellValue("F{$this->currentRow}", number_format($sum['fat_g'], 2));
        $this->setCellValue("G{$this->currentRow}", number_format($sum['fat_kcal'], 2));
        $this->setCellValue("H{$this->currentRow}", number_format($sum['carb_g'], 2));
        $this->setCellValue("I{$this->currentRow}", number_format($sum['carb_kcal'], 2));
        $this->setCellValue("J{$this->currentRow}", number_format($sum['total_kcal'], 2));

        $this->applyStyle("A{$this->currentRow}:J{$this->currentRow}", [
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD6E4F0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $this->applyStyle("A{$this->currentRow}", [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        $this->currentRow++;
    }

    private function writeVcAndDist(string $mealLabel, float $mealKcal): void
    {
        $this->sheet->mergeCells("A{$this->currentRow}:I{$this->currentRow}");
        $this->setCellValue("I{$this->currentRow}", "V.C. {$mealLabel}");
        $this->setCellValue("J{$this->currentRow}", number_format($mealKcal, 2));
        $this->applyStyle("A{$this->currentRow}:J{$this->currentRow}", [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);
        $this->currentRow++;

        $this->sheet->mergeCells("A{$this->currentRow}:I{$this->currentRow}");
        $this->setCellValue("I{$this->currentRow}", "Dist. {$mealLabel}");
        $this->setCellValue("J{$this->currentRow}", '0,0%');
        $this->applyStyle("A{$this->currentRow}:J{$this->currentRow}", [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
        ]);

        $this->currentRow++;
    }

    /**
     * @param  array<string, float>  $dayTotals
     */
    private function writeDayTotal(array $dayTotals): void
    {
        $this->setCellValue("A{$this->currentRow}", 'TOTAL GENERAL');
        $this->setCellValue("B{$this->currentRow}", number_format($dayTotals['net'], 2));
        $this->setCellValue("C{$this->currentRow}", number_format($dayTotals['gross'], 2));
        $this->setCellValue("D{$this->currentRow}", number_format($dayTotals['prot_g'], 2));
        $this->setCellValue("E{$this->currentRow}", number_format($dayTotals['prot_kcal'], 2));
        $this->setCellValue("F{$this->currentRow}", number_format($dayTotals['fat_g'], 2));
        $this->setCellValue("G{$this->currentRow}", number_format($dayTotals['fat_kcal'], 2));
        $this->setCellValue("H{$this->currentRow}", number_format($dayTotals['carb_g'], 2));
        $this->setCellValue("I{$this->currentRow}", number_format($dayTotals['carb_kcal'], 2));
        $this->setCellValue("J{$this->currentRow}", number_format($dayTotals['total_kcal'], 2));

        $this->applyStyle("A{$this->currentRow}:J{$this->currentRow}", [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1F4E79']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $this->applyStyle("A{$this->currentRow}", [
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        $this->currentRow++;
    }

    /**
     * @param  array<string, float>  $dayTotals
     * @param  array<string, array<string, float>>  $mealTotals
     */
    private function writeVctSummary(array $dayTotals, array $mealTotals): void
    {
        $totalKcal = $dayTotals['total_kcal'];

        foreach ($mealTotals as $mealTypeValue => $mealSum) {
            $mealLabel = self::MEAL_TYPE_LABELS[$mealTypeValue];
            $distPercent = $totalKcal > 0 ? ($mealSum['total_kcal'] / $totalKcal * 100) : 0;

            $this->updateDistRow($mealLabel, $distPercent);
        }

        $this->currentRow++;

        $this->sheet->mergeCells("A{$this->currentRow}:F{$this->currentRow}");
        $this->setCellValue("G{$this->currentRow}", '');
        $this->setCellValue("H{$this->currentRow}", 'PROTEÍNAS');
        $this->setCellValue("I{$this->currentRow}", 'GRASAS');
        $this->setCellValue("J{$this->currentRow}", 'CARBOHIDRATOS');
        $this->applyStyle("H{$this->currentRow}:J{$this->currentRow}", [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $this->currentRow++;

        $this->sheet->mergeCells("A{$this->currentRow}:G{$this->currentRow}");
        $this->setCellValue("G{$this->currentRow}", 'VCT (g)');
        $this->setCellValue("H{$this->currentRow}", number_format($dayTotals['prot_g'], 2));
        $this->setCellValue("I{$this->currentRow}", number_format($dayTotals['fat_g'], 2));
        $this->setCellValue("J{$this->currentRow}", number_format($dayTotals['carb_g'], 2));
        $this->applyStyle("G{$this->currentRow}", ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]]);
        $this->applyStyle("H{$this->currentRow}:J{$this->currentRow}", ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
        $this->currentRow++;

        $this->sheet->mergeCells("A{$this->currentRow}:G{$this->currentRow}");
        $this->setCellValue("G{$this->currentRow}", 'VCT (kcal)');
        $this->setCellValue("H{$this->currentRow}", number_format($dayTotals['prot_kcal'], 2));
        $this->setCellValue("I{$this->currentRow}", number_format($dayTotals['fat_kcal'], 2));
        $this->setCellValue("J{$this->currentRow}", number_format($dayTotals['carb_kcal'], 2));
        $this->applyStyle("G{$this->currentRow}", ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]]);
        $this->applyStyle("H{$this->currentRow}:J{$this->currentRow}", ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
        $this->currentRow++;

        $protPercent = $totalKcal > 0 ? ($dayTotals['prot_kcal'] / $totalKcal * 100) : 0;
        $fatPercent = $totalKcal > 0 ? ($dayTotals['fat_kcal'] / $totalKcal * 100) : 0;
        $carbPercent = $totalKcal > 0 ? ($dayTotals['carb_kcal'] / $totalKcal * 100) : 0;

        $this->sheet->mergeCells("A{$this->currentRow}:G{$this->currentRow}");
        $this->setCellValue("G{$this->currentRow}", 'VCT (%)');
        $this->setCellValue("H{$this->currentRow}", number_format($protPercent, 1).'%');
        $this->setCellValue("I{$this->currentRow}", number_format($fatPercent, 1).'%');
        $this->setCellValue("J{$this->currentRow}", number_format($carbPercent, 1).'%');
        $this->applyStyle("G{$this->currentRow}", ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]]);
        $this->applyStyle("H{$this->currentRow}:J{$this->currentRow}", ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
        $this->currentRow++;
    }

    private function updateDistRow(string $mealLabel, float $distPercent): void
    {
        $searchText = "Dist. {$mealLabel}";

        for ($row = 1; $row < $this->currentRow; $row++) {
            $cellValue = $this->sheet->getCell("I{$row}")->getValue();

            if ($cellValue === $searchText) {
                $this->setCellValue("J{$row}", number_format($distPercent, 1).'%');

                break;
            }
        }
    }

    private function writeDateHeader(Carbon $date): void
    {
        $this->sheet->mergeCells("A{$this->currentRow}:J{$this->currentRow}");
        $this->setCellValue("A{$this->currentRow}", ucfirst($date->translatedFormat('l')).' '.$date->format('d/m/Y'));
        $this->applyStyle("A{$this->currentRow}:J{$this->currentRow}", [
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
