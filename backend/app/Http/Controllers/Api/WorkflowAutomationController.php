<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WorkflowAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WorkflowAutomationController extends Controller
{
    public function __construct(
        private readonly WorkflowAutomationService $service
    ) {}

    public function createWorkflow(
        Request $request
    ): JsonResponse {
        $data = $request->validate([
            'business_id' => ['nullable', 'uuid'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:150'],
            'trigger_type' => [
                'required',
                'string',
                'max:150',
            ],
            'trigger_config' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'steps' => ['nullable', 'array'],
            'steps.*.step_type' => [
                'required',
                'in:action,condition,delay,approval,webhook,notification,loop',
            ],
            'steps.*.name' => [
                'required',
                'string',
                'max:255',
            ],
            'steps.*.configuration' => [
                'nullable',
                'array',
            ],
            'steps.*.conditions' => [
                'nullable',
                'array',
            ],
        ]);

        try {
            $id = $this->service->createWorkflow(
                $request->user(),
                $data
            );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Workflow created.',
            'data' => ['id' => $id],
        ], 201);
    }

    public function workflows(
        Request $request
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => DB::table(
                'automation.workflows'
            )
                ->where('created_by', $request->user()->id)
                ->whereNull('deleted_at')
                ->orderByDesc('created_at')
                ->paginate(25),
        ]);
    }

    public function publish(
        Request $request,
        string $workflowId
    ): JsonResponse {
        $this->service->publishWorkflow(
            $request->user(),
            $workflowId
        );

        return response()->json([
            'success' => true,
            'message' => 'Workflow published.',
        ]);
    }

    public function executions(
        Request $request,
        string $workflowId
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => DB::table(
                'automation.executions'
            )
                ->where('workflow_id', $workflowId)
                ->orderByDesc('created_at')
                ->paginate(50),
        ]);
    }

    public function approvals(
        Request $request
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => DB::table(
                'automation.approval_tasks'
            )
                ->where(function ($query) use ($request): void {
                    $query->whereNull('assigned_to')
                        ->orWhere(
                            'assigned_to',
                            $request->user()->id
                        );
                })
                ->where('status', 'pending')
                ->orderBy('due_at')
                ->paginate(25),
        ]);
    }

    public function decideApproval(
        Request $request,
        string $approvalId
    ): JsonResponse {
        $data = $request->validate([
            'decision' => [
                'required',
                'in:approved,rejected',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        $this->service->decideApproval(
            $request->user(),
            $approvalId,
            $data['decision'],
            $data['notes'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Approval decision recorded.',
        ]);
    }

    public function createWebhook(
        Request $request,
        string $workflowId
    ): JsonResponse {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Incoming webhook created.',
            'data' => $this->service
                ->createIncomingWebhook(
                    $request->user(),
                    $workflowId,
                    $data['name']
                ),
        ], 201);
    }
}
