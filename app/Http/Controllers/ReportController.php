<?php

namespace App\Http\Controllers;

use App\Exports\KitchenOrderExport;
use App\Exports\KitchenOrderPdfExport;
use App\Exports\MacronutrientReportExport;
use App\Exports\MicronutrientReportExport;
use App\Exports\NutritionalReportExport;
use App\Exports\StandardizedRecipeExport;
use App\Exports\StandardizedRecipePdfExport;
use App\Exports\WeeklyDetailedPlanExport;
use App\Exports\WeeklyRequirementExport;
use App\Exports\WeeklyRequirementPdfExport;
use App\Http\Requests\Report\KitchenOrderReportRequest;
use App\Http\Requests\Report\MacronutrientReportRequest;
use App\Http\Requests\Report\MicronutrientReportRequest;
use App\Http\Requests\Report\NutritionalReportRequest;
use App\Http\Requests\Report\StandardizedRecipeReportRequest;
use App\Http\Requests\Report\WeeklyDetailedPlanReportRequest;
use App\Http\Requests\Report\WeeklyRequirementReportRequest;
use App\Models\MealPlan;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function standardizedRecipe(StandardizedRecipeReportRequest $request, MealPlan $mealPlan): BinaryFileResponse
    {
        $format = $request->validated('format', 'xlsx');

        if ($format === 'pdf') {
            $export = new StandardizedRecipePdfExport(
                mealPlan: $mealPlan,
                startDate: $request->validated('start_date'),
                endDate: $request->validated('end_date'),
            );

            return $this->downloadPdf($export->generate());
        }

        $export = new StandardizedRecipeExport(
            mealPlan: $mealPlan,
            startDate: $request->validated('start_date'),
            endDate: $request->validated('end_date'),
        );

        return $this->downloadExport($export->generate());
    }

    public function weeklyRequirement(WeeklyRequirementReportRequest $request, MealPlan $mealPlan): BinaryFileResponse
    {
        $format = $request->validated('format', 'xlsx');

        if ($format === 'pdf') {
            $export = new WeeklyRequirementPdfExport(
                mealPlan: $mealPlan,
                startDate: $request->validated('start_date'),
                endDate: $request->validated('end_date'),
            );

            return $this->downloadPdf($export->generate());
        }

        $export = new WeeklyRequirementExport(
            mealPlan: $mealPlan,
            startDate: $request->validated('start_date'),
            endDate: $request->validated('end_date'),
        );

        return $this->downloadExport($export->generate());
    }

    public function kitchenOrder(KitchenOrderReportRequest $request, MealPlan $mealPlan): BinaryFileResponse
    {
        $format = $request->validated('format', 'xlsx');

        if ($format === 'pdf') {
            $export = new KitchenOrderPdfExport(
                mealPlan: $mealPlan,
                startDate: $request->validated('start_date'),
                endDate: $request->validated('end_date'),
            );

            return $this->downloadPdf($export->generate());
        }

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

    public function micronutrient(MicronutrientReportRequest $request, MealPlan $mealPlan): BinaryFileResponse
    {
        $export = new MicronutrientReportExport(
            mealPlan: $mealPlan,
            startDate: $request->validated('start_date'),
            endDate: $request->validated('end_date'),
            nutrientKeys: $request->validated('nutrient_keys'),
        );

        return $this->downloadExport($export->generate());
    }

    public function weeklyDetailedPlan(WeeklyDetailedPlanReportRequest $request, MealPlan $mealPlan): BinaryFileResponse
    {
        $export = new WeeklyDetailedPlanExport(
            mealPlan: $mealPlan,
            startDate: $request->validated('start_date'),
            endDate: $request->validated('end_date'),
            objective: $request->validated('objective', ''),
            userName: $request->validated('user_name', ''),
            nutritionist: $request->validated('nutritionist', ''),
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

    private function downloadPdf(string $path): BinaryFileResponse
    {
        return response()
            ->download($path, basename($path), [
                'Content-Type' => 'application/pdf',
            ])
            ->deleteFileAfterSend();
    }
}
