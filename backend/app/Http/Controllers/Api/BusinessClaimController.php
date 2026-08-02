<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBusinessClaimRequest;
use App\Models\Business;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;

class BusinessClaimController extends Controller
{
    public function __construct(
        private readonly VerificationService $verificationService
    ) {}

    public function store(
        StoreBusinessClaimRequest $request,
        Business $business
    ): JsonResponse {
        $claim = $this->verificationService->createClaim(
            $business,
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Business claim submitted successfully.',
            'data' => $claim,
        ], 201);
    }
}
