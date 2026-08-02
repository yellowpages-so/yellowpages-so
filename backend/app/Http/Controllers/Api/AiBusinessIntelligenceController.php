<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\AiBusinessIntelligenceService;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiBusinessIntelligenceController extends Controller
{
    public function __construct(
        private readonly AiBusinessIntelligenceService $service
    ) {}

    public function businessDescription(
        Request $request,
        Business $business
    ): JsonResponse {
        $this->assertManager($request, $business);

        return response()->json([
            'success' => true,
            'data' => $this->service->generateBusinessDescription($business),
        ]);
    }

    public function reviewSummary(
        Request $request,
        Business $business
    ): JsonResponse {
        $this->assertManager($request, $business);

        return response()->json([
            'success' => true,
            'data' => $this->service->summarizeReviews($business),
        ]);
    }

    public function recommendations(Business $business): JsonResponse
    {
        $items = DB::table('analytics.business_recommendations as recommendations')
            ->join('directory.businesses as businesses', 'businesses.id', '=', 'recommendations.recommended_business_id')
            ->where('recommendations.business_id', $business->id)
            ->where(function ($query): void {
                $query->whereNull('recommendations.expires_at')
                    ->orWhere('recommendations.expires_at', '>', now());
            })
            ->orderByDesc('recommendations.score')
            ->get([
                'businesses.public_id',
                'businesses.trading_name',
                'businesses.slug',
                'businesses.short_description',
                'businesses.average_rating',
                'businesses.review_count',
                'recommendations.score',
                'recommendations.reasons',
            ]);

        if ($items->isEmpty()) {
            $this->service->rebuildRecommendations($business->id);

            $items = DB::table('analytics.business_recommendations as recommendations')
                ->join('directory.businesses as businesses', 'businesses.id', '=', 'recommendations.recommended_business_id')
                ->where('recommendations.business_id', $business->id)
                ->orderByDesc('recommendations.score')
                ->get([
                    'businesses.public_id',
                    'businesses.trading_name',
                    'businesses.slug',
                    'businesses.short_description',
                    'businesses.average_rating',
                    'businesses.review_count',
                    'recommendations.score',
                    'recommendations.reasons',
                ]);
        }

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function scoreLead(
        Request $request,
        string $quoteRequestId
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $this->service->scoreLead($quoteRequestId),
        ]);
    }

    public function riskScan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entity_type' => ['required', 'string', 'max:100'],
            'entity_id' => ['required', 'uuid'],
            'text' => ['required', 'string', 'max:10000'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->service->scanRisk(
                $data['entity_type'],
                $data['entity_id'],
                $data['text']
            ),
        ]);
    }

    public function adminDashboard(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => $this->service->dashboard(),
        ]);
    }

    private function assertManager(
        Request $request,
        Business $business
    ): void {
        abort_unless(
            DB::table('directory.business_members')
                ->where('business_id', $business->id)
                ->where('user_id', $request->user()->id)
                ->where('status', 'active')
                ->exists(),
            403,
            'You do not manage this business.'
        );
    }
}
