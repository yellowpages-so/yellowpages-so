<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuoteRequest;
use App\Http\Requests\StoreQuoteResponse;
use App\Http\Requests\UpdateLeadStatusRequest;
use App\Services\LeadMarketplaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class LeadMarketplaceController extends Controller
{
    public function __construct(
        private readonly LeadMarketplaceService $service
    ) {}

    public function store(StoreQuoteRequest $request): JsonResponse
    {
        $result = $this->service->create(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Quote request submitted successfully.',
            'data' => $result,
        ], 201);
    }

    public function inbox(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->inbox(
                $request->user(),
                $request->validate([
                    'status' => [
                        'nullable',
                        'in:new,viewed,contacted,quoted,won,lost,closed',
                    ],
                ])
            ),
        ]);
    }

    public function respond(
        StoreQuoteResponse $request,
        string $quoteRequestId,
        string $businessId
    ): JsonResponse {
        try {
            $id = $this->service->respond(
                $quoteRequestId,
                $businessId,
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
            'message' => 'Quote submitted successfully.',
            'data' => ['id' => $id],
        ], 201);
    }

    public function updateStatus(
        UpdateLeadStatusRequest $request,
        string $assignmentId
    ): JsonResponse {
        $this->service->updateStatus(
            $assignmentId,
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Lead status updated.',
        ]);
    }

    public function analytics(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->analytics($request->user()),
        ]);
    }
}
