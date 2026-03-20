<?php

namespace Database\Seeders;

use App\Models\FoodTable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FoodTableSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tables = [
            'Peruana 2025',
            'EU 2025',
        ];

        foreach ($tables as $name) {
            FoodTable::query()->create(['name' => $name]);
        }
    }
}
