<?php

namespace App\Http\Requests\RecipeItem;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRecipeItemRequest extends FormRequest
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
            'food_id' => ['required', 'integer', 'exists:foods,id'],
            'food_unit_id' => ['required', 'integer', 'exists:food_units,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
