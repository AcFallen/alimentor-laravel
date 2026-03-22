<?php

namespace App\Http\Resources;

use App\Models\MealPlanItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MealPlanItem */
class MealPlanItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date->toDateString(),
            'meal_type' => $this->meal_type,
            'quantity' => $this->quantity,
            'sort_order' => $this->sort_order,
            'recipe' => new RecipeResource($this->whenLoaded('recipe')),
            'food' => new FoodResource($this->whenLoaded('food')),
            'food_unit' => new FoodUnitResource($this->whenLoaded('foodUnit')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
