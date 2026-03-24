<?php

namespace App\Http\Controllers;

use App\Exports\KitchenOrderExport;
use App\Exports\MacronutrientReportExport;
use App\Exports\NutritionalReportExport;
use App\Exports\StandardizedRecipeExport;
use App\Exports\WeeklyRequirementExport;
use App\Http\Requests\Report\KitchenOrderReportRequest;
use App\Http\Requests\Report\MacronutrientReportRequest;
use App\Http\Requests\Report\NutritionalReportRequest;
use App\Http\Requests\Report\StandardizedRecipeReportRequest;
use App\Http\Requests\Report\WeeklyRequirementReportRequest;
use App\Models\MealPlan;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function standardizedRecipe(StandardizedRecipeReportRequest $request, MealPlan $mealPlan): BinaryFileResponse
    {
        $export = new StandardizedRecipeExport(
            mealPlan: $mealPlan,
            startDate: $request->validated('start_date'),
            endDate: $request->validated('end_date'),
        );

        return $this->downloadExport($export->generate());
    }

    public function weeklyRequirement(WeeklyRequirementReportRequest $request, MealPlan $mealPlan): BinaryFileResponse
    {
        $export = new WeeklyRequirementExport(
            mealPlan: $mealPlan,
            startDate: $request->validated('start_date'),
            endDate: $request->validated('end_date'),
        );

        return $this->downloadExport($export->generate());
    }

    public function kitchenOrder(KitchenOrderReportRequest $request, MealPlan $mealPlan): BinaryFileResponse
    {
        $export = new KitchenOrderExport(
            mealPlan: $mealPlan,
            startDate: $request->validated('start_date'),
            endDate: $request->validated('end_date'),
        );

        return $this->downloadExport($export->generate());
    }

    public function nutritional(NutritionalReportRequest $request, MealPlan $mealPlan): BinaryFileResponse
    {
        $export = new NutritionalReportExport(
            mealPlan: $mealPlan,
            startDate: $request->validated('start_date'),
            endDate: $request->validated('end_date'),
        );

        return $this->downloadExport($export->generate());
    }

    public function macronutrient(MacronutrientReportRequest $request, MealPlan $mealPlan): BinaryFileResponse
    {
        $export = new MacronutrientReportExport(
            mealPlan: $mealPlan,
            startDate: $request->validated('start_date'),
            endDate: $request->validated('end_date'),
        );

        return $this->downloadExport($export->generate());
    }

    private function downloadExport(string $path): BinaryFileResponse
    {
        return response()
            ->download($path, basename($path), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend();
    }
}
