<?php

namespace App\Http\Requests\Recipe;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRecipeRequest extends FormRequest
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
            'recipe_category_id' => ['sometimes', 'integer', 'exists:recipe_categories,id'],
            'preparation' => ['nullable', 'string'],
            'items' => ['sometimes', 'array'],
            'items.*.food_id' => ['required_with:items', 'integer', 'exists:foods,id'],
            'items.*.food_unit_id' => ['required_with:items', 'integer', 'exists:food_units,id'],
            'items.*.quantity' => ['required_with:items', 'numeric', 'min:0.01'],
        ];
    }
}
