<?php

use App\Http\Controllers\Api\AdminWorkflowAutomationController;
use App\Http\Controllers\Api\WorkflowAutomationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/automation')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::get(
            '/workflows',
            [WorkflowAutomationController::class, 'workflows']
        );

        Route::post(
            '/workflows',
            [WorkflowAutomationController::class, 'createWorkflow']
        );

        Route::post(
            '/workflows/{workflowId}/publish',
            [WorkflowAutomationController::class, 'publish']
        );

        Route::get(
            '/workflows/{workflowId}/executions',
            [WorkflowAutomationController::class, 'executions']
        );

        Route::post(
            '/workflows/{workflowId}/webhooks',
            [WorkflowAutomationController::class, 'createWebhook']
        );

        Route::get(
            '/approvals',
            [WorkflowAutomationController::class, 'approvals']
        );

        Route::post(
            '/approvals/{approvalId}/decision',
            [WorkflowAutomationController::class, 'decideApproval']
        );

        Route::get(
            '/admin/dashboard',
            [AdminWorkflowAutomationController::class, 'dashboard']
        );

        Route::get(
            '/admin/executions',
            [AdminWorkflowAutomationController::class, 'executions']
        );
    });
