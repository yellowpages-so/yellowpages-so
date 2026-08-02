<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ModerateReviewRequest;
use App\Services\ReviewReputationService;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReviewController extends Controller
{
    public function __construct(
        private readonly ReviewReputationService $service
    ) {}

    public function queue(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => DB::table('reviews.reviews as reviews')
                ->join('directory.businesses as businesses', 'businesses.id', '=', 'reviews.business_id')
                ->leftJoin('iam.user_profiles as profiles', 'profiles.user_id', '=', 'reviews.user_id')
                ->select([
                    'reviews.*',
                    'businesses.trading_name',
                    'profiles.display_name as reviewer_name',
                ])
                ->orderByDesc('reviews.moderation_score')
                ->orderByDesc('reviews.created_at')
                ->paginate(25),
        ]);
    }

    public function moderate(
        ModerateReviewRequest $request,
        string $reviewId
    ): JsonResponse {
        AdminAccess::authorize($request->user());

        $this->service->moderate(
            $reviewId,
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Review moderation action recorded.',
        ]);
    }
}
