<?php

namespace App\Models;

use Database\Factories\RecipeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
}
