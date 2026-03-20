<?php

namespace App\Http\Resources;

use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Food */
class FoodResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'performance' => $this->performance,
            'nutrients' => $this->nutrients,
            'is_active' => $this->is_active,
            'category' => $this->whenLoaded('category'),
            'table' => $this->whenLoaded('table'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
