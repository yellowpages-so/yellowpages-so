<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateApiClientRequest;
use App\Http\Requests\CreateWebhookSubscriptionRequest;
use App\Services\ApiClientService;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DeveloperPlatformController extends Controller
{
    public function __construct(
        private readonly ApiClientService $clients,
        private readonly WebhookService $webhooks
    ) {}

    public function createClient(
        CreateApiClientRequest $request
    ): JsonResponse {
        try {
            $result = $this->clients->create(
                $request->user(),
                $request->validated()
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'API client created. Store the secret securely.',
            'data' => $result,
        ], 201);
    }

    public function rotateSecret(
        Request $request,
        string $clientId
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => 'API secret rotated.',
            'data' => $this->clients->rotateSecret(
                $request->user(),
                $clientId
            ),
        ]);
    }

    public function clients(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table('developer.api_clients')
                ->where('created_by', $request->user()->id)
                ->orderByDesc('created_at')
                ->get([
                    'id',
                    'business_id',
                    'name',
                    'environment',
                    'status',
                    'public_key',
                    'scopes',
                    'rate_limit_per_minute',
                    'last_used_at',
                    'expires_at',
                    'created_at',
                ]),
        ]);
    }

    public function createWebhook(
        CreateWebhookSubscriptionRequest $request
    ): JsonResponse {
        $client = DB::table('developer.api_clients')
            ->where('id', $request->validated()['api_client_id'])
            ->where('created_by', $request->user()->id)
            ->first();

        abort_unless($client, 403, 'You do not own this API client.');

        return response()->json([
            'success' => true,
            'message' => 'Webhook subscription created.',
            'data' => $this->webhooks->subscribe(
                $request->validated()
            ),
        ], 201);
    }

    public function usage(
        Request $request,
        string $clientId
    ): JsonResponse {
        $client = DB::table('developer.api_clients')
            ->where('id', $clientId)
            ->where('created_by', $request->user()->id)
            ->first();

        abort_unless($client, 403);

        return response()->json([
            'success' => true,
            'data' => [
                'requests' => DB::table('developer.api_usage')
                    ->where('api_client_id', $clientId)
                    ->count(),
                'errors' => DB::table('developer.api_usage')
                    ->where('api_client_id', $clientId)
                    ->where('status_code', '>=', 400)
                    ->count(),
                'average_duration_ms' => (float) (
                    DB::table('developer.api_usage')
                        ->where('api_client_id', $clientId)
                        ->avg('duration_ms') ?? 0
                ),
                'routes' => DB::table('developer.api_usage')
                    ->where('api_client_id', $clientId)
                    ->selectRaw('route, count(*) as requests')
                    ->groupBy('route')
                    ->orderByDesc('requests')
                    ->limit(20)
                    ->get(),
            ],
        ]);
    }
}
