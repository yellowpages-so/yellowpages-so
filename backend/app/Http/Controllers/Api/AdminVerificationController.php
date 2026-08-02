<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminVerificationDecisionRequest;
use App\Models\VerificationRequest;
use App\Services\VerificationService;
use App\Support\PlatformRoles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminVerificationController extends Controller
{
    public function __construct(
        private readonly VerificationService $verificationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless(
            PlatformRoles::hasAny(
                $request->user(),
                ['verifier', 'administrator', 'super_administrator']
            ),
            403,
            'You are not authorised to review verification requests.'
        );

        $requests = DB::table('verification.verification_requests as requests')
            ->join('directory.businesses as businesses', 'businesses.id', '=', 'requests.business_id')
            ->leftJoin('iam.user_profiles as profiles', 'profiles.user_id', '=', 'requests.submitted_by')
            ->select([
                'requests.*',
                'businesses.public_id as business_public_id',
                'businesses.trading_name',
                'profiles.display_name as submitted_by_name',
            ])
            ->orderByDesc('requests.submitted_at')
            ->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    public function decide(
        AdminVerificationDecisionRequest $request,
        VerificationRequest $verificationRequest
    ): JsonResponse {
        abort_unless(
            PlatformRoles::hasAny(
                $request->user(),
                ['verifier', 'administrator', 'super_administrator']
            ),
            403,
            'You are not authorised to review verification requests.'
        );

        $verification = $this->verificationService->decide(
            $verificationRequest,
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Verification decision recorded.',
            'data' => $verification,
        ]);
    }
}
