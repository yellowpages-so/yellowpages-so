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
        DB::statement('CREATE SCHEMA IF NOT EXISTS support');

        Schema::create('support.queues', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedInteger('default_sla_minutes')->default(1440);
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('support.tickets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('ticket_no')->unique();
            $table->uuid('requester_user_id')->nullable();
            $table->uuid('business_id')->nullable();
            $table->uuid('queue_id')->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->string('subject');
            $table->text('description');
            $table->string('channel')->default('web');
            $table->string('priority')->default('normal');
            $table->string('status')->default('open');
            $table->string('requester_name')->nullable();
            $table->string('requester_email')->nullable();
            $table->string('requester_phone')->nullable();
            $table->timestampTz('first_response_due_at')->nullable();
            $table->timestampTz('resolution_due_at')->nullable();
            $table->timestampTz('first_responded_at')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->timestampTz('closed_at')->nullable();
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('requester_user_id')
                ->references('id')
                ->on('iam.users')
                ->nullOnDelete();

            $table->foreign('business_id')
                ->references('id')
                ->on('directory.businesses')
                ->nullOnDelete();

            $table->foreign('queue_id')
                ->references('id')
                ->on('support.queues')
                ->nullOnDelete();

            $table->foreign('assigned_to')
                ->references('id')
                ->on('iam.users')
                ->nullOnDelete();

            $table->index(['status', 'priority', 'created_at']);
            $table->index(['assigned_to', 'status']);
            $table->index(['business_id', 'created_at']);
        });

        Schema::create('support.ticket_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('user_id')->nullable();
            $table->string('sender_type');
            $table->text('body');
            $table->boolean('internal')->default(false);
            $table->jsonb('metadata')->default(DB::raw("'{}'::jsonb"));
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('ticket_id')
                ->references('id')
                ->on('support.tickets')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('iam.users')
                ->nullOnDelete();

            $table->index(['ticket_id', 'created_at']);
        });

        Schema::create('support.ticket_attachments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('message_id')->nullable();
            $table->uuid('media_asset_id')->nullable();
            $table->string('name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('ticket_id')
                ->references('id')
                ->on('support.tickets')
                ->cascadeOnDelete();

            $table->foreign('message_id')
                ->references('id')
                ->on('support.ticket_messages')
                ->cascadeOnDelete();
        });

        Schema::create('support.ticket_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id');
            $table->uuid('actor_user_id')->nullable();
            $table->string('event_type');
            $table->jsonb('before')->nullable();
            $table->jsonb('after')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('ticket_id')
                ->references('id')
                ->on('support.tickets')
                ->cascadeOnDelete();

            $table->foreign('actor_user_id')
                ->references('id')
                ->on('iam.users')
                ->nullOnDelete();

            $table->index(['ticket_id', 'created_at']);
        });

        Schema::create('support.sla_policies', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('queue_id')->nullable();
            $table->string('priority');
            $table->unsignedInteger('first_response_minutes');
            $table->unsignedInteger('resolution_minutes');
            $table->boolean('active')->default(true);
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('queue_id')
                ->references('id')
                ->on('support.queues')
                ->cascadeOnDelete();

            $table->unique(['queue_id', 'priority']);
        });

        Schema::create('support.escalation_rules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('queue_id')->nullable();
            $table->string('name');
            $table->string('condition_type');
            $table->unsignedInteger('threshold_minutes');
            $table->string('action_type');
            $table->uuid('target_user_id')->nullable();
            $table->boolean('active')->default(true);
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('queue_id')
                ->references('id')
                ->on('support.queues')
                ->cascadeOnDelete();

            $table->foreign('target_user_id')
                ->references('id')
                ->on('iam.users')
                ->nullOnDelete();
        });

        Schema::create('support.knowledge_categories', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('support.knowledge_articles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('category_id')->nullable();
            $table->uuid('author_id');
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('status')->default('draft');
            $table->string('locale', 10)->default('en');
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('helpful_count')->default(0);
            $table->unsignedBigInteger('not_helpful_count')->default(0);
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('category_id')
                ->references('id')
                ->on('support.knowledge_categories')
                ->nullOnDelete();

            $table->foreign('author_id')
                ->references('id')
                ->on('iam.users')
                ->cascadeOnDelete();

            $table->index(['status', 'published_at']);
        });

        Schema::create('support.faqs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('category_id')->nullable();
            $table->string('question');
            $table->text('answer');
            $table->boolean('featured')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();

            $table->foreign('category_id')
                ->references('id')
                ->on('support.knowledge_categories')
                ->nullOnDelete();

            $table->index(['active', 'featured', 'sort_order']);
        });

        Schema::create('support.chat_conversations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->uuid('assigned_to')->nullable();
            $table->string('status')->default('open');
            $table->string('channel')->default('web');
            $table->timestampTz('last_message_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('user_id')
                ->references('id')
                ->on('iam.users')
                ->nullOnDelete();

            $table->foreign('assigned_to')
                ->references('id')
                ->on('iam.users')
                ->nullOnDelete();

            $table->index(['status', 'last_message_at']);
        });

        Schema::create('support.chat_messages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->uuid('user_id')->nullable();
            $table->string('sender_type');
            $table->text('body');
            $table->timestampTz('read_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('conversation_id')
                ->references('id')
                ->on('support.chat_conversations')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('iam.users')
                ->nullOnDelete();

            $table->index(['conversation_id', 'created_at']);
        });

        Schema::create('support.surveys', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('ticket_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('survey_type');
            $table->unsignedSmallInteger('score');
            $table->text('comment')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('ticket_id')
                ->references('id')
                ->on('support.tickets')
                ->nullOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('iam.users')
                ->nullOnDelete();

            $table->index(['survey_type', 'created_at']);
        });

        $queueId = (string) Str::uuid();

        DB::table('support.queues')->insert([
            'id' => $queueId,
            'code' => 'general',
            'name' => 'General Support',
            'description' => 'Default customer support queue.',
            'active' => true,
            'default_sla_minutes' => 1440,
            'created_at' => now(),
        ]);

        foreach ([
            ['urgent', 30, 240],
            ['high', 120, 480],
            ['normal', 480, 1440],
            ['low', 1440, 4320],
        ] as [$priority, $response, $resolution]) {
            DB::table('support.sla_policies')->insert([
                'id' => (string) Str::uuid(),
                'queue_id' => $queueId,
                'priority' => $priority,
                'first_response_minutes' => $response,
                'resolution_minutes' => $resolution,
                'active' => true,
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support.surveys');
        Schema::dropIfExists('support.chat_messages');
        Schema::dropIfExists('support.chat_conversations');
        Schema::dropIfExists('support.faqs');
        Schema::dropIfExists('support.knowledge_articles');
        Schema::dropIfExists('support.knowledge_categories');
        Schema::dropIfExists('support.escalation_rules');
        Schema::dropIfExists('support.sla_policies');
        Schema::dropIfExists('support.ticket_events');
        Schema::dropIfExists('support.ticket_attachments');
        Schema::dropIfExists('support.ticket_messages');
        Schema::dropIfExists('support.tickets');
        Schema::dropIfExists('support.queues');
    }
};
