<?php

namespace Database\Seeders;

use App\Models\FoodCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FoodCategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Cereales y derivados',
            'Verduras, hortalizas y derivados',
            'Frutas y derivados',
            'Grasas, aceites y oleaginosas',
            'Pescados y mariscos',
            'Carnes y derivados',
            'Leche y derivados',
            'Bebidas (alcohólicas y analcohólicas)',
            'Huevos y derivados',
            'Productos azucarados',
            'Misceláneos',
            'Alimentos infantiles',
            'Leguminosas y derivados',
            'Tubérculos, raíces y derivados',
            'Alimentos preparados',
        ];

        foreach ($categories as $name) {
            FoodCategory::query()->create(['name' => $name]);
        }
    }
}
