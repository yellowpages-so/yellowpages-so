<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Services\CommunicationManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function __construct(
        private readonly CommunicationManager $manager
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DB::table(
                'notifications.in_app_notifications'
            )
                ->where('user_id', $request->user()->id)
                ->orderByDesc('created_at')
                ->paginate(25),
            'unread_count' => $this->manager->unreadCount(
                $request->user()->id
            ),
        ]);
    }

    public function markRead(
        Request $request,
        string $notificationId
    ): JsonResponse {
        DB::table(
            'notifications.in_app_notifications'
        )
            ->where('id', $notificationId)
            ->where('user_id', $request->user()->id)
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ]);
    }

    public function markAllRead(
        Request $request
    ): JsonResponse {
        DB::table(
            'notifications.in_app_notifications'
        )
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
        ]);
    }

    public function preferences(
        Request $request
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => DB::table(
                'notifications.user_preferences'
            )
                ->where('user_id', $request->user()->id)
                ->orderBy('event_code')
                ->get(),
        ]);
    }

    public function updatePreferences(
        UpdateNotificationPreferencesRequest $request
    ): JsonResponse {
        $data = $request->validated();
        $userId = $request->user()->id;

        DB::table(
            'notifications.user_preferences'
        )->updateOrInsert(
            [
                'user_id' => $userId,
                'event_code' => $data['event_code'],
            ],
            [
                'id' => DB::table(
                    'notifications.user_preferences'
                )
                    ->where('user_id', $userId)
                    ->where(
                        'event_code',
                        $data['event_code']
                    )
                    ->value('id') ?? (string) Str::uuid(),
                'email_enabled' => $data['email_enabled'] ?? true,
                'sms_enabled' => $data['sms_enabled'] ?? false,
                'whatsapp_enabled' => $data['whatsapp_enabled'] ?? false,
                'push_enabled' => $data['push_enabled'] ?? false,
                'in_app_enabled' => $data['in_app_enabled'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated.',
        ]);
    }
}
