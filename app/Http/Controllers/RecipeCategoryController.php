<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecipeCategory\StoreRecipeCategoryRequest;
use App\Http\Requests\RecipeCategory\UpdateRecipeCategoryRequest;
use App\Http\Resources\RecipeCategoryResource;
use App\Models\RecipeCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RecipeCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return RecipeCategoryResource::collection(RecipeCategory::all());
    }

    public function store(StoreRecipeCategoryRequest $request): JsonResponse
    {
        $category = RecipeCategory::query()->create($request->validated());

        return response()->json(new RecipeCategoryResource($category), 201);
    }

    public function show(RecipeCategory $recipeCategory): RecipeCategoryResource
    {
        return new RecipeCategoryResource($recipeCategory);
    }

    public function update(UpdateRecipeCategoryRequest $request, RecipeCategory $recipeCategory): RecipeCategoryResource
    {
        $recipeCategory->update($request->validated());

        return new RecipeCategoryResource($recipeCategory);
    }

    public function destroy(RecipeCategory $recipeCategory): JsonResponse
    {
        $recipeCategory->delete();

        return response()->json(null, 204);
    }
}
