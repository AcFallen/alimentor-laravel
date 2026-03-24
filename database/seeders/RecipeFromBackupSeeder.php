<?php

namespace Database\Seeders;

use App\Models\Food;
use App\Models\Recipe;
use App\Models\RecipeCategory;
use Illuminate\Database\Seeder;

class RecipeFromBackupSeeder extends Seeder
{
    /** @var array<int, string> */
    private const array CATEGORY_MAP = [
        1 => 'Desayunos',
        4 => 'Postres',
        5 => 'Bebidas',
        6 => 'Entradas',
        7 => 'Sopas y caldos',
        8 => 'Ensaladas',
        9 => 'Segundos',
        10 => 'Guarniciones',
    ];

    public function run(): void
    {
        $categoryIdMap = $this->buildCategoryIdMap();
        $foodNameMap = $this->buildFoodNameMap();

        $recipes = $this->getRecipes();
        $ingredients = $this->getIngredients();

        $ingredientsByRecipe = [];
        foreach ($ingredients as $ingredient) {
            $ingredientsByRecipe[$ingredient['recipe_id']][] = $ingredient;
        }

        $created = 0;
        $skippedIngredients = 0;

        foreach ($recipes as $recipeData) {
            $categoryId = $categoryIdMap[$recipeData['category_id']] ?? null;

            if (! $categoryId) {
                $this->command->warn("Categoría no mapeada para receta: {$recipeData['name']} (categoryId: {$recipeData['category_id']})");

                continue;
            }

            $recipe = Recipe::create([
                'name' => $recipeData['name'],
                'recipe_category_id' => $categoryId,
                'preparation' => $recipeData['preparation'],
            ]);

            $recipeIngredients = $ingredientsByRecipe[$recipeData['id']] ?? [];

            foreach ($recipeIngredients as $ingredient) {
                $foodId = $foodNameMap[$ingredient['food_name']] ?? null;

                if (! $foodId) {
                    $skippedIngredients++;
                    $this->command->warn("  Alimento no encontrado: {$ingredient['food_name']} (receta: {$recipeData['name']})");

                    continue;
                }

                $recipe->items()->create([
                    'food_id' => $foodId,
                    'food_unit_id' => null,
                    'quantity' => $ingredient['quantity'],
                ]);
            }

            $created++;
        }

        $this->command->info("Recetas creadas: {$created}");

        if ($skippedIngredients > 0) {
            $this->command->warn("Ingredientes omitidos por no encontrar alimento: {$skippedIngredients}");
        }
    }

    /**
     * @return array<int, int>
     */
    private function buildCategoryIdMap(): array
    {
        $map = [];

        foreach (self::CATEGORY_MAP as $pgCategoryId => $mysqlCategoryName) {
            $category = RecipeCategory::where('name', $mysqlCategoryName)->first();

            if ($category) {
                $map[$pgCategoryId] = $category->id;
            }
        }

        return $map;
    }

    /**
     * @return array<string, int>
     */
    private function buildFoodNameMap(): array
    {
        $map = [];

        foreach (Food::all(['id', 'name']) as $food) {
            $map[$food->name] = $food->id;

            $cleaned = rtrim($food->name, '*');
            if ($cleaned !== $food->name) {
                $map[$cleaned] = $food->id;
            }

            $withoutQuotes = str_replace('"', '', $food->name);
            if ($withoutQuotes !== $food->name) {
                $map[$withoutQuotes] = $food->id;
            }

            $cleanedWithoutQuotes = str_replace('"', '', $cleaned);
            if ($cleanedWithoutQuotes !== $food->name) {
                $map[$cleanedWithoutQuotes] = $food->id;
            }
        }

        $aliases = [
            'Plátano Bellaco' => 'Plátano "bellaco"',
        ];

        foreach ($aliases as $alias => $realName) {
            if (isset($map[$realName])) {
                $map[$alias] = $map[$realName];
            }
        }

        return $map;
    }

    /**
     * @return list<array{id: int, name: string, category_id: int, preparation: string}>
     */
    private function getRecipes(): array
    {
        return [
            ['id' => 1, 'name' => 'Arroz graneado', 'category_id' => 10, 'preparation' => ''],
            ['id' => 2, 'name' => 'Lentejas guisadas con res', 'category_id' => 9, 'preparation' => ''],
            ['id' => 3, 'name' => 'Estofado de res', 'category_id' => 9, 'preparation' => ''],
            ['id' => 4, 'name' => 'Seco de res', 'category_id' => 9, 'preparation' => ''],
            ['id' => 5, 'name' => 'Aji de pollo', 'category_id' => 9, 'preparation' => ''],
            ['id' => 6, 'name' => 'Tallarines rojos con pollo', 'category_id' => 9, 'preparation' => ''],
            ['id' => 7, 'name' => 'Locro de pecho', 'category_id' => 9, 'preparation' => ''],
            ['id' => 8, 'name' => 'Olluco con charqui', 'category_id' => 9, 'preparation' => '<p>En sartén a fuego medio (90 °C–100 °C) sofríe cebolla, ajo y ají 4 minutos; agrega el charqui deshilachado y mezcla 2 minutos más; añade olluco en tiras y caldo, cocina tapado 12–15 minutos a 95 °C, hasta que ablande; rectifica sazón, perfuma con culantro y sirve caliente junto a arroz o papa.</p>'],
            ['id' => 9, 'name' => 'Bistec a lo pobre', 'category_id' => 9, 'preparation' => '<p>El Bistec a lo Pobre se prepara marinando los filetes de res con sal, pimienta y ajo por 20 minutos. Las papas se fríen en aceite a 170–175 °C por 8–10 min hasta dorar; los plátanos, a 160 °C por 4–5 min por lado; y los huevos, a 150–160 °C por 2–3 min, manteniendo la yema semi líquida. Finalmente, los bistecs se sellan en plancha caliente a 180–200 °C por 2–3 min por lado. Se sirve con arroz blanco, colocando el huevo sobre la carne y acompañando con papas y plátano frito.</p>'],
            ['id' => 10, 'name' => 'Lomo saltado', 'category_id' => 9, 'preparation' => '<p>Las papas se cortan en bastones y se fríen en aceite a 170–175 °C por 8–10 minutos hasta dorar. En un wok muy caliente a 200–220 °C, con 20 ml de aceite, se saltea el lomo de res en tiras durante 2–3 minutos para sellar sin recocer. Se añade ajo, cebolla, ají amarillo y se saltea 1 minuto; luego se incorpora el tomate, la salsa de soya y el vinagre, cocinando 1 minuto más a fuego fuerte para mantener la textura crocante de las verduras. Se retira del fuego, se mezcla con las papas fritas y se espolvorea cilantro picado. Se sirve de inmediato acompañado con 150 g de arroz blanco caliente.</p>'],
            ['id' => 11, 'name' => 'Guiso de Frijoles de cerdo', 'category_id' => 9, 'preparation' => ''],
            ['id' => 12, 'name' => 'Carne Asada', 'category_id' => 9, 'preparation' => ''],
            ['id' => 13, 'name' => 'Avena con Leche', 'category_id' => 1, 'preparation' => ''],
            ['id' => 14, 'name' => 'Avena con Platano', 'category_id' => 1, 'preparation' => ''],
            ['id' => 15, 'name' => 'Quinua con Manzana', 'category_id' => 1, 'preparation' => ''],
            ['id' => 16, 'name' => 'Quinua con Leche', 'category_id' => 1, 'preparation' => ''],
            ['id' => 17, 'name' => 'Polenta con Leche', 'category_id' => 1, 'preparation' => ''],
            ['id' => 18, 'name' => 'Pan con Queso', 'category_id' => 10, 'preparation' => ''],
            ['id' => 19, 'name' => 'Pan con Mantequilla', 'category_id' => 10, 'preparation' => ''],
            ['id' => 20, 'name' => 'Pan con Mermelada', 'category_id' => 10, 'preparation' => ''],
            ['id' => 21, 'name' => 'Ensalada Criolla', 'category_id' => 8, 'preparation' => ''],
            ['id' => 22, 'name' => 'Ensalada Rusa', 'category_id' => 8, 'preparation' => ''],
            ['id' => 23, 'name' => 'Ensalada de Apio', 'category_id' => 8, 'preparation' => ''],
            ['id' => 24, 'name' => 'Ensalada de Rabanito', 'category_id' => 8, 'preparation' => ''],
            ['id' => 25, 'name' => 'Ensalada de Pepinillo', 'category_id' => 8, 'preparation' => ''],
            ['id' => 26, 'name' => 'Ensalada de Lechuga con Tomate', 'category_id' => 8, 'preparation' => ''],
            ['id' => 27, 'name' => 'Sopa de Pollo', 'category_id' => 7, 'preparation' => ''],
            ['id' => 28, 'name' => 'Menestrón', 'category_id' => 7, 'preparation' => ''],
            ['id' => 29, 'name' => 'Sopa Carnonada', 'category_id' => 7, 'preparation' => ''],
            ['id' => 30, 'name' => 'Sopa de Quinua', 'category_id' => 7, 'preparation' => ''],
            ['id' => 31, 'name' => 'Aguadito de Pollo', 'category_id' => 7, 'preparation' => ''],
            ['id' => 32, 'name' => 'Caldo de Mote', 'category_id' => 7, 'preparation' => ''],
            ['id' => 33, 'name' => 'Caldo Blanco', 'category_id' => 7, 'preparation' => ''],
            ['id' => 34, 'name' => 'Mazamorra Morada', 'category_id' => 4, 'preparation' => ''],
            ['id' => 35, 'name' => 'Arroz con Leche', 'category_id' => 4, 'preparation' => ''],
            ['id' => 36, 'name' => 'Puré de Papa', 'category_id' => 10, 'preparation' => ''],
            ['id' => 38, 'name' => 'Ensalada Mediterranea Clasica', 'category_id' => 8, 'preparation' => ''],
            ['id' => 39, 'name' => 'Ensalada de Pollo con Quinua', 'category_id' => 8, 'preparation' => ''],
            ['id' => 40, 'name' => 'Ensalada de Garbanzos', 'category_id' => 8, 'preparation' => ''],
            ['id' => 41, 'name' => 'Ensalada Verde con Atún', 'category_id' => 8, 'preparation' => ''],
            ['id' => 42, 'name' => 'Ensalada Tropical con Pollo y Frutas', 'category_id' => 8, 'preparation' => ''],
            ['id' => 43, 'name' => 'Guiso de Lentejas y Verduras', 'category_id' => 9, 'preparation' => ''],
            ['id' => 44, 'name' => 'Guiso de Quinua con Pollo y Verduras', 'category_id' => 9, 'preparation' => ''],
            ['id' => 45, 'name' => 'Arroz Integral con Verduras Salteadas', 'category_id' => 9, 'preparation' => ''],
            ['id' => 46, 'name' => 'Omelette de Verduras', 'category_id' => 10, 'preparation' => ''],
            ['id' => 47, 'name' => 'Crema de Zapallo', 'category_id' => 6, 'preparation' => ''],
            ['id' => 48, 'name' => 'Sopa de Verduras Ligera', 'category_id' => 6, 'preparation' => ''],
            ['id' => 49, 'name' => 'Rollitos de Lechuga con Pollo', 'category_id' => 6, 'preparation' => ''],
            ['id' => 50, 'name' => 'Tortilla de Espinaca y Avena', 'category_id' => 6, 'preparation' => ''],
            ['id' => 51, 'name' => 'Mousse de Yogur con Frutas', 'category_id' => 4, 'preparation' => ''],
            ['id' => 52, 'name' => 'Manzanas Asadas con Canela y Avena', 'category_id' => 4, 'preparation' => ''],
            ['id' => 53, 'name' => 'Pudín de Chia con leche y frutas', 'category_id' => 4, 'preparation' => ''],
            ['id' => 54, 'name' => 'Estofado de Pescado con Papas y verduras', 'category_id' => 9, 'preparation' => ''],
            ['id' => 55, 'name' => 'Papa Asada con Romero y Ajo', 'category_id' => 10, 'preparation' => ''],
            ['id' => 56, 'name' => 'Verduras Salteadas con Semillas', 'category_id' => 10, 'preparation' => ''],
            ['id' => 57, 'name' => 'Jugo de Manzana y Chia', 'category_id' => 5, 'preparation' => ''],
            ['id' => 58, 'name' => 'Limonada con Jengibre y Miel', 'category_id' => 5, 'preparation' => ''],
            ['id' => 59, 'name' => 'Batido de Platano y Avena', 'category_id' => 5, 'preparation' => ''],
            ['id' => 60, 'name' => 'Agua de Piña con Hierba Luisa', 'category_id' => 5, 'preparation' => ''],
            ['id' => 61, 'name' => 'Arroz graneado con ajo', 'category_id' => 10, 'preparation' => ''],
        ];
    }

    /**
     * @return list<array{recipe_id: int, quantity: float, food_name: string}>
     */
    private function getIngredients(): array
    {
        return [
            ['recipe_id' => 1, 'quantity' => 100, 'food_name' => 'Arroz blanco corriente'],
            ['recipe_id' => 1, 'quantity' => 5, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 2, 'quantity' => 60, 'food_name' => 'Lentejas chicas'],
            ['recipe_id' => 2, 'quantity' => 60, 'food_name' => 'Res, carne pulpa de'],
            ['recipe_id' => 2, 'quantity' => 50, 'food_name' => 'Papa blanca'],
            ['recipe_id' => 2, 'quantity' => 5, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 2, 'quantity' => 20, 'food_name' => 'Tomate'],
            ['recipe_id' => 2, 'quantity' => 30, 'food_name' => 'Cebolla de cabeza'],
            ['recipe_id' => 3, 'quantity' => 150, 'food_name' => 'Res, osobuco de'],
            ['recipe_id' => 3, 'quantity' => 112, 'food_name' => 'Papa blanca'],
            ['recipe_id' => 3, 'quantity' => 17, 'food_name' => 'Zanahoria'],
            ['recipe_id' => 3, 'quantity' => 13, 'food_name' => 'Arveja, fresca sin vaina'],
            ['recipe_id' => 3, 'quantity' => 30, 'food_name' => 'Cebolla de cabeza'],
            ['recipe_id' => 3, 'quantity' => 1, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 4, 'quantity' => 150, 'food_name' => 'Res, osobuco de'],
            ['recipe_id' => 4, 'quantity' => 17, 'food_name' => 'Zanahoria'],
            ['recipe_id' => 4, 'quantity' => 13, 'food_name' => 'Arveja, fresca sin vaina'],
            ['recipe_id' => 4, 'quantity' => 10, 'food_name' => 'Espinaca blanca'],
            ['recipe_id' => 4, 'quantity' => 20, 'food_name' => 'Cebolla de cabeza'],
            ['recipe_id' => 4, 'quantity' => 2, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 5, 'quantity' => 80, 'food_name' => 'Pollo, pechuga de, sin piel, sancochada sin sal'],
            ['recipe_id' => 5, 'quantity' => 80, 'food_name' => 'Papa blanca'],
            ['recipe_id' => 5, 'quantity' => 35, 'food_name' => 'Huevo de gallina entero, crudo'],
            ['recipe_id' => 5, 'quantity' => 15, 'food_name' => 'Aceituna de botija'],
            ['recipe_id' => 5, 'quantity' => 5, 'food_name' => 'Ají amarillo fresco'],
            ['recipe_id' => 5, 'quantity' => 5, 'food_name' => 'Maní tostado, sin piel'],
            ['recipe_id' => 5, 'quantity' => 10, 'food_name' => 'Queso andino'],
            ['recipe_id' => 6, 'quantity' => 100, 'food_name' => 'Fideo tallarín crudo fortificado con hierro'],
            ['recipe_id' => 6, 'quantity' => 150, 'food_name' => 'Pollo, carne pulpa'],
            ['recipe_id' => 6, 'quantity' => 50, 'food_name' => 'Cebolla de cabeza'],
            ['recipe_id' => 6, 'quantity' => 10, 'food_name' => 'Ají panca'],
            ['recipe_id' => 6, 'quantity' => 100, 'food_name' => 'Tomate'],
            ['recipe_id' => 7, 'quantity' => 200, 'food_name' => 'Papa blanca'],
            ['recipe_id' => 7, 'quantity' => 100, 'food_name' => 'Res, carne pulpa de'],
            ['recipe_id' => 7, 'quantity' => 40, 'food_name' => 'Zapallo macre'],
            ['recipe_id' => 7, 'quantity' => 30, 'food_name' => 'Cebolla de cabeza'],
            ['recipe_id' => 7, 'quantity' => 30, 'food_name' => 'Queso fresco de vaca'],
            ['recipe_id' => 8, 'quantity' => 170, 'food_name' => 'Olluco sin cáscara'],
            ['recipe_id' => 8, 'quantity' => 50, 'food_name' => 'Llama, carne seca de (charqui)'],
            ['recipe_id' => 8, 'quantity' => 35, 'food_name' => 'Cebolla de cabeza'],
            ['recipe_id' => 8, 'quantity' => 2, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 8, 'quantity' => 3, 'food_name' => 'Ají panca'],
            ['recipe_id' => 8, 'quantity' => 3, 'food_name' => 'Ají amarillo fresco'],
            ['recipe_id' => 8, 'quantity' => 25, 'food_name' => 'Tomate'],
            ['recipe_id' => 9, 'quantity' => 150, 'food_name' => 'Res, carne pulpa de'],
            ['recipe_id' => 9, 'quantity' => 60, 'food_name' => 'Huevo de gallina entero, sancochado en agua'],
            ['recipe_id' => 9, 'quantity' => 180, 'food_name' => 'Papa blanca'],
            ['recipe_id' => 9, 'quantity' => 30, 'food_name' => 'Cebolla blanca'],
            ['recipe_id' => 9, 'quantity' => 60, 'food_name' => 'Plátano Bellaco'],
            ['recipe_id' => 10, 'quantity' => 150, 'food_name' => 'Res, carne pulpa de'],
            ['recipe_id' => 10, 'quantity' => 100, 'food_name' => 'Papa blanca'],
            ['recipe_id' => 10, 'quantity' => 80, 'food_name' => 'Cebolla blanca'],
            ['recipe_id' => 10, 'quantity' => 60, 'food_name' => 'Tomate'],
            ['recipe_id' => 10, 'quantity' => 5, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 10, 'quantity' => 20, 'food_name' => 'Ají amarillo fresco'],
            ['recipe_id' => 10, 'quantity' => 20, 'food_name' => 'Vinagre'],
            ['recipe_id' => 11, 'quantity' => 100, 'food_name' => 'Frejol amarillo común'],
            ['recipe_id' => 11, 'quantity' => 5, 'food_name' => 'Cebolla blanca'],
            ['recipe_id' => 11, 'quantity' => 1, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 11, 'quantity' => 2, 'food_name' => 'Ají amarillo fresco'],
            ['recipe_id' => 11, 'quantity' => 2, 'food_name' => 'Ají panca'],
            ['recipe_id' => 11, 'quantity' => 99, 'food_name' => 'Cerdo, carne magra, cruda'],
            ['recipe_id' => 12, 'quantity' => 120, 'food_name' => 'Res, carne pulpa de'],
            ['recipe_id' => 12, 'quantity' => 2, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 12, 'quantity' => 2, 'food_name' => 'Pimienta negra'],
            ['recipe_id' => 12, 'quantity' => 2, 'food_name' => 'Cominos'],
            ['recipe_id' => 13, 'quantity' => 25, 'food_name' => 'Avena envasada'],
            ['recipe_id' => 13, 'quantity' => 22, 'food_name' => 'Leche evaporada entera'],
            ['recipe_id' => 13, 'quantity' => 20, 'food_name' => 'Azúcar granulada o refinada'],
            ['recipe_id' => 13, 'quantity' => 2, 'food_name' => 'Canela, molida'],
            ['recipe_id' => 14, 'quantity' => 100, 'food_name' => 'Avena envasada'],
            ['recipe_id' => 14, 'quantity' => 100, 'food_name' => 'Plátano de seda'],
            ['recipe_id' => 14, 'quantity' => 2, 'food_name' => 'Canela, molida'],
            ['recipe_id' => 14, 'quantity' => 15, 'food_name' => 'Azúcar granulada o refinada'],
            ['recipe_id' => 15, 'quantity' => 20, 'food_name' => 'Quinua'],
            ['recipe_id' => 15, 'quantity' => 20, 'food_name' => 'Manzana nacional'],
            ['recipe_id' => 15, 'quantity' => 13, 'food_name' => 'Azúcar granulada o refinada'],
            ['recipe_id' => 15, 'quantity' => 20, 'food_name' => 'Chancaca'],
            ['recipe_id' => 16, 'quantity' => 25, 'food_name' => 'Quinua'],
            ['recipe_id' => 16, 'quantity' => 22, 'food_name' => 'Leche evaporada entera'],
            ['recipe_id' => 16, 'quantity' => 15, 'food_name' => 'Azúcar rubia'],
            ['recipe_id' => 16, 'quantity' => 2, 'food_name' => 'Canela, molida'],
            ['recipe_id' => 17, 'quantity' => 25, 'food_name' => 'Maíz, polenta cruda de'],
            ['recipe_id' => 17, 'quantity' => 22, 'food_name' => 'Leche evaporada entera'],
            ['recipe_id' => 17, 'quantity' => 15, 'food_name' => 'Azúcar rubia'],
            ['recipe_id' => 18, 'quantity' => 60, 'food_name' => 'Pan ciabatta'],
            ['recipe_id' => 18, 'quantity' => 30, 'food_name' => 'Queso fresco de vaca'],
            ['recipe_id' => 19, 'quantity' => 60, 'food_name' => 'Pan de labranza'],
            ['recipe_id' => 19, 'quantity' => 20, 'food_name' => 'Mantequilla'],
            ['recipe_id' => 20, 'quantity' => 60, 'food_name' => 'Pan de labranza'],
            ['recipe_id' => 20, 'quantity' => 20, 'food_name' => 'Mermelada frutilla'],
            ['recipe_id' => 21, 'quantity' => 50, 'food_name' => 'Cebolla blanca'],
            ['recipe_id' => 21, 'quantity' => 40, 'food_name' => 'Tomate'],
            ['recipe_id' => 21, 'quantity' => 10, 'food_name' => 'Ají amarillo fresco'],
            ['recipe_id' => 21, 'quantity' => 10, 'food_name' => 'Limón, jugo de'],
            ['recipe_id' => 21, 'quantity' => 5, 'food_name' => 'Aceite vegetal de girasol'],
            ['recipe_id' => 21, 'quantity' => 2, 'food_name' => 'Pimienta negra'],
            ['recipe_id' => 21, 'quantity' => 3, 'food_name' => 'Culantro sin tallo'],
            ['recipe_id' => 22, 'quantity' => 80, 'food_name' => 'Papa blanca'],
            ['recipe_id' => 22, 'quantity' => 30, 'food_name' => 'Zanahoria'],
            ['recipe_id' => 22, 'quantity' => 40, 'food_name' => 'Betarraga'],
            ['recipe_id' => 22, 'quantity' => 20, 'food_name' => 'Arveja, fresca sin vaina'],
            ['recipe_id' => 22, 'quantity' => 20, 'food_name' => 'Mayonesa con sal, envasada'],
            ['recipe_id' => 22, 'quantity' => 2, 'food_name' => 'Pimienta negra'],
            ['recipe_id' => 22, 'quantity' => 2, 'food_name' => 'Perejil sin tallo'],
            ['recipe_id' => 23, 'quantity' => 60, 'food_name' => 'Apio, tallo sin hojas'],
            ['recipe_id' => 23, 'quantity' => 30, 'food_name' => 'Zanahoria'],
            ['recipe_id' => 23, 'quantity' => 40, 'food_name' => 'Manzana delicia helada con cáscara'],
            ['recipe_id' => 23, 'quantity' => 10, 'food_name' => 'Limón, jugo de'],
            ['recipe_id' => 23, 'quantity' => 5, 'food_name' => 'Aceite vegetal de girasol'],
            ['recipe_id' => 23, 'quantity' => 2, 'food_name' => 'Pimienta negra'],
            ['recipe_id' => 23, 'quantity' => 2, 'food_name' => 'Perejil sin tallo'],
            ['recipe_id' => 24, 'quantity' => 60, 'food_name' => 'Rabanitos'],
            ['recipe_id' => 24, 'quantity' => 30, 'food_name' => 'Cebolla blanca'],
            ['recipe_id' => 24, 'quantity' => 10, 'food_name' => 'Limón, jugo de'],
            ['recipe_id' => 24, 'quantity' => 5, 'food_name' => 'Aceite vegetal de girasol'],
            ['recipe_id' => 24, 'quantity' => 2, 'food_name' => 'Pimienta negra'],
            ['recipe_id' => 24, 'quantity' => 2, 'food_name' => 'Perejil sin tallo'],
            ['recipe_id' => 25, 'quantity' => 80, 'food_name' => 'Pepinillo sin cáscara'],
            ['recipe_id' => 25, 'quantity' => 30, 'food_name' => 'Cebolla blanca'],
            ['recipe_id' => 25, 'quantity' => 10, 'food_name' => 'Limón, jugo de'],
            ['recipe_id' => 25, 'quantity' => 5, 'food_name' => 'Aceite vegetal de girasol'],
            ['recipe_id' => 25, 'quantity' => 2, 'food_name' => 'Pimienta negra'],
            ['recipe_id' => 25, 'quantity' => 2, 'food_name' => 'Hierbabuena'],
            ['recipe_id' => 26, 'quantity' => 60, 'food_name' => 'Lechuga redonda'],
            ['recipe_id' => 26, 'quantity' => 50, 'food_name' => 'Tomate'],
            ['recipe_id' => 26, 'quantity' => 10, 'food_name' => 'Limón, jugo de'],
            ['recipe_id' => 26, 'quantity' => 5, 'food_name' => 'Aceite vegetal de girasol'],
            ['recipe_id' => 26, 'quantity' => 2, 'food_name' => 'Pimienta negra'],
            ['recipe_id' => 27, 'quantity' => 80, 'food_name' => 'Pollo, pecho y ala de, con piel a la brasa'],
            ['recipe_id' => 27, 'quantity' => 60, 'food_name' => 'Papa blanca'],
            ['recipe_id' => 27, 'quantity' => 30, 'food_name' => 'Fideo crudo fortificado con hierro'],
            ['recipe_id' => 27, 'quantity' => 30, 'food_name' => 'Zanahoria'],
            ['recipe_id' => 27, 'quantity' => 20, 'food_name' => 'Apio, tallo sin hojas'],
            ['recipe_id' => 27, 'quantity' => 20, 'food_name' => 'Nabo'],
            ['recipe_id' => 27, 'quantity' => 20, 'food_name' => 'Cebolla blanca'],
            ['recipe_id' => 27, 'quantity' => 2, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 27, 'quantity' => 2, 'food_name' => 'Orégano, seco'],
            ['recipe_id' => 27, 'quantity' => 2, 'food_name' => 'Hierbabuena'],
            ['recipe_id' => 27, 'quantity' => 2, 'food_name' => 'Pimienta negra'],
            ['recipe_id' => 28, 'quantity' => 60, 'food_name' => 'Res, carne pulpa de'],
            ['recipe_id' => 28, 'quantity' => 25, 'food_name' => 'Fideo crudo fortificado con hierro'],
            ['recipe_id' => 28, 'quantity' => 60, 'food_name' => 'Papa blanca'],
            ['recipe_id' => 28, 'quantity' => 40, 'food_name' => 'Yuca amarilla fresca sin cáscara'],
            ['recipe_id' => 28, 'quantity' => 30, 'food_name' => 'Zanahoria'],
            ['recipe_id' => 28, 'quantity' => 40, 'food_name' => 'Zapallo macre'],
            ['recipe_id' => 28, 'quantity' => 30, 'food_name' => 'Maíz, grano fresco (choclo)'],
            ['recipe_id' => 28, 'quantity' => 25, 'food_name' => 'Arveja, fresca sin vaina'],
            ['recipe_id' => 28, 'quantity' => 35, 'food_name' => 'Frejol amarillo común'],
            ['recipe_id' => 28, 'quantity' => 10, 'food_name' => 'Apio, tallo sin hojas'],
            ['recipe_id' => 28, 'quantity' => 10, 'food_name' => 'Poro sin hojas'],
            ['recipe_id' => 28, 'quantity' => 3, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 28, 'quantity' => 5, 'food_name' => 'Aceite compuesto (vegetal 70% pescado 30%)'],
            ['recipe_id' => 29, 'quantity' => 60, 'food_name' => 'Res, carne pulpa de'],
            ['recipe_id' => 29, 'quantity' => 60, 'food_name' => 'Papa blanca'],
            ['recipe_id' => 29, 'quantity' => 40, 'food_name' => 'Zapallo macre'],
            ['recipe_id' => 29, 'quantity' => 40, 'food_name' => 'Yuca blanca fresca sin cáscara'],
            ['recipe_id' => 29, 'quantity' => 30, 'food_name' => 'Maíz, grano fresco (choclo)'],
            ['recipe_id' => 29, 'quantity' => 25, 'food_name' => 'Arveja, fresca sin vaina'],
            ['recipe_id' => 29, 'quantity' => 30, 'food_name' => 'Zanahoria'],
            ['recipe_id' => 29, 'quantity' => 20, 'food_name' => 'Arroz blanco corriente'],
            ['recipe_id' => 29, 'quantity' => 20, 'food_name' => 'Fideo crudo fortificado con hierro'],
            ['recipe_id' => 29, 'quantity' => 3, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 29, 'quantity' => 5, 'food_name' => 'Aceite vegetal de algodón'],
            ['recipe_id' => 30, 'quantity' => 30, 'food_name' => 'Quinua'],
            ['recipe_id' => 30, 'quantity' => 60, 'food_name' => 'Papa blanca'],
            ['recipe_id' => 30, 'quantity' => 30, 'food_name' => 'Zanahoria'],
            ['recipe_id' => 30, 'quantity' => 40, 'food_name' => 'Zapallo macre'],
            ['recipe_id' => 30, 'quantity' => 25, 'food_name' => 'Habas frescas, sin cáscara y sin vaina'],
            ['recipe_id' => 30, 'quantity' => 60, 'food_name' => 'Res, carne pulpa de'],
            ['recipe_id' => 30, 'quantity' => 3, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 30, 'quantity' => 3, 'food_name' => 'Hierbabuena'],
            ['recipe_id' => 31, 'quantity' => 70, 'food_name' => 'Pollo, encuentro de, con piel, sancochado sin sal'],
            ['recipe_id' => 31, 'quantity' => 30, 'food_name' => 'Arroz blanco corriente'],
            ['recipe_id' => 31, 'quantity' => 50, 'food_name' => 'Papa blanca'],
            ['recipe_id' => 31, 'quantity' => 30, 'food_name' => 'Zanahoria'],
            ['recipe_id' => 31, 'quantity' => 25, 'food_name' => 'Arveja, fresca sin vaina'],
            ['recipe_id' => 31, 'quantity' => 30, 'food_name' => 'Maíz, grano fresco (choclo)'],
            ['recipe_id' => 31, 'quantity' => 10, 'food_name' => 'Culantro sin tallo'],
            ['recipe_id' => 31, 'quantity' => 3, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 31, 'quantity' => 5, 'food_name' => 'Aceite compuesto (vegetal 70% pescado 30%)'],
            ['recipe_id' => 32, 'quantity' => 80, 'food_name' => 'Maíz, mote de, sancochado'],
            ['recipe_id' => 32, 'quantity' => 70, 'food_name' => 'Res, carne pulpa de'],
            ['recipe_id' => 32, 'quantity' => 60, 'food_name' => 'Papa blanca'],
            ['recipe_id' => 32, 'quantity' => 30, 'food_name' => 'Zanahoria'],
            ['recipe_id' => 32, 'quantity' => 3, 'food_name' => 'Hierbabuena'],
            ['recipe_id' => 32, 'quantity' => 3, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 32, 'quantity' => 5, 'food_name' => 'Aceite compuesto (vegetal 70% pescado 30%)'],
            ['recipe_id' => 33, 'quantity' => 70, 'food_name' => 'Res, carne pulpa de'],
            ['recipe_id' => 33, 'quantity' => 80, 'food_name' => 'Papa blanca'],
            ['recipe_id' => 33, 'quantity' => 30, 'food_name' => 'Zanahoria'],
            ['recipe_id' => 33, 'quantity' => 25, 'food_name' => 'Arroz blanco corriente'],
            ['recipe_id' => 33, 'quantity' => 10, 'food_name' => 'Apio, tallo sin hojas'],
            ['recipe_id' => 33, 'quantity' => 10, 'food_name' => 'Poro sin hojas'],
            ['recipe_id' => 33, 'quantity' => 3, 'food_name' => 'Hierbabuena'],
            ['recipe_id' => 34, 'quantity' => 40, 'food_name' => 'Maíz morado, chicha de, natural, sin azúcar, sin limón'],
            ['recipe_id' => 34, 'quantity' => 30, 'food_name' => 'Piña'],
            ['recipe_id' => 34, 'quantity' => 30, 'food_name' => 'Manzana nacional'],
            ['recipe_id' => 34, 'quantity' => 30, 'food_name' => 'Membrillo'],
            ['recipe_id' => 34, 'quantity' => 15, 'food_name' => 'Guindón sin pepa'],
            ['recipe_id' => 34, 'quantity' => 25, 'food_name' => 'Azúcar granulada o refinada'],
            ['recipe_id' => 34, 'quantity' => 10, 'food_name' => 'Papa, chuño de, envasado (almidón de papa o fécula de papa)'],
            ['recipe_id' => 34, 'quantity' => 1, 'food_name' => 'Clavo de olor, molido'],
            ['recipe_id' => 35, 'quantity' => 40, 'food_name' => 'Arroz blanco corriente'],
            ['recipe_id' => 35, 'quantity' => 150, 'food_name' => 'Leche evaporada entera'],
            ['recipe_id' => 35, 'quantity' => 20, 'food_name' => 'Azúcar granulada o refinada'],
            ['recipe_id' => 35, 'quantity' => 2, 'food_name' => 'Canela, molida'],
            ['recipe_id' => 35, 'quantity' => 1, 'food_name' => 'Clavo de olor, molido'],
            ['recipe_id' => 35, 'quantity' => 10, 'food_name' => 'Pasa sin pepa'],
            ['recipe_id' => 36, 'quantity' => 120, 'food_name' => 'Papa blanca'],
            ['recipe_id' => 36, 'quantity' => 30, 'food_name' => 'Leche evaporada entera'],
            ['recipe_id' => 36, 'quantity' => 5, 'food_name' => 'Mantequilla con sal'],
            ['recipe_id' => 38, 'quantity' => 120, 'food_name' => 'Tomate'],
            ['recipe_id' => 38, 'quantity' => 80, 'food_name' => 'Pepinillo sin cáscara'],
            ['recipe_id' => 38, 'quantity' => 30, 'food_name' => 'Cebolla de cabeza'],
            ['recipe_id' => 38, 'quantity' => 20, 'food_name' => 'Aceitunas negras preparadas'],
            ['recipe_id' => 38, 'quantity' => 40, 'food_name' => 'Queso fresco de vaca'],
            ['recipe_id' => 38, 'quantity' => 10, 'food_name' => 'Aceite vegetal de oliva extravirgen'],
            ['recipe_id' => 38, 'quantity' => 10, 'food_name' => 'Limón, jugo de'],
            ['recipe_id' => 39, 'quantity' => 100, 'food_name' => 'Pollo, carne pulpa'],
            ['recipe_id' => 39, 'quantity' => 80, 'food_name' => 'Quinua cocida'],
            ['recipe_id' => 39, 'quantity' => 50, 'food_name' => 'Espinaca blanca'],
            ['recipe_id' => 39, 'quantity' => 60, 'food_name' => 'Tomate'],
            ['recipe_id' => 39, 'quantity' => 50, 'food_name' => 'Palta'],
            ['recipe_id' => 39, 'quantity' => 10, 'food_name' => 'Aceite vegetal de olivo'],
            ['recipe_id' => 39, 'quantity' => 10, 'food_name' => 'Limón, jugo de'],
            ['recipe_id' => 40, 'quantity' => 100, 'food_name' => 'Garbanzo, cocido'],
            ['recipe_id' => 40, 'quantity' => 50, 'food_name' => 'Pimiento rojo'],
            ['recipe_id' => 40, 'quantity' => 50, 'food_name' => 'Pepinillo sin cáscara'],
            ['recipe_id' => 40, 'quantity' => 30, 'food_name' => 'Cebolla de cabeza'],
            ['recipe_id' => 40, 'quantity' => 80, 'food_name' => 'Tomate'],
            ['recipe_id' => 40, 'quantity' => 5, 'food_name' => 'Perejil sin tallo'],
            ['recipe_id' => 40, 'quantity' => 10, 'food_name' => 'Aceite vegetal de olivo'],
            ['recipe_id' => 40, 'quantity' => 10, 'food_name' => 'Vinagre'],
            ['recipe_id' => 41, 'quantity' => 100, 'food_name' => 'Pescado atún, en conserva'],
            ['recipe_id' => 41, 'quantity' => 50, 'food_name' => 'Lechuga americana'],
            ['recipe_id' => 41, 'quantity' => 30, 'food_name' => 'Espinaca blanca'],
            ['recipe_id' => 41, 'quantity' => 80, 'food_name' => 'Tomate'],
            ['recipe_id' => 41, 'quantity' => 50, 'food_name' => 'Huevo de gallina entero, crudo'],
            ['recipe_id' => 41, 'quantity' => 10, 'food_name' => 'Aceite vegetal de olivo'],
            ['recipe_id' => 41, 'quantity' => 10, 'food_name' => 'Limón, jugo de'],
            ['recipe_id' => 42, 'quantity' => 100, 'food_name' => 'Pollo, carne pulpa'],
            ['recipe_id' => 42, 'quantity' => 50, 'food_name' => 'Lechuga americana'],
            ['recipe_id' => 42, 'quantity' => 50, 'food_name' => 'Mango'],
            ['recipe_id' => 42, 'quantity' => 50, 'food_name' => 'Piña'],
            ['recipe_id' => 42, 'quantity' => 50, 'food_name' => 'Palta'],
            ['recipe_id' => 42, 'quantity' => 10, 'food_name' => 'Girasol, semilla de'],
            ['recipe_id' => 42, 'quantity' => 10, 'food_name' => 'Aceite vegetal de olivo'],
            ['recipe_id' => 43, 'quantity' => 80, 'food_name' => 'Lentejas chicas'],
            ['recipe_id' => 43, 'quantity' => 50, 'food_name' => 'Zanahoria'],
            ['recipe_id' => 43, 'quantity' => 80, 'food_name' => 'Tomate'],
            ['recipe_id' => 43, 'quantity' => 40, 'food_name' => 'Cebolla blanca'],
            ['recipe_id' => 43, 'quantity' => 5, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 43, 'quantity' => 10, 'food_name' => 'Aceite vegetal de olivo'],
            ['recipe_id' => 43, 'quantity' => 2, 'food_name' => 'Cominos'],
            ['recipe_id' => 44, 'quantity' => 70, 'food_name' => 'Quinua'],
            ['recipe_id' => 44, 'quantity' => 100, 'food_name' => 'Gallina, pechuga de, sin piel'],
            ['recipe_id' => 44, 'quantity' => 50, 'food_name' => 'Zanahoria'],
            ['recipe_id' => 44, 'quantity' => 50, 'food_name' => 'Zapallito'],
            ['recipe_id' => 44, 'quantity' => 80, 'food_name' => 'Tomate'],
            ['recipe_id' => 44, 'quantity' => 40, 'food_name' => 'Cebolla blanca'],
            ['recipe_id' => 44, 'quantity' => 5, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 44, 'quantity' => 10, 'food_name' => 'Aceite vegetal de olivo'],
            ['recipe_id' => 44, 'quantity' => 2, 'food_name' => 'Orégano, seco'],
            ['recipe_id' => 45, 'quantity' => 150, 'food_name' => 'Arroz con cáscara'],
            ['recipe_id' => 45, 'quantity' => 60, 'food_name' => 'Brocoli'],
            ['recipe_id' => 45, 'quantity' => 50, 'food_name' => 'Zanahoria'],
            ['recipe_id' => 45, 'quantity' => 50, 'food_name' => 'Zapallito'],
            ['recipe_id' => 45, 'quantity' => 10, 'food_name' => 'Aceite vegetal de olivo'],
            ['recipe_id' => 45, 'quantity' => 5, 'food_name' => 'Salsa de soya'],
            ['recipe_id' => 46, 'quantity' => 100, 'food_name' => 'Huevo de gallina entero, crudo'],
            ['recipe_id' => 46, 'quantity' => 40, 'food_name' => 'Espinaca blanca'],
            ['recipe_id' => 46, 'quantity' => 50, 'food_name' => 'Tomate'],
            ['recipe_id' => 46, 'quantity' => 60, 'food_name' => 'Cebolla blanca'],
            ['recipe_id' => 46, 'quantity' => 5, 'food_name' => 'Aceite vegetal de olivo'],
            ['recipe_id' => 47, 'quantity' => 200, 'food_name' => 'Zapallo criollo'],
            ['recipe_id' => 47, 'quantity' => 30, 'food_name' => 'Cebolla blanca'],
            ['recipe_id' => 47, 'quantity' => 5, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 47, 'quantity' => 5, 'food_name' => 'Aceite vegetal de olivo'],
            ['recipe_id' => 47, 'quantity' => 50, 'food_name' => 'Leche evaporada descremada'],
            ['recipe_id' => 48, 'quantity' => 50, 'food_name' => 'Zanahoria'],
            ['recipe_id' => 48, 'quantity' => 30, 'food_name' => 'Apio, tallo sin hojas'],
            ['recipe_id' => 48, 'quantity' => 50, 'food_name' => 'Zapallito'],
            ['recipe_id' => 48, 'quantity' => 50, 'food_name' => 'Papa blanca'],
            ['recipe_id' => 48, 'quantity' => 30, 'food_name' => 'Cebolla blanca'],
            ['recipe_id' => 48, 'quantity' => 5, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 48, 'quantity' => 5, 'food_name' => 'Aceite vegetal de olivo'],
            ['recipe_id' => 49, 'quantity' => 30, 'food_name' => 'Lechuga de seda'],
            ['recipe_id' => 49, 'quantity' => 80, 'food_name' => 'Pollo, carne pulpa'],
            ['recipe_id' => 49, 'quantity' => 30, 'food_name' => 'Zanahoria'],
            ['recipe_id' => 49, 'quantity' => 30, 'food_name' => 'Palta'],
            ['recipe_id' => 49, 'quantity' => 10, 'food_name' => 'Limón, jugo de'],
            ['recipe_id' => 49, 'quantity' => 5, 'food_name' => 'Aceite vegetal de olivo'],
            ['recipe_id' => 50, 'quantity' => 100, 'food_name' => 'Huevo de gallina entero, crudo'],
            ['recipe_id' => 50, 'quantity' => 50, 'food_name' => 'Espinaca blanca'],
            ['recipe_id' => 50, 'quantity' => 20, 'food_name' => 'Avena envasada'],
            ['recipe_id' => 50, 'quantity' => 20, 'food_name' => 'Cebolla blanca'],
            ['recipe_id' => 50, 'quantity' => 5, 'food_name' => 'Aceite vegetal de olivo'],
            ['recipe_id' => 51, 'quantity' => 150, 'food_name' => 'Yogurt de leche entera'],
            ['recipe_id' => 51, 'quantity' => 50, 'food_name' => 'Plátano de isla'],
            ['recipe_id' => 51, 'quantity' => 50, 'food_name' => 'Fresa'],
            ['recipe_id' => 51, 'quantity' => 5, 'food_name' => 'Miel de abeja'],
            ['recipe_id' => 51, 'quantity' => 5, 'food_name' => 'Chía, semilla de'],
            ['recipe_id' => 52, 'quantity' => 150, 'food_name' => 'Manzana delicia helada con cáscara'],
            ['recipe_id' => 52, 'quantity' => 20, 'food_name' => 'Avena envasada'],
            ['recipe_id' => 52, 'quantity' => 2, 'food_name' => 'Canela, molida'],
            ['recipe_id' => 52, 'quantity' => 5, 'food_name' => 'Miel de abeja'],
            ['recipe_id' => 52, 'quantity' => 10, 'food_name' => 'Castaña peruana o nuez de Brasil'],
            ['recipe_id' => 53, 'quantity' => 25, 'food_name' => 'Chía, semilla de'],
            ['recipe_id' => 53, 'quantity' => 150, 'food_name' => 'Leche evaporada entera'],
            ['recipe_id' => 53, 'quantity' => 30, 'food_name' => 'Mango'],
            ['recipe_id' => 53, 'quantity' => 30, 'food_name' => 'Fresa'],
            ['recipe_id' => 53, 'quantity' => 30, 'food_name' => 'Plátano de isla'],
            ['recipe_id' => 53, 'quantity' => 100, 'food_name' => 'Miel de abeja'],
            ['recipe_id' => 54, 'quantity' => 120, 'food_name' => 'Pescado bacalao fresco'],
            ['recipe_id' => 54, 'quantity' => 100, 'food_name' => 'Papa blanca'],
            ['recipe_id' => 54, 'quantity' => 40, 'food_name' => 'Pimiento rojo'],
            ['recipe_id' => 54, 'quantity' => 40, 'food_name' => 'Cebolla blanca'],
            ['recipe_id' => 54, 'quantity' => 50, 'food_name' => 'Tomate'],
            ['recipe_id' => 54, 'quantity' => 3, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 54, 'quantity' => 5, 'food_name' => 'Aceite vegetal de olivo'],
            ['recipe_id' => 54, 'quantity' => 5, 'food_name' => 'Limón, jugo de'],
            ['recipe_id' => 55, 'quantity' => 150, 'food_name' => 'Papa blanca'],
            ['recipe_id' => 55, 'quantity' => 3, 'food_name' => 'Ajo sin cáscara'],
            ['recipe_id' => 55, 'quantity' => 2, 'food_name' => 'Romero fresco'],
            ['recipe_id' => 55, 'quantity' => 5, 'food_name' => 'Aceite vegetal de olivo'],
            ['recipe_id' => 56, 'quantity' => 60, 'food_name' => 'Brocoli'],
            ['recipe_id' => 56, 'quantity' => 50, 'food_name' => 'Zapallito'],
            ['recipe_id' => 56, 'quantity' => 40, 'food_name' => 'Pimiento rojo'],
            ['recipe_id' => 56, 'quantity' => 5, 'food_name' => 'Aceite vegetal de olivo'],
            ['recipe_id' => 56, 'quantity' => 5, 'food_name' => 'Ajonjolí, semilla de'],
            ['recipe_id' => 56, 'quantity' => 5, 'food_name' => 'Girasol, semilla de'],
            ['recipe_id' => 56, 'quantity' => 5, 'food_name' => 'Salsa de soya'],
            ['recipe_id' => 57, 'quantity' => 120, 'food_name' => 'Manzana pero con cáscara (para agua)'],
            ['recipe_id' => 57, 'quantity' => 5, 'food_name' => 'Chía, semilla de'],
            ['recipe_id' => 57, 'quantity' => 5, 'food_name' => 'Limón, jugo de'],
            ['recipe_id' => 58, 'quantity' => 20, 'food_name' => 'Limón, jugo de'],
            ['recipe_id' => 58, 'quantity' => 5, 'food_name' => 'Miel de abeja'],
            ['recipe_id' => 59, 'quantity' => 100, 'food_name' => 'Plátano de isla'],
            ['recipe_id' => 59, 'quantity' => 20, 'food_name' => 'Avena envasada'],
            ['recipe_id' => 59, 'quantity' => 200, 'food_name' => 'Leche evaporada entera'],
            ['recipe_id' => 59, 'quantity' => 1, 'food_name' => 'Canela, molida'],
            ['recipe_id' => 60, 'quantity' => 50, 'food_name' => 'Piña'],
            ['recipe_id' => 60, 'quantity' => 2, 'food_name' => 'Infusión de hierba luisa, con azúcar'],
            ['recipe_id' => 60, 'quantity' => 5, 'food_name' => 'Limón, jugo de'],
            ['recipe_id' => 60, 'quantity' => 5, 'food_name' => 'Miel de abeja'],
            ['recipe_id' => 61, 'quantity' => 5, 'food_name' => 'Ajo sin cáscara'],
        ];
    }
}
