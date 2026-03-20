<?php

namespace Database\Seeders;

use App\Models\Food;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FoodSeeder extends Seeder
{
    use WithoutModelEvents;

    /** @var array<string, int> */
    private const CATEGORY_MAP = [
        'A' => 1,
        'B' => 2,
        'C' => 3,
        'D' => 4,
        'E' => 5,
        'F' => 6,
        'G' => 7,
        'H' => 8,
        'J' => 9,
        'K' => 10,
        'L' => 11,
        'Q' => 12,
        'T' => 13,
        'U' => 14,
        'S' => 15,
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Food::query()->truncate();

        $csvPath = database_path('data/tabla_peruana_alimentos_2025.csv');
        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            $this->command->error("Could not open CSV file: {$csvPath}");

            return;
        }

        // Skip first 2 rows (header with column names and units)
        fgetcsv($handle, 0, ';');
        fgetcsv($handle, 0, ';');

        $foods = [];

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) < 24) {
                continue;
            }

            $code = trim($row[0] ?? '');

            if ($code === '' || $code === 'CÓDIGO') {
                continue;
            }

            $categoryLetter = $code[0];
            $categoryId = self::CATEGORY_MAP[$categoryLetter] ?? null;

            if ($categoryId === null) {
                continue;
            }

            $name = trim($row[1] ?? '');

            if ($name === '') {
                continue;
            }

            $nutrients = $this->buildNutrients($row);

            $foods[] = [
                'name' => $name,
                'food_category_id' => $categoryId,
                'food_table_id' => 1,
                'performance' => 100,
                'nutrients' => json_encode($nutrients),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($foods) >= 100) {
                Food::query()->insert($foods);
                $foods = [];
            }
        }

        if (count($foods) > 0) {
            Food::query()->insert($foods);
        }

        fclose($handle);

        $this->command->info('Imported '.Food::query()->count().' foods.');
    }

    /**
     * @param  array<int, string|null>  $row
     * @return array<int, array{key: string, label: string, value: float}>
     */
    private function buildNutrients(array $row): array
    {
        $columns = [
            ['key' => 'energia_kcal', 'label' => 'Energía (kcal)', 'index' => 2],
            ['key' => 'energia_kj', 'label' => 'Energía (kJ)', 'index' => 3],
            ['key' => 'agua', 'label' => 'Agua (g)', 'index' => 4],
            ['key' => 'proteinas', 'label' => 'Proteínas (g)', 'index' => 5],
            ['key' => 'grasa_total', 'label' => 'Grasa Total (g)', 'index' => 6],
            ['key' => 'carbohidratos_t', 'label' => 'Carbohidratos Totales (g)', 'index' => 7],
            ['key' => 'carbohidratos_d', 'label' => 'Carbohidratos Disponibles (g)', 'index' => 8],
            ['key' => 'fibra', 'label' => 'Fibra (g)', 'index' => 9],
            ['key' => 'cenizas', 'label' => 'Cenizas (g)', 'index' => 10],
            ['key' => 'calcio', 'label' => 'Calcio (mg)', 'index' => 11],
            ['key' => 'fosforo', 'label' => 'Fósforo (mg)', 'index' => 12],
            ['key' => 'zinc', 'label' => 'Zinc (mg)', 'index' => 13],
            ['key' => 'hierro', 'label' => 'Hierro (mg)', 'index' => 14],
            ['key' => 'sodio', 'label' => 'Sodio (mg)', 'index' => 15],
            ['key' => 'potasio', 'label' => 'Potasio (mg)', 'index' => 16],
            ['key' => 'beta_caroteno', 'label' => 'Beta Caroteno (µg)', 'index' => 17],
            ['key' => 'vitamina_a', 'label' => 'Vitamina A (µg)', 'index' => 18],
            ['key' => 'tiamina', 'label' => 'Tiamina (mg)', 'index' => 19],
            ['key' => 'riboflavina', 'label' => 'Riboflavina (mg)', 'index' => 20],
            ['key' => 'niacina', 'label' => 'Niacina (mg)', 'index' => 21],
            ['key' => 'vitamina_c', 'label' => 'Vitamina C (mg)', 'index' => 22],
            ['key' => 'acido_folico', 'label' => 'Ácido Fólico (µg)', 'index' => 23],
        ];

        return array_map(function (array $col) use ($row): array {
            return [
                'key' => $col['key'],
                'label' => $col['label'],
                'value' => $this->parseValue($row[$col['index']] ?? ''),
            ];
        }, $columns);
    }

    private function parseValue(string $value): float
    {
        $value = trim($value);

        if ($value === '' || $value === '0') {
            return 0;
        }

        return (float) str_replace(',', '.', $value);
    }
}
