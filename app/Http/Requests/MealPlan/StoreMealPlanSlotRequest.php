<?php

namespace App\Http\Requests\MealPlan;

use App\Enums\MealType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMealPlanSlotRequest extends FormRequest
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
            'date' => ['required', 'date_format:Y-m-d'],
            'meal_type' => ['required', 'string', Rule::enum(MealType::class)],
            'diners' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'diners.min' => 'Debe haber al menos 1 comensal.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $mealPlan = $this->route('meal_plan');
                $exists = $mealPlan->slots()
                    ->where('date', $this->validated('date'))
                    ->where('meal_type', $this->validated('meal_type'))
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('meal_type', 'Ya existe un turno para esta fecha y tiempo de comida.');
                }
            },
        ];
    }
}
