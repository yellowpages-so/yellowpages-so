<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DirectoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DirectoryServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = DirectoryService::query()->where('active', true)->with('category');
        if ($request->filled('category')) {
            $q->whereHas('category', fn ($x) => $x->where('slug', $request->string('category')->toString()));
        }

        return response()->json(['success' => true, 'data' => $q->orderBy('name')->paginate(50)]);
    }
}
