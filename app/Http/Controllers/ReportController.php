<?php

namespace App\Http\Controllers;

use App\Exports\StandardizedRecipeExport;
use App\Http\Requests\Report\StandardizedRecipeReportRequest;
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

        $path = $export->generate();

        return response()
            ->download($path, basename($path), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend();
    }
}
