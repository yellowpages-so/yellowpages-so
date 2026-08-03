<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $service
    ) {}

    public function dashboard(
        Request $request
    ): JsonResponse {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'successful_volume' => (float) DB::table('payments.payment_intents')
                    ->where('status', 'succeeded')
                    ->sum('captured_amount'),
                'refund_volume' => (float) DB::table('payments.refunds')
                    ->where('status', 'succeeded')
                    ->sum('amount'),
                'pending_intents' => DB::table('payments.payment_intents')
                    ->whereIn('status', [
                        'requires_payment_method',
                        'requires_action',
                        'processing',
                    ])
                    ->count(),
                'held_escrow' => (float) DB::table('payments.escrows')
                    ->where('status', 'held')
                    ->sum('amount'),
            ],
        ]);
    }

    public function intents(
        Request $request
    ): JsonResponse {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => DB::table('payments.payment_intents')
                ->orderByDesc('created_at')
                ->paginate(50),
        ]);
    }

    public function releaseEscrow(
        Request $request,
        string $escrowId
    ): JsonResponse {
        AdminAccess::authorize($request->user());
        $this->service->releaseEscrow($escrowId);

        return response()->json([
            'success' => true,
            'message' => 'Escrow released.',
        ]);
    }
}
