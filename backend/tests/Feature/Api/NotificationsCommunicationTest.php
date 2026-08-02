<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Services\CommunicationManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationsCommunicationTest extends TestCase
{
    public function test_in_app_notification_can_be_created_and_read(): void
    {
        $user = User::query()->create([
            'public_id' => 'test-'.Str::ulid(),
            'status' => 'active',
            'password_hash' => Hash::make('Password123!'),
        ]);

        app(CommunicationManager::class)->queue(
            'lead_received',
            [
                'user_id' => $user->id,
                'email' => 'test@example.com',
            ],
            [
                'title' => 'Insurance enquiry',
                'action_url' => '/owner/leads',
            ],
            null,
            ['in_app']
        );

        Sanctum::actingAs($user);

        $response = $this->getJson(
            '/api/v1/notifications'
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('unread_count', 1);

        $id = $response->json('data.data.0.id');

        $this->postJson(
            "/api/v1/notifications/{$id}/read"
        )
            ->assertOk()
            ->assertJsonPath('success', true);

        DB::table(
            'notifications.in_app_notifications'
        )
            ->where('user_id', $user->id)
            ->delete();

        $user->delete();
    }
}
