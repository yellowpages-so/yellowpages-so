<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CmsContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CmsContentController extends Controller
{
    public function __construct(
        private readonly CmsContentService $service
    ) {}

    public function pages(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('cms.pages')
                ->where('status', 'published')
                ->where('locale', $request->string('locale', 'en'))
                ->whereNull('deleted_at')
                ->orderByDesc('published_at')
                ->paginate(20),
        ]);
    }

    public function page(Request $request, string $slug): JsonResponse
    {
        try {
            $page = $this->service->publicPage(
                $slug,
                (string) $request->string('locale', 'en')
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $page,
        ]);
    }

    public function posts(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('cms.posts')
                ->where('status', 'published')
                ->where('locale', $request->string('locale', 'en'))
                ->whereNull('deleted_at')
                ->orderByDesc('published_at')
                ->paginate(20),
        ]);
    }

    public function post(Request $request, string $slug): JsonResponse
    {
        try {
            $post = $this->service->publicPost(
                $slug,
                (string) $request->string('locale', 'en')
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $post,
        ]);
    }

    public function banners(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('cms.banners')
                ->where('placement', $request->string('placement'))
                ->where('status', 'published')
                ->where(function ($query): void {
                    $query->whereNull('starts_at')
                        ->orWhere('starts_at', '<=', now());
                })
                ->where(function ($query): void {
                    $query->whereNull('ends_at')
                        ->orWhere('ends_at', '>=', now());
                })
                ->orderBy('sort_order')
                ->get(),
        ]);
    }
}
