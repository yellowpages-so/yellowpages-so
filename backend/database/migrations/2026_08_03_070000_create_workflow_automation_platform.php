<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS automation');

        Schema::create('automation.workflows', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('business_id')->nullable();
            $table->uuid('created_by');
            $table->string('name');
            $table->string('code')->unique();
            $table->string('status')->default('draft');
            $table->string('trigger_type');
            $table->jsonb('trigger_config')->default(DB::raw("'{}'::jsonb"));
            $table->jsonb('settings')->default(DB::raw("'{}'::jsonb"));
            $table->unsignedInteger('version')->default(1);
            $table->boolean('active')->default(false);
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->timestampTz('deleted_at')->nullable();

            $table->foreign('business_id')
                ->references('id')
                ->on('directory.businesses')
                ->nullOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('iam.users')
                ->cascadeOnDelete();

            $table->index(['business_id', 'status']);
            $table->index(['trigger_type', 'active']);
        });

        Schema::create('automation.workflow_steps', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('workflow_id');
            $table->uuid('parent_step_id')->nullable();
            $table->string('step_type');
            $table->string('name');
            $table->jsonb('configuration')->default(DB::raw("'{}'::jsonb"));
            $table->jsonb('conditions')->default(DB::raw("'[]'::jsonb"));
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('workflow_id')
                ->references('id')
                ->on('automation.workflows')
                ->cascadeOnDelete();

            $table->index(['workflow_id', 'sort_order']);
        });

        Schema::create('automation.workflow_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('workflow_id');
            $table->unsignedInteger('version');
            $table->jsonb('definition');
            $table->uuid('created_by');
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('workflow_id')
                ->references('id')
                ->on('automation.workflows')
                ->cascadeOnDelete();

            $table->foreign('created_by')
                ->references('id')
                ->on('iam.users')
                ->cascadeOnDelete();

            $table->unique(['workflow_id', 'version']);
        });

        Schema::create('automation.executions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('workflow_id');
            $table->string('status')->default('queued');
            $table->string('trigger_type');
            $table->string('trigger_event_id')->nullable();
            $table->jsonb('input')->default(DB::raw("'{}'::jsonb"));
            $table->jsonb('context')->default(DB::raw("'{}'::jsonb"));
            $table->jsonb('output')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('workflow_id')
                ->references('id')
                ->on('automation.workflows')
                ->cascadeOnDelete();

            $table->index(['status', 'created_at']);
            $table->index(['workflow_id', 'created_at']);
        });

        Schema::create('automation.execution_steps', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('execution_id');
            $table->uuid('workflow_step_id')->nullable();
            $table->string('status')->default('pending');
            $table->jsonb('input')->default(DB::raw("'{}'::jsonb"));
            $table->jsonb('output')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('retry_at')->nullable();
            $table->text('error_message')->nullable();

            $table->foreign('execution_id')
                ->references('id')
                ->on('automation.executions')
                ->cascadeOnDelete();

            $table->foreign('workflow_step_id')
                ->references('id')
                ->on('automation.workflow_steps')
                ->nullOnDelete();

            $table->index(['status', 'retry_at']);
        });

        Schema::create('automation.schedules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('workflow_id');
            $table->string('schedule_type');
            $table->string('cron_expression')->nullable();
            $table->string('timezone')->default('Africa/Mogadishu');
            $table->boolean('active')->default(true);
            $table->timestampTz('next_run_at')->nullable();
            $table->timestampTz('last_run_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('workflow_id')
                ->references('id')
                ->on('automation.workflows')
                ->cascadeOnDelete();

            $table->index(['active', 'next_run_at']);
        });

        Schema::create('automation.approval_tasks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('execution_id');
            $table->uuid('workflow_step_id')->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->string('status')->default('pending');
            $table->string('title');
            $table->text('description')->nullable();
            $table->jsonb('payload')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('due_at')->nullable();
            $table->timestampTz('decided_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('execution_id')
                ->references('id')
                ->on('automation.executions')
                ->cascadeOnDelete();

            $table->foreign('workflow_step_id')
                ->references('id')
                ->on('automation.workflow_steps')
                ->nullOnDelete();

            $table->foreign('assigned_to')
                ->references('id')
                ->on('iam.users')
                ->nullOnDelete();

            $table->index(['assigned_to', 'status', 'due_at']);
        });

        Schema::create('automation.webhooks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('workflow_id');
            $table->string('direction');
            $table->string('name');
            $table->string('endpoint_key')->unique();
            $table->text('target_url')->nullable();
            $table->string('secret_hash')->nullable();
            $table->boolean('active')->default(true);
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('workflow_id')
                ->references('id')
                ->on('automation.workflows')
                ->cascadeOnDelete();
        });

        Schema::create('automation.dead_letters', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('execution_id')->nullable();
            $table->uuid('execution_step_id')->nullable();
            $table->string('reason');
            $table->jsonb('payload')->default(DB::raw("'{}'::jsonb"));
            $table->unsignedInteger('attempts')->default(0);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('replayed_at')->nullable();

            $table->foreign('execution_id')
                ->references('id')
                ->on('automation.executions')
                ->nullOnDelete();

            $table->foreign('execution_step_id')
                ->references('id')
                ->on('automation.execution_steps')
                ->nullOnDelete();

            $table->index(['replayed_at', 'created_at']);
        });

        Schema::create('automation.templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('category');
            $table->text('description')->nullable();
            $table->jsonb('definition');
            $table->boolean('active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
        });

        foreach ([
            [
                'lead_follow_up',
                'Lead Follow-up',
                'leads',
                'Notify the business owner and create follow-up tasks after a lead arrives.',
            ],
            [
                'verification_review',
                'Verification Review',
                'verification',
                'Route verification requests through review and approval.',
            ],
            [
                'review_request',
                'Review Request',
                'reviews',
                'Send a review request after an order is completed.',
            ],
            [
                'subscription_reminder',
                'Subscription Reminder',
                'billing',
                'Notify customers before subscription renewal or expiry.',
            ],
            [
                'sla_escalation',
                'SLA Escalation',
                'support',
                'Escalate overdue support tickets.',
            ],
        ] as [$code, $name, $category, $description]) {
            DB::table('automation.templates')->insert([
                'id' => (string) Str::uuid(),
                'code' => $code,
                'name' => $name,
                'category' => $category,
                'description' => $description,
                'definition' => json_encode([
                    'trigger' => [],
                    'steps' => [],
                ]),
                'active' => true,
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('automation.templates');
        Schema::dropIfExists('automation.dead_letters');
        Schema::dropIfExists('automation.webhooks');
        Schema::dropIfExists('automation.approval_tasks');
        Schema::dropIfExists('automation.schedules');
        Schema::dropIfExists('automation.execution_steps');
        Schema::dropIfExists('automation.executions');
        Schema::dropIfExists('automation.workflow_versions');
        Schema::dropIfExists('automation.workflow_steps');
        Schema::dropIfExists('automation.workflows');
    }
};
