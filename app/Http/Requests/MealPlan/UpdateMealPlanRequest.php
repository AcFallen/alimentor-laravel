<?php

namespace App\Http\Requests\MealPlan;

use App\Enums\ActivityFactor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMealPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'food_table_id' => ['sometimes', 'integer', 'exists:food_tables,id'],
            'sex' => ['sometimes', 'string', Rule::in(['M', 'F'])],
            'age' => ['sometimes', 'integer', 'min:0', 'max:150'],
            'weight' => ['sometimes', 'numeric', 'min:0.1'],
            'height_cm' => ['sometimes', 'numeric', 'min:1'],
            'formula' => ['nullable', 'string', 'max:255'],
            'geb' => ['nullable', 'numeric', 'min:0'],
            'get' => ['nullable', 'numeric', 'min:0'],
            'activity_factor' => ['nullable', 'string', Rule::enum(ActivityFactor::class)],
            'breakfast_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'morning_snack_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lunch_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'afternoon_snack_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'dinner_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'protein_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fat_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'carbohydrate_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
