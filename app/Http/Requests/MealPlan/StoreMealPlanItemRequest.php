<?php

namespace App\Http\Requests\MealPlan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMealPlanItemRequest extends FormRequest
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
            'option_group' => ['nullable', 'string', 'max:50'],
            'recipe_id' => ['nullable', 'integer', 'exists:recipes,id', 'required_without:food_id', 'missing_with:food_id'],
            'food_id' => ['nullable', 'integer', 'exists:foods,id', 'required_without:recipe_id', 'missing_with:recipe_id'],
            'food_unit_id' => ['nullable', 'integer', 'exists:food_units,id', 'required_with:food_id'],
            'quantity' => ['nullable', 'numeric', 'min:0.01', 'required_with:food_id'],
            'diners' => ['sometimes', 'integer', 'min:1'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $optionGroup = $this->input('option_group');

                if ($optionGroup === null) {
                    return;
                }

                $slot = $this->route('meal_plan_slot');
                $requestedDiners = $this->integer('diners', 1);

                $existingSum = $slot->items()
                    ->where('option_group', $optionGroup)
                    ->sum('diners');

                if (($existingSum + $requestedDiners) > $slot->diners) {
                    $validator->errors()->add(
                        'diners',
                        "La suma de comensales del grupo '{$optionGroup}' ({$existingSum} + {$requestedDiners}) excede el total del turno ({$slot->diners})."
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
            'recipe_id.required_without' => 'Debes seleccionar una receta o un alimento.',
            'food_id.required_without' => 'Debes seleccionar un alimento o una receta.',
            'recipe_id.missing_with' => 'No puedes seleccionar una receta y un alimento al mismo tiempo.',
            'food_id.missing_with' => 'No puedes seleccionar un alimento y una receta al mismo tiempo.',
            'food_unit_id.required_with' => 'Debes seleccionar una unidad para el alimento.',
            'quantity.required_with' => 'Debes indicar la cantidad para el alimento.',
        ];
    }
}
