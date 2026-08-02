<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Category::query()->where('active', true)->orderBy('sort_order')->orderBy('name')->paginate(50)]);
    }

    public function tree(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => Category::query()->whereNull('parent_id')->where('active', true)->with('children.children')->orderBy('sort_order')->orderBy('name')->get()]);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $category->load('children')]);
    }
}
