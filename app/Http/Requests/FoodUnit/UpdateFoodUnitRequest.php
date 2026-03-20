<?php

namespace App\Http\Requests\FoodUnit;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFoodUnitRequest extends FormRequest
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
            'equivalent_in_grams' => ['sometimes', 'numeric', 'min:0.01'],
            'cost' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
