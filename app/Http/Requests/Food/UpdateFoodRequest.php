<?php

namespace App\Http\Requests\Food;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFoodRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', Rule::unique('foods', 'name')->ignore($this->route('food'))],
            'food_category_id' => ['required', 'integer', 'exists:food_categories,id'],
            'food_table_id' => ['required', 'integer', 'exists:food_tables,id'],
            'performance' => ['required', 'numeric', 'min:0'],
            'nutrients' => ['required', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
