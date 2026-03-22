<?php

namespace App\Http\Resources;

use App\Models\MealPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MealPlan */
class MealPlanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sex' => $this->sex,
            'age' => $this->age,
            'weight' => $this->weight,
            'height_cm' => $this->height_cm,
            'formula' => $this->formula,
            'geb' => $this->geb,
            'get' => $this->get,
            'activity_factor' => $this->activity_factor,
            'breakfast_percentage' => $this->breakfast_percentage,
            'morning_snack_percentage' => $this->morning_snack_percentage,
            'lunch_percentage' => $this->lunch_percentage,
            'afternoon_snack_percentage' => $this->afternoon_snack_percentage,
            'dinner_percentage' => $this->dinner_percentage,
            'protein_percentage' => $this->protein_percentage,
            'fat_percentage' => $this->fat_percentage,
            'carbohydrate_percentage' => $this->carbohydrate_percentage,
            'food_table' => new FoodTableResource($this->whenLoaded('foodTable')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
