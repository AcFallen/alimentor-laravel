<?php

namespace App\Models;

use Database\Factories\MealPlanItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'meal_plan_slot_id',
    'option_group',
    'recipe_id',
    'food_id',
    'food_unit_id',
    'quantity',
    'diners',
    'sort_order',
])]
class MealPlanItem extends Model
{
    /** @use HasFactory<MealPlanItemFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<MealPlanSlot, $this>
     */
    public function slot(): BelongsTo
    {
        return $this->belongsTo(MealPlanSlot::class, 'meal_plan_slot_id');
    }

    /**
     * @return BelongsTo<Recipe, $this>
     */
    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * @return BelongsTo<Food, $this>
     */
    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }

    /**
     * @return BelongsTo<FoodUnit, $this>
     */
    public function foodUnit(): BelongsTo
    {
        return $this->belongsTo(FoodUnit::class);
    }
}
