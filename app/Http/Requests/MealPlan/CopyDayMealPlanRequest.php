<?php

namespace App\Http\Requests\MealPlan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CopyDayMealPlanRequest extends FormRequest
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
            'source_date' => ['required', 'date_format:Y-m-d'],
            'target_date' => ['required', 'date_format:Y-m-d', 'different:source_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_date.different' => 'La fecha destino debe ser diferente a la fecha origen.',
        ];
    }
}
