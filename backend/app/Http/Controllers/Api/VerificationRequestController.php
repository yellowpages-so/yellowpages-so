<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVerificationRequest;
use App\Http\Requests\UploadVerificationDocumentRequest;
use App\Models\Business;
use App\Models\VerificationRequest;
use App\Services\VerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerificationRequestController extends Controller
{
    public function __construct(
        private readonly VerificationService $verificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $businessIds = DB::table('directory.business_members')
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->pluck('business_id');

        $requests = VerificationRequest::query()
            ->whereIn('business_id', $businessIds)
            ->orderByDesc('submitted_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    public function store(
        StoreVerificationRequest $request,
        Business $business
    ): JsonResponse {
        $verification = $this->verificationService
            ->createVerificationRequest(
                $business,
                $request->user(),
                $request->validated()['requested_level_code']
            );

        return response()->json([
            'success' => true,
            'message' => 'Verification request submitted successfully.',
            'data' => $verification,
        ], 201);
    }

    public function uploadDocument(
        UploadVerificationDocumentRequest $request,
        VerificationRequest $verificationRequest
    ): JsonResponse {
        $document = $this->verificationService->storeDocument(
            $verificationRequest,
            $request->user(),
            $request->validated(),
            $request->file('file')
        );

        return response()->json([
            'success' => true,
            'message' => 'Verification document uploaded successfully.',
            'data' => $document,
        ], 201);
    }
}
