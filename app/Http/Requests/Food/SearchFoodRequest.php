<?php

namespace App\Http\Requests\Food;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchFoodRequest extends FormRequest
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
            'food_table_id' => ['required', 'integer', 'exists:food_tables,id'],
            'search' => ['required', 'string', 'min:2', 'max:255'],
        ];
    }
}
