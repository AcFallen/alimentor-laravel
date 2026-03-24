<?php

namespace App\Http\Controllers;

use App\Models\Food;
use App\Models\FoodCategory;
use App\Models\MealPlan;
use App\Models\MealPlanItem;
use App\Models\MealPlanSlot;
use App\Models\Recipe;
use App\Models\RecipeCategory;
use App\Models\RecipeItem;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'totals' => $this->getTotals(),
            'recipes_by_category' => $this->getRecipesByCategory(),
            'foods_by_category' => $this->getFoodsByCategory(),
            'top_foods_in_recipes' => $this->getTopFoodsInRecipes(),
            'top_recipes_in_plans' => $this->getTopRecipesInPlans(),
            'recent_meal_plans' => $this->getRecentMealPlans(),
            'meal_plans_activity' => $this->getMealPlansActivity(),
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function getTotals(): array
    {
        return [
            'users' => User::count(),
            'recipes' => Recipe::count(),
            'foods' => Food::count(),
            'meal_plans' => MealPlan::count(),
            'recipe_categories' => RecipeCategory::count(),
            'food_categories' => FoodCategory::count(),
            'recipe_items' => RecipeItem::count(),
            'meal_plan_slots' => MealPlanSlot::count(),
            'meal_plan_items' => MealPlanItem::count(),
        ];
    }

    /**
     * @return list<array{category: string, total: int}>
     */
    private function getRecipesByCategory(): array
    {
        return RecipeCategory::query()
            ->select('recipe_categories.name as category')
            ->selectRaw('COUNT(recipes.id) as total')
            ->leftJoin('recipes', function ($join) {
                $join->on('recipes.recipe_category_id', '=', 'recipe_categories.id')
                    ->whereNull('recipes.deleted_at');
            })
            ->groupBy('recipe_categories.id', 'recipe_categories.name')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    /**
     * @return list<array{category: string, total: int}>
     */
    private function getFoodsByCategory(): array
    {
        return FoodCategory::query()
            ->select('food_categories.name as category')
            ->selectRaw('COUNT(foods.id) as total')
            ->leftJoin('foods', function ($join) {
                $join->on('foods.food_category_id', '=', 'food_categories.id')
                    ->whereNull('foods.deleted_at');
            })
            ->groupBy('food_categories.id', 'food_categories.name')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    /**
     * @return list<array{name: string, usage_count: int}>
     */
    private function getTopFoodsInRecipes(): array
    {
        return Food::query()
            ->select('foods.name')
            ->selectRaw('COUNT(recipe_items.id) as usage_count')
            ->join('recipe_items', 'recipe_items.food_id', '=', 'foods.id')
            ->groupBy('foods.id', 'foods.name')
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * @return list<array{name: string, usage_count: int}>
     */
    private function getTopRecipesInPlans(): array
    {
        return Recipe::query()
            ->select('recipes.name')
            ->selectRaw('COUNT(meal_plan_items.id) as usage_count')
            ->join('meal_plan_items', 'meal_plan_items.recipe_id', '=', 'recipes.id')
            ->groupBy('recipes.id', 'recipes.name')
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * @return list<array{id: int, name: string, created_at: string, slots_count: int}>
     */
    private function getRecentMealPlans(): array
    {
        return MealPlan::query()
            ->select('id', 'name', 'created_at')
            ->withCount('slots')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * @return list<array{date: string, total_items: int}>
     */
    private function getMealPlansActivity(): array
    {
        return MealPlanSlot::query()
            ->select('date')
            ->selectRaw('COUNT(DISTINCT meal_plan_slots.id) as total_slots')
            ->selectRaw('(SELECT COUNT(*) FROM meal_plan_items WHERE meal_plan_items.meal_plan_slot_id IN (SELECT id FROM meal_plan_slots ms WHERE ms.date = meal_plan_slots.date)) as total_items')
            ->groupBy('date')
            ->orderByDesc('date')
            ->limit(14)
            ->get()
            ->toArray();
    }
}
