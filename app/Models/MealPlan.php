<?php

namespace App\Models;

use App\Enums\ActivityFactor;
use Database\Factories\MealPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'name',
    'food_table_id',
    'sex',
    'age',
    'weight',
    'height_cm',
    'formula',
    'geb',
    'get',
    'activity_factor',
    'breakfast_percentage',
    'morning_snack_percentage',
    'lunch_percentage',
    'afternoon_snack_percentage',
    'dinner_percentage',
    'protein_percentage',
    'fat_percentage',
    'carbohydrate_percentage',
])]
class MealPlan extends Model
{
    /** @use HasFactory<MealPlanFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activity_factor' => ActivityFactor::class,
        ];
    }

    /**
     * @return BelongsTo<FoodTable, $this>
     */
    public function foodTable(): BelongsTo
    {
        return $this->belongsTo(FoodTable::class);
    }

    /**
     * @return HasMany<MealPlanSlot, $this>
     */
    public function slots(): HasMany
    {
        return $this->hasMany(MealPlanSlot::class);
    }

    /**
     * @return HasManyThrough<MealPlanItem, MealPlanSlot, $this>
     */
    public function items(): HasManyThrough
    {
        return $this->hasManyThrough(MealPlanItem::class, MealPlanSlot::class);
    }
}
