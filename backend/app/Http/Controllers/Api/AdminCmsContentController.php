<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CmsContentService;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCmsContentController extends Controller
{
    public function __construct(
        private readonly CmsContentService $service
    ) {}

    public function createPage(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'template' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:draft,review,scheduled,published,archived'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'blocks' => ['nullable', 'array'],
            'locale' => ['nullable', 'string', 'max:10'],
            'is_homepage' => ['boolean'],
            'scheduled_at' => ['nullable', 'date'],
            'seo' => ['nullable', 'array'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Page created.',
            'data' => [
                'id' => $this->service->createPage(
                    $request->user(),
                    $data
                ),
            ],
        ], 201);
    }

    public function createPost(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['nullable', 'string'],
            'status' => ['nullable', 'in:draft,review,scheduled,published,archived'],
            'locale' => ['nullable', 'string', 'max:10'],
            'featured_media_id' => ['nullable', 'uuid'],
            'featured' => ['boolean'],
            'scheduled_at' => ['nullable', 'date'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['uuid'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['uuid'],
            'seo' => ['nullable', 'array'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post created.',
            'data' => [
                'id' => $this->service->createPost(
                    $request->user(),
                    $data
                ),
            ],
        ], 201);
    }

    public function dashboard(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'pages' => DB::table('cms.pages')
                    ->whereNull('deleted_at')
                    ->count(),
                'posts' => DB::table('cms.posts')
                    ->whereNull('deleted_at')
                    ->count(),
                'drafts' => DB::table('cms.pages')
                    ->where('status', 'draft')
                    ->count()
                    + DB::table('cms.posts')
                        ->where('status', 'draft')
                        ->count(),
                'scheduled' => DB::table('cms.pages')
                    ->where('status', 'scheduled')
                    ->count()
                    + DB::table('cms.posts')
                        ->where('status', 'scheduled')
                        ->count(),
            ],
        ]);
    }
}
