<?php

namespace App\Http\Requests\MealPlan;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateMealPlanItemRequest extends FormRequest
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
            'recipe_id' => ['nullable', 'integer', 'exists:recipes,id', 'missing_with:food_id'],
            'food_id' => ['nullable', 'integer', 'exists:foods,id', 'missing_with:recipe_id'],
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
                $item = $this->route('meal_plan_item');
                $optionGroup = $this->input('option_group', $item->option_group);

                if ($optionGroup === null) {
                    return;
                }

                if (! $this->has('diners') && ! $this->has('option_group')) {
                    return;
                }

                $slot = $item->slot;
                $requestedDiners = $this->integer('diners', $item->diners);

                $existingSum = $slot->items()
                    ->where('id', '!=', $item->id)
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
            'recipe_id.missing_with' => 'No puedes seleccionar una receta y un alimento al mismo tiempo.',
            'food_id.missing_with' => 'No puedes seleccionar un alimento y una receta al mismo tiempo.',
            'food_unit_id.required_with' => 'Debes seleccionar una unidad para el alimento.',
            'quantity.required_with' => 'Debes indicar la cantidad para el alimento.',
        ];
    }
}
