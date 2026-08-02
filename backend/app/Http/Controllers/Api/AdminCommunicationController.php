<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendCommunicationRequest;
use App\Services\CommunicationManager;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCommunicationController extends Controller
{
    public function __construct(
        private readonly CommunicationManager $manager
    ) {}

    public function send(
        SendCommunicationRequest $request
    ): JsonResponse {
        AdminAccess::authorize($request->user());
        $data = $request->validated();

        $ids = $this->manager->queue(
            $data['event_code'],
            [
                'user_id' => $data['user_id'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'device_token' => $data['device_token'] ?? null,
            ],
            $data['variables'] ?? [],
            $data['business_id'] ?? null,
            $data['channels']
        );

        return response()->json([
            'success' => true,
            'message' => 'Communication queued.',
            'data' => ['ids' => $ids],
        ], 201);
    }

    public function messages(
        Request $request
    ): JsonResponse {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => DB::table(
                'notifications.messages'
            )
                ->orderByDesc('created_at')
                ->paginate(50),
        ]);
    }

    public function dashboard(
        Request $request
    ): JsonResponse {
        AdminAccess::authorize($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'pending' => DB::table(
                    'notifications.messages'
                )->where('status', 'pending')->count(),
                'sent' => DB::table(
                    'notifications.messages'
                )->where('status', 'sent')->count(),
                'delivered' => DB::table(
                    'notifications.messages'
                )->where('status', 'delivered')->count(),
                'failed' => DB::table(
                    'notifications.messages'
                )->where('status', 'failed')->count(),
                'in_app_unread' => DB::table(
                    'notifications.in_app_notifications'
                )->whereNull('read_at')->count(),
            ],
        ]);
    }
}
