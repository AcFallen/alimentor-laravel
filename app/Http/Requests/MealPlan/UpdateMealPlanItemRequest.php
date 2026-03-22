<?php

namespace App\Http\Requests\MealPlan;

use App\Enums\MealType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMealPlanItemRequest extends FormRequest
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
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'meal_type' => ['sometimes', 'string', Rule::enum(MealType::class)],
            'recipe_id' => ['nullable', 'integer', 'exists:recipes,id', 'missing_with:food_id'],
            'food_id' => ['nullable', 'integer', 'exists:foods,id', 'missing_with:recipe_id'],
            'food_unit_id' => ['nullable', 'integer', 'exists:food_units,id', 'required_with:food_id'],
            'quantity' => ['nullable', 'numeric', 'min:0.01', 'required_with:food_id'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'recipe_id.missing_with' => 'No puedes seleccionar una receta y un alimento al mismo tiempo.',
            'food_id.missing_with' => 'No puedes seleccionar un alimento y una receta al mismo tiempo.',
            'food_unit_id.required_with' => 'Debes seleccionar una unidad para el alimento.',
            'quantity.required_with' => 'Debes indicar la cantidad para el alimento.',
        ];
    }
}
