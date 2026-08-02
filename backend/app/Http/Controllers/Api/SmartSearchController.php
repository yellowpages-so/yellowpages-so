<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SmartSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SmartSearchController extends Controller
{
    public function __construct(
        private readonly SmartSearchService $searchService
    ) {
    }

    public function search(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'verified' => ['nullable', 'boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'numeric', 'min:0.1', 'max:500'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $result = $this->searchService->search($filters);

        DB::table('analytics.search_events')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $request->user()?->id,
            'session_id' => $request->header('X-Search-Session'),
            'query' => $filters['q'] ?? null,
            'filters' => json_encode($filters),
            'result_count' => $result['estimated_total_hits'] ?? count($result['hits'] ?? []),
            'processing_time_ms' => $result['processing_time_ms'],
            'source' => $result['source'],
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->searchService->suggestions(
                $data['q'],
                $data['limit'] ?? 8
            ),
        ]);
    }

    public function popular(): JsonResponse
    {
        $queries = DB::table('analytics.search_events')
            ->whereNotNull('query')
            ->where('query', '!=', '')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('lower(query) as query, count(*) as searches')
            ->groupByRaw('lower(query)')
            ->orderByDesc('searches')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $queries,
        ]);
    }
}
