<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCommerceController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'published_products' => DB::table('commerce.products')
                    ->where('status', 'published')
                    ->whereNull('deleted_at')
                    ->count(),
                'pending_orders' => DB::table('commerce.orders')
                    ->where('status', 'pending')
                    ->count(),
                'revenue' => (float) DB::table('commerce.orders')
                    ->where('payment_status', 'paid')
                    ->sum('grand_total'),
                'unfulfilled_orders' => DB::table('commerce.orders')
                    ->where('fulfilment_status', 'unfulfilled')
                    ->count(),
            ],
        ]);
    }

    public function orders(Request $request): JsonResponse
    {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => DB::table('commerce.orders')
                ->orderByDesc('created_at')
                ->paginate(50),
        ]);
    }

    public function updateOrder(
        Request $request,
        string $orderId
    ): JsonResponse {
        AdminAccess::authorize($request->user());

        $data = $request->validate([
            'status' => ['nullable', 'in:pending,confirmed,processing,completed,cancelled'],
            'payment_status' => ['nullable', 'in:unpaid,pending,paid,failed,refunded'],
            'fulfilment_status' => ['nullable', 'in:unfulfilled,partial,fulfilled,returned'],
        ]);

        DB::table('commerce.orders')
            ->where('id', $orderId)
            ->update([
                ...$data,
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Order updated.',
        ]);
    }
}
