<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportReviewRequest;
use App\Http\Requests\StoreReviewReplyRequest;
use App\Http\Requests\StoreReviewRequest;
use App\Models\Business;
use App\Services\ReviewReputationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewReputationService $service
    ) {}

    public function index(Business $business): JsonResponse
    {
        $query = DB::table('reviews.reviews as reviews')
            ->leftJoin('iam.user_profiles as profiles', 'profiles.user_id', '=', 'reviews.user_id')
            ->leftJoin('reviews.review_replies as replies', 'replies.review_id', '=', 'reviews.id')
            ->where('reviews.business_id', $business->id);

        if (Schema::hasColumn('reviews.reviews', 'status')) {
            $query->where('reviews.status', 'published');
        }

        return response()->json([
            'success' => true,
            'data' => $query
                ->select([
                    'reviews.*',
                    'profiles.display_name as reviewer_name',
                    'replies.reply as business_reply',
                ])
                ->orderByDesc('reviews.created_at')
                ->paginate(20),
        ]);
    }

    public function store(StoreReviewRequest $request, Business $business): JsonResponse
    {
        try {
            $id = $this->service->createReview(
                $business,
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
            'message' => 'Review submitted successfully.',
            'data' => ['id' => $id],
        ], 201);
    }

    public function reply(StoreReviewReplyRequest $request, string $reviewId): JsonResponse
    {
        $id = $this->service->reply(
            $reviewId,
            $request->user(),
            $request->validated()['reply']
        );

        return response()->json([
            'success' => true,
            'message' => 'Reply saved successfully.',
            'data' => ['id' => $id],
        ], 201);
    }

    public function helpful(Request $request, string $reviewId): JsonResponse
    {
        $data = $request->validate([
            'helpful' => ['required', 'boolean'],
        ]);

        $this->service->helpful(
            $reviewId,
            $request->user(),
            $data['helpful']
        );

        return response()->json([
            'success' => true,
            'message' => 'Vote recorded.',
        ]);
    }

    public function report(ReportReviewRequest $request, string $reviewId): JsonResponse
    {
        $id = $this->service->report(
            $reviewId,
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Review report submitted.',
            'data' => ['id' => $id],
        ], 201);
    }
}
