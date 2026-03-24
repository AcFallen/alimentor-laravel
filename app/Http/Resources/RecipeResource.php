<?php

namespace App\Http\Resources;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Recipe */
class RecipeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'preparation' => $this->preparation,
            'category' => new RecipeCategoryResource($this->whenLoaded('category')),
            'items' => RecipeItemResource::collection($this->whenLoaded('items')),
            'nutritional_summary' => $this->when(
                $this->relationLoaded('items') && $this->items->every(fn ($item) => $item->relationLoaded('food')),
                fn () => $this->getNutritionalSummary()
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
