<?php

namespace App\Models;

use Database\Factories\FoodCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name'])]
class FoodCategory extends Model
{
    /** @use HasFactory<FoodCategoryFactory> */
    use HasFactory;
}
