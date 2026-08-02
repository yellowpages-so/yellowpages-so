<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdvertisingCampaignRequest;
use App\Http\Requests\StoreAdvertisingCreativeRequest;
use App\Services\AdvertisingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AdvertisingController extends Controller
{
    public function __construct(
        private readonly AdvertisingService $service
    ) {}

    public function placements(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => \DB::table('advertising.placements')
                ->where('active', true)
                ->orderBy('channel')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeCampaign(
        StoreAdvertisingCampaignRequest $request
    ): JsonResponse {
        try {
            $id = $this->service->createCampaign(
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
            'message' => 'Campaign created successfully.',
            'data' => ['id' => $id],
        ], 201);
    }

    public function storeCreative(
        StoreAdvertisingCreativeRequest $request,
        string $campaignId
    ): JsonResponse {
        $id = $this->service->createCreative(
            $request->user(),
            $campaignId,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Creative submitted for review.',
            'data' => ['id' => $id],
        ], 201);
    }

    public function slot(Request $request, string $placementCode): JsonResponse
    {
        $creative = $this->service->activeCreative(
            $placementCode,
            $request->validate([
                'city' => ['nullable', 'string', 'max:255'],
                'category' => ['nullable', 'string', 'max:255'],
                'business_id' => ['nullable', 'uuid'],
            ])
        );

        if (! $creative) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        $this->service->recordEvent(
            $creative,
            'impression',
            $request
        );

        return response()->json([
            'success' => true,
            'data' => $creative,
        ]);
    }

    public function click(
        Request $request,
        string $creativeId
    ): RedirectResponse {
        $creative = \DB::table('advertising.creatives as creatives')
            ->join('advertising.campaigns as campaigns', 'campaigns.id', '=', 'creatives.campaign_id')
            ->join('advertising.placements as placements', 'placements.id', '=', 'creatives.placement_id')
            ->where('creatives.id', $creativeId)
            ->select([
                'creatives.id',
                'creatives.destination_url',
                'campaigns.id as campaign_id',
                'campaigns.business_id',
                'campaigns.billing_model',
                'placements.base_price',
            ])
            ->first();

        abort_if(! $creative, 404, 'Advertisement not found.');

        $this->service->recordEvent(
            $creative,
            'click',
            $request
        );

        return redirect()->away($creative->destination_url);
    }

    public function analytics(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->analytics($request->user()),
        ]);
    }
}
