<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminAdvertisingDecisionRequest;
use App\Services\AdvertisingService;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAdvertisingController extends Controller
{
    public function __construct(
        private readonly AdvertisingService $service
    ) {}

    public function queue(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => DB::table('advertising.campaigns as campaigns')
                ->join('directory.businesses as businesses', 'businesses.id', '=', 'campaigns.business_id')
                ->select([
                    'campaigns.*',
                    'businesses.trading_name',
                ])
                ->orderByDesc('campaigns.created_at')
                ->paginate(25),
        ]);
    }

    public function decide(
        AdminAdvertisingDecisionRequest $request,
        string $campaignId
    ): JsonResponse {
        AdminAccess::authorize($request->user());

        $this->service->decide(
            $campaignId,
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Campaign decision recorded.',
        ]);
    }
}
