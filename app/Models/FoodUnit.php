<?php

namespace App\Models;

use Database\Factories\FoodUnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['food_id', 'name', 'equivalent_in_grams', 'cost'])]
class FoodUnit extends Model
{
    /** @use HasFactory<FoodUnitFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Food, $this>
     */
    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }
}
