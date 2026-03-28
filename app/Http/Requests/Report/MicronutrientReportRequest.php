<?php

namespace App\Http\Requests\Report;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MicronutrientReportRequest extends FormRequest
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
        $validKeys = [
            'calcio', 'fosforo', 'zinc', 'hierro', 'beta_caroteno',
            'vitamina_a', 'tiamina', 'riboflavina', 'niacina',
            'vitamina_c', 'acido_folico', 'sodio', 'potasio',
        ];

        return [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'nutrient_keys' => ['required', 'array', 'min:1'],
            'nutrient_keys.*' => ['required', 'string', 'in:'.implode(',', $validKeys)],
            'format' => ['sometimes', 'in:xlsx,pdf'],
        ];
    }
}
