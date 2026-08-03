<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkflowAutomationTest extends TestCase
{
    public function test_user_can_create_workflow(): void
    {
        $user = User::query()->create([
            'public_id' => 'test-'.Str::ulid(),
            'status' => 'active',
            'password_hash' => Hash::make(
                'Password123!'
            ),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson(
            '/api/v1/automation/workflows',
            [
                'name' => 'Lead Follow-up',
                'code' => 'lead-follow-up-'
                    .Str::lower(Str::random(6)),
                'trigger_type' => 'lead.created',
                'steps' => [
                    [
                        'step_type' => 'notification',
                        'name' => 'Notify owner',
                        'configuration' => [
                            'channel' => 'email',
                        ],
                    ],
                ],
            ]
        );

        $response
            ->assertCreated()
            ->assertJsonPath('success', true);

        $workflowId = $response->json('data.id');

        $this->assertDatabaseHas(
            'automation.workflows',
            [
                'id' => $workflowId,
                'created_by' => $user->id,
            ]
        );

        DB::table('automation.workflow_versions')
            ->where('workflow_id', $workflowId)
            ->delete();

        DB::table('automation.workflow_steps')
            ->where('workflow_id', $workflowId)
            ->delete();

        DB::table('automation.workflows')
            ->where('id', $workflowId)
            ->delete();

        $user->delete();
    }
}
