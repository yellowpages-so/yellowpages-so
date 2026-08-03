<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class WorkflowAutomationService
{
    public function createWorkflow(
        User $user,
        array $data
    ): string {
        if (! empty($data['business_id'])) {
            $this->assertManager(
                $user,
                $data['business_id']
            );
        }

        $id = (string) Str::uuid();

        DB::transaction(function () use (
            $user,
            $data,
            $id
        ): void {
            DB::table('automation.workflows')->insert([
                'id' => $id,
                'business_id' => $data['business_id'] ?? null,
                'created_by' => $user->id,
                'name' => $data['name'],
                'code' => $data['code'],
                'status' => 'draft',
                'trigger_type' => $data['trigger_type'],
                'trigger_config' => json_encode(
                    $data['trigger_config'] ?? []
                ),
                'settings' => json_encode(
                    $data['settings'] ?? []
                ),
                'version' => 1,
                'active' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($data['steps'] ?? [] as $index => $step) {
                DB::table('automation.workflow_steps')->insert([
                    'id' => (string) Str::uuid(),
                    'workflow_id' => $id,
                    'parent_step_id' => null,
                    'step_type' => $step['step_type'],
                    'name' => $step['name'],
                    'configuration' => json_encode(
                        $step['configuration'] ?? []
                    ),
                    'conditions' => json_encode(
                        $step['conditions'] ?? []
                    ),
                    'sort_order' => $index,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->snapshot(
                $id,
                $user->id,
                1
            );
        });

        return $id;
    }

    public function publishWorkflow(
        User $user,
        string $workflowId
    ): void {
        $workflow = DB::table('automation.workflows')
            ->where('id', $workflowId)
            ->whereNull('deleted_at')
            ->first();

        if (! $workflow) {
            throw new RuntimeException('Workflow not found.');
        }

        if ($workflow->business_id) {
            $this->assertManager(
                $user,
                $workflow->business_id
            );
        }

        $version = (int) $workflow->version + 1;

        DB::transaction(function () use (
            $workflowId,
            $user,
            $version
        ): void {
            DB::table('automation.workflows')
                ->where('id', $workflowId)
                ->update([
                    'status' => 'published',
                    'active' => true,
                    'version' => $version,
                    'published_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->snapshot(
                $workflowId,
                $user->id,
                $version
            );
        });
    }

    public function trigger(
        string $triggerType,
        array $payload,
        ?string $eventId = null
    ): int {
        $workflows = DB::table('automation.workflows')
            ->where('trigger_type', $triggerType)
            ->where('active', true)
            ->where('status', 'published')
            ->whereNull('deleted_at')
            ->get();

        foreach ($workflows as $workflow) {
            DB::table('automation.executions')->insert([
                'id' => (string) Str::uuid(),
                'workflow_id' => $workflow->id,
                'status' => 'queued',
                'trigger_type' => $triggerType,
                'trigger_event_id' => $eventId,
                'input' => json_encode($payload),
                'context' => json_encode([]),
                'attempts' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $workflows->count();
    }

    public function processPending(int $limit): int
    {
        $executions = DB::table('automation.executions')
            ->where('status', 'queued')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        $processed = 0;

        foreach ($executions as $execution) {
            $this->processExecution(
                $execution->id
            );
            $processed++;
        }

        return $processed;
    }

    public function decideApproval(
        User $user,
        string $approvalId,
        string $decision,
        ?string $notes
    ): void {
        $task = DB::table('automation.approval_tasks')
            ->where('id', $approvalId)
            ->where('status', 'pending')
            ->first();

        if (! $task) {
            throw new RuntimeException(
                'Approval task not found.'
            );
        }

        if (
            $task->assigned_to
            && $task->assigned_to !== $user->id
        ) {
            throw new RuntimeException(
                'Approval task is assigned to another user.'
            );
        }

        DB::transaction(function () use (
            $task,
            $decision,
            $notes
        ): void {
            DB::table('automation.approval_tasks')
                ->where('id', $task->id)
                ->update([
                    'status' => $decision,
                    'decision_notes' => $notes,
                    'decided_at' => now(),
                ]);

            DB::table('automation.executions')
                ->where('id', $task->execution_id)
                ->update([
                    'status' => $decision === 'approved'
                        ? 'queued'
                        : 'cancelled',
                    'updated_at' => now(),
                ]);
        });
    }

    public function createIncomingWebhook(
        User $user,
        string $workflowId,
        string $name
    ): array {
        $workflow = DB::table('automation.workflows')
            ->where('id', $workflowId)
            ->first();

        if (! $workflow) {
            throw new RuntimeException('Workflow not found.');
        }

        if ($workflow->business_id) {
            $this->assertManager(
                $user,
                $workflow->business_id
            );
        }

        $endpointKey = Str::lower(
            Str::random(32)
        );
        $secret = Str::random(48);

        DB::table('automation.webhooks')->insert([
            'id' => (string) Str::uuid(),
            'workflow_id' => $workflowId,
            'direction' => 'incoming',
            'name' => $name,
            'endpoint_key' => $endpointKey,
            'secret_hash' => Hash::make($secret),
            'active' => true,
            'created_at' => now(),
        ]);

        return [
            'endpoint_key' => $endpointKey,
            'secret' => $secret,
        ];
    }

    private function processExecution(
        string $executionId
    ): void {
        $execution = DB::table(
            'automation.executions'
        )
            ->where('id', $executionId)
            ->first();

        if (! $execution) {
            return;
        }

        DB::table('automation.executions')
            ->where('id', $executionId)
            ->update([
                'status' => 'running',
                'started_at' => now(),
                'attempts' => $execution->attempts + 1,
                'updated_at' => now(),
            ]);

        $steps = DB::table(
            'automation.workflow_steps'
        )
            ->where('workflow_id', $execution->workflow_id)
            ->where('active', true)
            ->orderBy('sort_order')
            ->get();

        try {
            foreach ($steps as $step) {
                $result = $this->executeStep(
                    $execution,
                    $step
                );

                if ($result === 'waiting_approval') {
                    DB::table('automation.executions')
                        ->where('id', $executionId)
                        ->update([
                            'status' => 'waiting_approval',
                            'updated_at' => now(),
                        ]);

                    return;
                }
            }

            DB::table('automation.executions')
                ->where('id', $executionId)
                ->update([
                    'status' => 'completed',
                    'output' => json_encode([
                        'success' => true,
                    ]),
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);
        } catch (\Throwable $exception) {
            $attempts = $execution->attempts + 1;
            $failed = $attempts >= config(
                'automation.max_attempts'
            );

            DB::table('automation.executions')
                ->where('id', $executionId)
                ->update([
                    'status' => $failed
                        ? 'failed'
                        : 'queued',
                    'failed_at' => $failed
                        ? now()
                        : null,
                    'error_message' => $exception->getMessage(),
                    'updated_at' => now(),
                ]);

            if ($failed) {
                DB::table(
                    'automation.dead_letters'
                )->insert([
                    'id' => (string) Str::uuid(),
                    'execution_id' => $executionId,
                    'reason' => $exception->getMessage(),
                    'payload' => $execution->input,
                    'attempts' => $attempts,
                    'created_at' => now(),
                ]);
            }
        }
    }

    private function executeStep(
        object $execution,
        object $step
    ): string {
        $executionStepId = (string) Str::uuid();

        DB::table('automation.execution_steps')
            ->insert([
                'id' => $executionStepId,
                'execution_id' => $execution->id,
                'workflow_step_id' => $step->id,
                'status' => 'running',
                'input' => $execution->input,
                'attempts' => 1,
                'started_at' => now(),
            ]);

        $configuration = json_decode(
            $step->configuration,
            true
        ) ?: [];

        if ($step->step_type === 'approval') {
            DB::table(
                'automation.approval_tasks'
            )->insert([
                'id' => (string) Str::uuid(),
                'execution_id' => $execution->id,
                'workflow_step_id' => $step->id,
                'assigned_to' => $configuration['assigned_to'] ?? null,
                'status' => 'pending',
                'title' => $configuration['title']
                    ?? $step->name,
                'description' => $configuration['description'] ?? null,
                'payload' => $execution->input,
                'due_at' => isset(
                    $configuration['due_minutes']
                )
                    ? now()->addMinutes(
                        $configuration['due_minutes']
                    )
                    : null,
                'created_at' => now(),
            ]);

            DB::table('automation.execution_steps')
                ->where('id', $executionStepId)
                ->update([
                    'status' => 'waiting_approval',
                ]);

            return 'waiting_approval';
        }

        if ($step->step_type === 'delay') {
            DB::table('automation.execution_steps')
                ->where('id', $executionStepId)
                ->update([
                    'status' => 'delayed',
                    'retry_at' => now()->addMinutes(
                        $configuration['minutes'] ?? 1
                    ),
                ]);

            return 'completed';
        }

        DB::table('automation.execution_steps')
            ->where('id', $executionStepId)
            ->update([
                'status' => 'completed',
                'output' => json_encode([
                    'step_type' => $step->step_type,
                    'processed' => true,
                ]),
                'completed_at' => now(),
            ]);

        return 'completed';
    }

    private function snapshot(
        string $workflowId,
        string $userId,
        int $version
    ): void {
        $workflow = DB::table(
            'automation.workflows'
        )
            ->where('id', $workflowId)
            ->first();

        $steps = DB::table(
            'automation.workflow_steps'
        )
            ->where('workflow_id', $workflowId)
            ->orderBy('sort_order')
            ->get();

        DB::table(
            'automation.workflow_versions'
        )->insert([
            'id' => (string) Str::uuid(),
            'workflow_id' => $workflowId,
            'version' => $version,
            'definition' => json_encode([
                'workflow' => $workflow,
                'steps' => $steps,
            ]),
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    private function assertManager(
        User $user,
        string $businessId
    ): void {
        $allowed = DB::table(
            'directory.business_members'
        )
            ->where('business_id', $businessId)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if (! $allowed) {
            throw new RuntimeException(
                'You do not manage this business.'
            );
        }
    }
}
