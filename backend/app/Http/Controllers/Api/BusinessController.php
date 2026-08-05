<?php

namespace App\Http\Controllers\Api;

use App\Domain\Directory\DTO\CreateBusinessData;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBusinessRequest;
use App\Http\Requests\UpdateBusinessRequest;
use App\Http\Resources\BusinessResource;
use App\Models\Business;
use App\Services\BusinessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BusinessController extends Controller
{
    public function __construct(private readonly BusinessService $businessService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $businesses = Business::query()
            ->whereHas(
                'members',
                fn ($query) => $query
                    ->where('iam.users.id', $request->user()->id)
                    ->where('directory.business_members.status', 'active')
            )
            ->latest('created_at')
            ->paginate(15);

        return BusinessResource::collection($businesses);
    }

    public function store(StoreBusinessRequest $request): JsonResponse
    {
        $data = $request->validated();

        $business = $this->businessService->create(
            $request->user(),
            CreateBusinessData::fromArray($data)
        );

        return response()->json([
            'success' => true,
            'message' => 'Business created successfully.',
            'data' => new BusinessResource($business),
        ], 201);
    }

    public function show(Business $business): JsonResponse
    {
        $this->authorize('view', $business);

        return response()->json([
            'success' => true,
            'data' => new BusinessResource($business),
        ]);
    }

    public function update(
        UpdateBusinessRequest $request,
        Business $business
    ): JsonResponse {
        $this->authorize('update', $business);

        $business = $this->businessService->update(
            $business,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Business updated successfully.',
            'data' => new BusinessResource($business),
        ]);
    }

    public function destroy(Business $business): JsonResponse
    {
        $this->authorize('delete', $business);

        $business->delete();

        return response()->json([
            'success' => true,
            'message' => 'Business archived successfully.',
        ]);
    }
}
