<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddCartItemRequest;
use App\Http\Requests\CheckoutRequest;
use App\Http\Requests\StoreProductRequest;
use App\Services\CommerceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CommerceController extends Controller
{
    public function __construct(
        private readonly CommerceService $service
    ) {}

    public function products(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'business_id' => ['nullable', 'uuid'],
            'type' => ['nullable', 'in:product,service,package,digital'],
        ]);

        $products = DB::table('commerce.products')
            ->where('status', 'published')
            ->whereNull('deleted_at')
            ->when(
                $data['q'] ?? null,
                fn ($query, string $q) => $query->where('name', 'ilike', "%{$q}%")
            )
            ->when(
                $data['business_id'] ?? null,
                fn ($query, string $businessId) => $query->where('business_id', $businessId)
            )
            ->when(
                $data['type'] ?? null,
                fn ($query, string $type) => $query->where('type', $type)
            )
            ->orderByDesc('published_at')
            ->paginate(24);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function storeProduct(
        StoreProductRequest $request
    ): JsonResponse {
        try {
            $id = $this->service->createProduct(
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
            'message' => 'Product created successfully.',
            'data' => ['id' => $id],
        ], 201);
    }

    public function addToCart(
        AddCartItemRequest $request
    ): JsonResponse {
        try {
            $cart = $this->service->addToCart(
                $request->user(),
                $request->validated()['product_id'],
                $request->validated()['quantity'],
                $request->validated()['session_id'] ?? null
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart.',
            'data' => $cart,
        ], 201);
    }

    public function cart(string $cartId): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->cart($cartId),
        ]);
    }

    public function checkout(
        CheckoutRequest $request
    ): JsonResponse {
        try {
            $order = $this->service->checkout(
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
            'message' => 'Order placed successfully.',
            'data' => $order,
        ], 201);
    }

    public function ownerOrders(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->service->ownerOrders(
                $request->user()
            ),
        ]);
    }
}
