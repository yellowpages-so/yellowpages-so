<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeveloperIntegrationTest extends TestCase
{
    public function test_user_can_create_api_client(): void
    {
        $user = User::query()->create([
            'public_id' => 'test-'.Str::ulid(),
            'status' => 'active',
            'password_hash' => Hash::make('Password123!'),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson(
            '/api/v1/developer/clients',
            [
                'name' => 'Integration Test',
                'environment' => 'sandbox',
                'scopes' => ['businesses:read'],
            ]
        );

        fwrite(
            STDERR,
            "\nDEVELOPER STATUS: ".$response->status().
            "\nDEVELOPER BODY: ".$response->getContent()."\n"
        );

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);

        $clientId = $response->json('data.id');

        $this->assertDatabaseHas(
            'developer.api_clients',
            [
                'id' => $clientId,
                'created_by' => $user->id,
            ]
        );

        DB::table('developer.api_usage')
            ->where('api_client_id', $clientId)
            ->delete();

        DB::table('developer.webhook_subscriptions')
            ->where('api_client_id', $clientId)
            ->delete();

        DB::table('developer.oauth_clients')
            ->where('api_client_id', $clientId)
            ->delete();

        DB::table('developer.api_clients')
            ->where('id', $clientId)
            ->delete();

        $user->delete();
    }
}
