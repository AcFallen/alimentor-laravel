<?php

namespace App\Http\Requests\Recipe;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRecipeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'recipe_category_id' => ['required', 'integer', 'exists:recipe_categories,id'],
            'preparation' => ['nullable', 'string'],
            'servings' => ['required', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
            'items' => ['sometimes', 'array'],
            'items.*.food_id' => ['required_with:items', 'integer', 'exists:foods,id'],
            'items.*.food_unit_id' => ['required_with:items', 'integer', 'min:1', 'exists:food_units,id'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.01'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.*.food_unit_id.min' => 'Debes seleccionar una unidad para este alimento. Agrega al menos una unidad antes de usarlo en una receta.',
            'items.*.food_unit_id.exists' => 'La unidad seleccionada no existe. Agrega una unidad válida para este alimento.',
        ];
    }
}
