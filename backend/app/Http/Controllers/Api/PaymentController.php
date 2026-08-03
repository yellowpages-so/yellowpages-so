<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePaymentIntentRequest;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $service
    ) {}

    public function providers(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => \DB::table('payments.providers')
                ->where('active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function createIntent(
        CreatePaymentIntentRequest $request
    ): JsonResponse {
        try {
            $data = $this->service->createIntent(
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
            'message' => 'Payment intent created.',
            'data' => $data,
        ], 201);
    }

    public function capture(
        string $intentId
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => 'Payment captured.',
            'data' => $this->service->capture($intentId),
        ]);
    }

    public function refund(
        Request $request,
        string $intentId
    ): JsonResponse {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Refund processed.',
            'data' => $this->service->refund(
                $request->user(),
                $intentId,
                (float) $data['amount'],
                $data['reason'] ?? null
            ),
        ]);
    }
}
