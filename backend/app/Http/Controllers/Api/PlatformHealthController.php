<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlatformHealthController extends Controller
{
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'service' => config('app.name'),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function ready(): JsonResponse
    {
        $checks = [
            'database' => $this->databaseCheck(),
            'cache' => $this->cacheCheck(),
            'queue' => [
                'status' => config('queue.default') !== 'sync'
                    ? 'healthy'
                    : 'degraded',
                'connection' => config('queue.default'),
            ],
        ];

        $healthy = collect($checks)->every(
            fn (array $check): bool => $check['status'] === 'healthy'
        );

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }

    private function databaseCheck(): array
    {
        try {
            DB::select('SELECT 1');

            return ['status' => 'healthy'];
        } catch (\Throwable $exception) {
            return [
                'status' => 'unhealthy',
                'message' => $exception->getMessage(),
            ];
        }
    }

    private function cacheCheck(): array
    {
        try {
            Cache::put('platform:ready', 'ok', 30);

            return [
                'status' => Cache::get('platform:ready') === 'ok'
                    ? 'healthy'
                    : 'unhealthy',
            ];
        } catch (\Throwable $exception) {
            return [
                'status' => 'unhealthy',
                'message' => $exception->getMessage(),
            ];
        }
    }
}
