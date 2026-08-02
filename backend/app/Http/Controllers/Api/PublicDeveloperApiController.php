<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicDeveloperApiController extends Controller
{
    public function businesses(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = DB::table('directory.businesses')
            ->where('status', 'published')
            ->whereNull('deleted_at');

        if (! empty($data['q'])) {
            $query->where(
                'trading_name',
                'ilike',
                '%'.$data['q'].'%'
            );
        }

        return response()->json([
            'success' => true,
            'data' => $query
                ->limit($data['limit'] ?? 25)
                ->get([
                    'public_id',
                    'trading_name',
                    'slug',
                    'short_description',
                    'average_rating',
                    'review_count',
                ]),
        ]);
    }

    public function health(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'api_version' => 'v1',
                'environment' => app()->environment(),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
