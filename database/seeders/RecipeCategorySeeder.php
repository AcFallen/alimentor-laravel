<?php

namespace Database\Seeders;

use App\Models\RecipeCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RecipeCategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Sopas y caldos',
            'Segundos',
            'Entradas',
            'Ensaladas',
            'Postres',
            'Bebidas',
            'Desayunos',
            'Guarniciones',
            'Snacks',
            'Salsas',
        ];

        foreach ($categories as $name) {
            RecipeCategory::query()->create(['name' => $name]);
        }
    }
}
