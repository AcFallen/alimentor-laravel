<?php

namespace App\Models;

use App\Enums\MealType;
use Database\Factories\MealPlanSlotFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'meal_plan_id',
    'date',
    'meal_type',
    'diners',
])]
class MealPlanSlot extends Model
{
    /** @use HasFactory<MealPlanSlotFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'meal_type' => MealType::class,
        ];
    }

    /**
     * @return BelongsTo<MealPlan, $this>
     */
    public function mealPlan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class);
    }

    /**
     * @return HasMany<MealPlanItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(MealPlanItem::class);
    }
}
