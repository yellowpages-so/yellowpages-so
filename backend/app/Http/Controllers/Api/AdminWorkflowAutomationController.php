<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\AdminAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminWorkflowAutomationController extends Controller
{
    public function dashboard(
        Request $request
    ): JsonResponse {
        AdminAccess::authorize(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'data' => [
                'active_workflows' => DB::table(
                    'automation.workflows'
                )
                    ->where('active', true)
                    ->count(),
                'queued_executions' => DB::table(
                    'automation.executions'
                )
                    ->where('status', 'queued')
                    ->count(),
                'failed_executions' => DB::table(
                    'automation.executions'
                )
                    ->where('status', 'failed')
                    ->count(),
                'pending_approvals' => DB::table(
                    'automation.approval_tasks'
                )
                    ->where('status', 'pending')
                    ->count(),
                'dead_letters' => DB::table(
                    'automation.dead_letters'
                )
                    ->whereNull('replayed_at')
                    ->count(),
            ],
        ]);
    }

    public function executions(
        Request $request
    ): JsonResponse {
        AdminAccess::authorize(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'data' => DB::table(
                'automation.executions'
            )
                ->orderByDesc('created_at')
                ->paginate(100),
        ]);
    }
}
