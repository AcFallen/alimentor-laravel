<?php

namespace App\Models;

use Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

#[Fillable(['name', 'recipe_category_id', 'preparation'])]
class Recipe extends Model
{
    /** @use HasFactory<RecipeFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return BelongsTo<RecipeCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(RecipeCategory::class, 'recipe_category_id');
    }

    /**
     * @return HasMany<RecipeItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(RecipeItem::class);
    }

    /**
     * @return array{calories: float, proteins: float, fats: float, carbs: float, fiber: float, items_count: int, cost: float}
     */
    public function getNutritionalSummary(): array
    {
        $totals = ['calories' => 0, 'proteins' => 0, 'fats' => 0, 'carbs' => 0, 'fiber' => 0, 'cost' => 0];

        $nutrientKeys = [
            'calories' => 'energia_kcal',
            'proteins' => 'proteinas',
            'fats' => 'grasa_total',
            'carbs' => 'carbohidratos_t',
            'fiber' => 'fibra',
        ];

        foreach ($this->items as $item) {
            $grams = $item->foodUnit
                ? $item->quantity * $item->foodUnit->equivalent_in_grams
                : $item->quantity;

            $nutrients = Collection::make($item->food->nutrients ?? [])
                ->keyBy('key');

            foreach ($nutrientKeys as $total => $key) {
                $per100g = $nutrients->get($key)['value'] ?? 0;
                $totals[$total] += ($grams / 100) * $per100g;
            }

            if ($item->foodUnit) {
                $totals['cost'] += $item->quantity * ($item->foodUnit->cost ?? 0);
            }
        }

        return [
            'calories' => round($totals['calories'], 1),
            'proteins' => round($totals['proteins'], 1),
            'fats' => round($totals['fats'], 1),
            'carbs' => round($totals['carbs'], 1),
            'fiber' => round($totals['fiber'], 1),
            'items_count' => $this->items->count(),
            'cost' => round($totals['cost'], 2),
        ];
    }
}
