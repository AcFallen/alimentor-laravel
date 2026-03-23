<?php

namespace App\Http\Requests\MealPlan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateMealPlanSlotRequest extends FormRequest
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
            'diners' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->has('diners')) {
                    return;
                }

                $slot = $this->route('meal_plan_slot');
                $assignedDiners = $slot->items()->sum('diners');

                if ($this->validated('diners') < $assignedDiners) {
                    $validator->errors()->add(
                        'diners',
                        "No puedes reducir los comensales a {$this->validated('diners')} porque ya hay {$assignedDiners} asignados en las opciones."
                    );
                }
            },
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
}
