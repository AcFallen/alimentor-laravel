<?php

namespace App\Http\Resources;

use App\Models\RecipeItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RecipeItem */
class RecipeItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'food' => new FoodResource($this->whenLoaded('food')),
            'food_unit' => new FoodUnitResource($this->whenLoaded('foodUnit')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
