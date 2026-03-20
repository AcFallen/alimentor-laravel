<?php

namespace App\Models;

use Database\Factories\FoodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'food_category_id', 'food_table_id', 'performance', 'nutrients', 'is_active'])]
class Food extends Model
{
    /** @use HasFactory<FoodFactory> */
    use HasFactory;

    protected $table = 'foods';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'nutrients' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<FoodCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(FoodCategory::class, 'food_category_id');
    }

    /**
     * @return BelongsTo<FoodTable, $this>
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(FoodTable::class, 'food_table_id');
    }
}
