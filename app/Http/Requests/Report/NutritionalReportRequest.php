<?php

namespace App\Http\Requests\Report;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class NutritionalReportRequest extends FormRequest
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
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $startDate = $this->input('start_date');
            $endDate = $this->input('end_date');

            if ($startDate && $endDate && ! $validator->errors()->has('start_date') && ! $validator->errors()->has('end_date')) {
                $diff = Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate));

                if ($diff > 6) {
                    $validator->errors()->add('end_date', 'El rango de fechas no debe ser mayor a 7 días.');
                }
            }
        });
    }
}
