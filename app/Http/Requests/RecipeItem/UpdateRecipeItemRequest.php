<?php

namespace App\Http\Requests\RecipeItem;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRecipeItemRequest extends FormRequest
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
            'food_id' => ['sometimes', 'integer', 'exists:foods,id'],
            'food_unit_id' => ['sometimes', 'integer', 'exists:food_units,id'],
            'quantity' => ['sometimes', 'numeric', 'min:0.01'],
        ];
    }
}
