<?php

namespace App\Models;

use Database\Factories\FoodTableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name'])]
class FoodTable extends Model
{
    /** @use HasFactory<FoodTableFactory> */
    use HasFactory;
}
