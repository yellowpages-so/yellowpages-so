<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeSubscriptionPlanRequest;
use App\Http\Requests\StartSubscriptionRequest;
use App\Services\SubscriptionBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubscriptionBillingController extends Controller
{
    public function __construct(
        private readonly SubscriptionBillingService $service
    ) {}

    public function plans(): JsonResponse
    {
        $plans = DB::table('billing.plans')
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    public function start(StartSubscriptionRequest $request): JsonResponse
    {
        try {
            $result = $this->service->start(
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
            'message' => 'Subscription started successfully.',
            'data' => $result,
        ], 201);
    }

    public function current(
        Request $request,
        string $businessId
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $this->service->entitlements($businessId),
        ]);
    }

    public function changePlan(
        ChangeSubscriptionPlanRequest $request,
        string $subscriptionId
    ): JsonResponse {
        $this->service->changePlan(
            $request->user(),
            $subscriptionId,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Plan change recorded.',
        ]);
    }

    public function cancel(
        Request $request,
        string $subscriptionId
    ): JsonResponse {
        $data = $request->validate([
            'immediately' => ['required', 'boolean'],
        ]);

        $this->service->cancel(
            $request->user(),
            $subscriptionId,
            $data['immediately']
        );

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancellation recorded.',
        ]);
    }

    public function invoices(
        Request $request,
        string $businessId
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => DB::table('billing.invoices')
                ->where('business_id', $businessId)
                ->orderByDesc('created_at')
                ->paginate(25),
        ]);
    }
}
