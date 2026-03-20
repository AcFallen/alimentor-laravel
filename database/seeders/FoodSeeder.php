<?php

namespace Database\Seeders;

use App\Models\Food;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FoodSeeder extends Seeder
{
    use WithoutModelEvents;

    /** @var array<string, int> */
    private const LETTER_CATEGORY_MAP = [
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

    /** @var array<string, int> */
    private const NAME_CATEGORY_MAP = [
        'Cereales y derivados' => 1,
        'Verduras, hortalizas y derivados' => 2,
        'Frutas y derivados' => 3,
        'Grasas, aceites y oleaginosas' => 4,
        'Pescados y mariscos' => 5,
        'Carnes y derivados' => 6,
        'Leche y derivados' => 7,
        'Bebidas (alcohólicas y analcohólicas)' => 8,
        'Huevos y derivados' => 9,
        'Productos azucarados' => 10,
        'Misceláneos' => 11,
        'Alimentos infantiles' => 12,
        'Leguminosas y derivados' => 13,
        'Tubérculos, raíces y derivados' => 14,
        'Alimentos preparados' => 15,
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Food::query()->truncate();

        $this->importPeruvianTable();
        $this->importUsaPeruTable();

        $this->command->info('Imported '.Food::query()->count().' foods total.');
    }

    private function importPeruvianTable(): void
    {
        $csvPath = database_path('data/tabla_peruana_alimentos_2025.csv');
        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            $this->command->error("Could not open CSV file: {$csvPath}");

            return;
        }

        // Skip 2 header rows (column names and units)
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

            $categoryId = self::LETTER_CATEGORY_MAP[$code[0]] ?? null;

            if ($categoryId === null) {
                continue;
            }

            $name = trim($row[1] ?? '');

            if ($name === '') {
                continue;
            }

            $foods[] = [
                'name' => $name,
                'food_category_id' => $categoryId,
                'food_table_id' => 1,
                'performance' => 100,
                'nutrients' => json_encode($this->buildPeruvianNutrients($row)),
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
    }

    private function importUsaPeruTable(): void
    {
        $csvPath = database_path('data/tabla_usa_version_peruana_2025.csv');
        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            $this->command->error("Could not open CSV file: {$csvPath}");

            return;
        }

        // Skip 1 header row
        fgetcsv($handle, 0, ';');

        $foods = [];

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) < 67) {
                continue;
            }

            $name = trim($row[0] ?? '');
            $categoryName = trim($row[1] ?? '');

            if ($name === '' || $categoryName === '') {
                continue;
            }

            $categoryId = self::NAME_CATEGORY_MAP[$categoryName] ?? null;

            if ($categoryId === null) {
                continue;
            }

            $nutrients = $this->buildUsaPeruNutrients($row);

            if (! collect($nutrients)->contains(fn (array $n): bool => $n['value'] > 0)) {
                continue;
            }

            $foods[] = [
                'name' => $name,
                'food_category_id' => $categoryId,
                'food_table_id' => 2,
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
    }

    /**
     * @param  array<int, string|null>  $row
     * @return array<int, array{key: string, label: string, value: float}>
     */
    private function buildPeruvianNutrients(array $row): array
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

        return $this->mapNutrients($columns, $row);
    }

    /**
     * @param  array<int, string|null>  $row
     * @return array<int, array{key: string, label: string, value: float}>
     */
    private function buildUsaPeruNutrients(array $row): array
    {
        $columns = [
            ['key' => 'energia_kcal', 'label' => 'Energía (kcal)', 'index' => 2],
            ['key' => 'proteinas', 'label' => 'Proteínas (g)', 'index' => 3],
            ['key' => 'carbohidratos_t', 'label' => 'Carbohidratos Totales (g)', 'index' => 4],
            ['key' => 'azucares_totales', 'label' => 'Azúcares totales (g)', 'index' => 5],
            ['key' => 'fibra', 'label' => 'Fibra (g)', 'index' => 6],
            ['key' => 'grasa_total', 'label' => 'Grasa Total (g)', 'index' => 7],
            ['key' => 'acidos_grasos_saturados', 'label' => 'Ácidos grasos saturados totales (g)', 'index' => 8],
            ['key' => 'acidos_grasos_monoinsaturados', 'label' => 'Ácidos grasos monoinsaturados totales (g)', 'index' => 9],
            ['key' => 'acidos_grasos_poliinsaturados', 'label' => 'Ácidos grasos poliinsaturados totales (g)', 'index' => 10],
            ['key' => 'colesterol', 'label' => 'Colesterol (mg)', 'index' => 11],
            ['key' => 'retinol', 'label' => 'Retinol (mcg)', 'index' => 12],
            ['key' => 'vitamina_a', 'label' => 'Vitamina A (µg)', 'index' => 13],
            ['key' => 'caroteno_alfa', 'label' => 'Caroteno alfa (mcg)', 'index' => 14],
            ['key' => 'beta_caroteno', 'label' => 'Beta Caroteno (µg)', 'index' => 15],
            ['key' => 'criptoxantina_beta', 'label' => 'Criptoxantina beta (mcg)', 'index' => 16],
            ['key' => 'licopeno', 'label' => 'Licopeno (mcg)', 'index' => 17],
            ['key' => 'luteina_zeaxantina', 'label' => 'Luteína + zeaxantina (mcg)', 'index' => 18],
            ['key' => 'tiamina', 'label' => 'Tiamina (mg)', 'index' => 19],
            ['key' => 'riboflavina', 'label' => 'Riboflavina (mg)', 'index' => 20],
            ['key' => 'niacina', 'label' => 'Niacina (mg)', 'index' => 21],
            ['key' => 'vitamina_b6', 'label' => 'Vitamina B-6 (mg)', 'index' => 22],
            ['key' => 'acido_folico', 'label' => 'Ácido Fólico (µg)', 'index' => 23],
            ['key' => 'folato_alimentos', 'label' => 'Folato, alimentos (mcg)', 'index' => 24],
            ['key' => 'folato_dfe', 'label' => 'Folato, DFE (mcg_DFE)', 'index' => 25],
            ['key' => 'folato_total', 'label' => 'Folato, total (mcg)', 'index' => 26],
            ['key' => 'colina_total', 'label' => 'Colina, total (mg)', 'index' => 27],
            ['key' => 'vitamina_b12', 'label' => 'Vitamina B-12 (mcg)', 'index' => 28],
            ['key' => 'vitamina_b12_anadida', 'label' => 'Vitamina B-12, añadida (mcg)', 'index' => 29],
            ['key' => 'vitamina_c', 'label' => 'Vitamina C (mg)', 'index' => 30],
            ['key' => 'vitamina_d', 'label' => 'Vitamina D (D2 + D3) (mcg)', 'index' => 31],
            ['key' => 'vitamina_e', 'label' => 'Vitamina E (alfa-tocoferol) (mg)', 'index' => 32],
            ['key' => 'vitamina_e_anadida', 'label' => 'Vitamina E, añadida (mg)', 'index' => 33],
            ['key' => 'vitamina_k', 'label' => 'Vitamina K (filoquinona) (mcg)', 'index' => 34],
            ['key' => 'calcio', 'label' => 'Calcio (mg)', 'index' => 35],
            ['key' => 'fosforo', 'label' => 'Fósforo (mg)', 'index' => 36],
            ['key' => 'magnesio', 'label' => 'Magnesio (mg)', 'index' => 37],
            ['key' => 'hierro', 'label' => 'Hierro (mg)', 'index' => 38],
            ['key' => 'zinc', 'label' => 'Zinc (mg)', 'index' => 39],
            ['key' => 'cobre', 'label' => 'Cobre (mg)', 'index' => 40],
            ['key' => 'selenio', 'label' => 'Selenio (mcg)', 'index' => 41],
            ['key' => 'potasio', 'label' => 'Potasio (mg)', 'index' => 42],
            ['key' => 'sodio', 'label' => 'Sodio (mg)', 'index' => 43],
            ['key' => 'cafeina', 'label' => 'Cafeína (mg)', 'index' => 44],
            ['key' => 'teobromina', 'label' => 'Teobromina (mg)', 'index' => 45],
            ['key' => 'alcohol', 'label' => 'Alcohol (g)', 'index' => 46],
            ['key' => 'acido_butirico', 'label' => 'Ácido butírico 4:0 (g)', 'index' => 47],
            ['key' => 'acido_caproico', 'label' => 'Ácido caproico 6:0 (g)', 'index' => 48],
            ['key' => 'acido_caprilico', 'label' => 'Ácido caprílico 8:0 (g)', 'index' => 49],
            ['key' => 'acido_caprico', 'label' => 'Ácido cáprico 10:0 (g)', 'index' => 50],
            ['key' => 'acido_laurico', 'label' => 'Ácido láurico 12:0 (g)', 'index' => 51],
            ['key' => 'acido_miristico', 'label' => 'Ácido mirístico 14:0 (g)', 'index' => 52],
            ['key' => 'acido_palmitico', 'label' => 'Ácido palmítico 16:0 (g)', 'index' => 53],
            ['key' => 'acido_estearico', 'label' => 'Ácido esteárico 18:0 (g)', 'index' => 54],
            ['key' => 'acido_palmitoleico', 'label' => 'Ácido palmitoleico 16:1 (g)', 'index' => 55],
            ['key' => 'acido_oleico', 'label' => 'Ácido oleico 18:1 (g)', 'index' => 56],
            ['key' => 'acido_eicosenoico', 'label' => 'Ácido eicosenoico 20:1 (g)', 'index' => 57],
            ['key' => 'acido_erucico', 'label' => 'Ácido erúcico 22:1 (g)', 'index' => 58],
            ['key' => 'acido_linoleico', 'label' => 'Ácido linoleico 18:2 (g)', 'index' => 59],
            ['key' => 'acido_linolenico', 'label' => 'Ácido linolénico 18:3 (g)', 'index' => 60],
            ['key' => 'acido_estearidónico', 'label' => 'Ácido estearidónico 18:4 (g)', 'index' => 61],
            ['key' => 'acido_araquidónico', 'label' => 'Ácido araquidónico 20:4 (g)', 'index' => 62],
            ['key' => 'acido_epa', 'label' => 'Ácido eicosapentaenoico (EPA) 20:5 n-3 (g)', 'index' => 63],
            ['key' => 'acido_dpa', 'label' => 'Ácido docosapentaenoico (DPA) 22:5 n-3 (g)', 'index' => 64],
            ['key' => 'acido_dha', 'label' => 'Ácido docosahexaenoico (DHA) 22:6 n-3 (g)', 'index' => 65],
            ['key' => 'agua', 'label' => 'Agua (g)', 'index' => 66],
        ];

        return $this->mapNutrients($columns, $row);
    }

    /**
     * @param  array<int, array{key: string, label: string, index: int}>  $columns
     * @param  array<int, string|null>  $row
     * @return array<int, array{key: string, label: string, value: float}>
     */
    private function mapNutrients(array $columns, array $row): array
    {
        return array_map(fn (array $col): array => [
            'key' => $col['key'],
            'label' => $col['label'],
            'value' => $this->parseValue($row[$col['index']] ?? ''),
        ], $columns);
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
